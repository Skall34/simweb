// SimBridge — PoC de pont X-Plane 12 -> UDP JSON (remplacement moderne de XUIPC pour SimAddon)
//
// Principe : un flight-loop cadencé à 10 Hz lit une sélection de DataRefs X-Plane
// (position, vitesses, stall warning, moteurs, fuel, gear/flaps, COM1, transpondeur...)
// et émet une trame JSON sur UDP 127.0.0.1:49180. Côté SimAddon, SimBridgeClient.cs
// reçoit ces trames et alimente les mêmes champs que simData.cs (FSUIPC/XUIPC).
//
// Le schéma JSON est documenté dans simbridge/PROTOCOL.md.
//
// Licence : même licence que le projet simweb. Build : voir CMakeLists.txt (X-Plane SDK requis).

#include "XPLMPlugin.h"
#include "XPLMProcessing.h"
#include "XPLMDataAccess.h"
#include "XPLMUtilities.h"

#include <cstdio>
#include <cstring>
#include <cstdint>
#include <cmath>

#ifdef _WIN32
  #ifndef WIN32_LEAN_AND_MEAN
    #define WIN32_LEAN_AND_MEAN
  #endif
  #include <winsock2.h>
  #include <ws2tcpip.h>
  #include <windows.h>
  #pragma comment(lib, "ws2_32.lib")
  typedef int socklen_t;
#else
  #include <sys/socket.h>
  #include <netinet/in.h>
  #include <arpa/inet.h>
  #include <unistd.h>
  #include <time.h>
  #define INVALID_SOCKET (-1)
  #define SOCKET_ERROR (-1)
  typedef int SOCKET;
  static int closesocket(SOCKET s) { return ::close(s); }
#endif

#define SIMBRIDGE_UDP_PORT 49180
#define SIMBRIDGE_INTERVAL_SEC 0.1f   // 10 Hz
#define SIMBRIDGE_MAX_ENGINES 8
#define SIMBRIDGE_BUF_SIZE 2048

// ---------------------------------------------------------------------------
// Table des DataRefs scalaires : cle JSON, nom DataRef, type, échelle, offset.
// Tout DataRef introuvable est null-guardé : le champ vaut 0 (pas de crash).
// ---------------------------------------------------------------------------
struct ScalarRef {
    const char* key;      // clé JSON
    const char* dataref;  // nom du DataRef X-Plane
    char        kind;     // 'd' double, 'f' float, 'i' int, 'b' byte
    double      scale;    // valeur JSON = brut * scale + add
    double      add;
    XPLMDataRef handle;   // mis à jour dans SimBridgeEnable
};

static ScalarRef g_scalars[] = {
    { "lat",             "sim/flightmodel/position/latitude",            'd', 1.0,       0.0, nullptr },
    { "lon",             "sim/flightmodel/position/longitude",           'd', 1.0,       0.0, nullptr },
    { "alt_m",           "sim/flightmodel/position/elevation",           'd', 1.0,       0.0, nullptr },
    { "ias_kt",          "sim/flightmodel/position/indicated_airspeed",  'f', 1.9438445, 0.0, nullptr }, // m/s -> kt
    { "tas_kt",          "sim/flightmodel/position/true_airspeed",       'f', 1.9438445, 0.0, nullptr },
    { "gs_kt",           "sim/flightmodel/position/groundspeed",         'f', 1.9438445, 0.0, nullptr },
    { "vs_fpm",          "sim/flightmodel/position/vh_ind_fpm",          'f', 1.0,       0.0, nullptr },
    { "hdg_true",        "sim/flightmodel/position/psi",                 'f', 1.0,       0.0, nullptr },
    { "pitch",           "sim/flightmodel/position/theta",               'f', 1.0,       0.0, nullptr },
    { "bank",            "sim/flightmodel/position/phi",                 'f', 1.0,       0.0, nullptr },
    { "magvar_deg",      "sim/flightmodel/position/magnetic_variation",  'f', 1.0,       0.0, nullptr },
    { "on_ground",       "sim/flightmodel/failures/onground_any",        'i', 1.0,       0.0, nullptr },
    { "stall_warning",   "sim/cockpit2/annunciators/stall_warning",      'i', 1.0,       0.0, nullptr }, // << le champ que XUIPC ne renvoie pas
    { "overspeed_warning","sim/cockpit2/annunciators/overspeed_warning", 'i', 1.0,       0.0, nullptr }, // null-guardé : 0 si absent
    { "crashed",         "sim/flightmodel/failures/crashed",             'i', 1.0,       0.0, nullptr }, // null-guardé : 0 si absent (à valider dans le DataRef Editor XP12)
    { "fuel_total_kg",   "sim/flightmodel/weight/m_fuel_total",          'f', 1.0,       0.0, nullptr },
    { "plane_weight_kg", "sim/flightmodel/weight/m_total",               'f', 1.0,       0.0, nullptr }, // poids brut
    { "payload_kg",      "sim/flightmodel/weight/m_payload",             'f', 1.0,       0.0, nullptr }, // null-guardé : 0 si absent
    { "battery_on",      "sim/cockpit2/switches/battery_on",             'i', 1.0,       0.0, nullptr },
    { "avionics_on",     "sim/cockpit2/switches/avionics_power_on",      'i', 1.0,       0.0, nullptr },
    { "flaps_ratio",     "sim/cockpit2/controls/flap_ratio",             'f', 1.0,       0.0, nullptr },
    { "com1_mhz",        "sim/cockpit2/radios/actuators/com1_frequency_hz", 'i', 1e-6,   0.0, nullptr }, // Hz -> MHz
    { "squawk",          "sim/cockpit2/radios/actuators/transponder_code",  'i', 1.0,    0.0, nullptr },
};
static const int g_numScalars = sizeof(g_scalars) / sizeof(g_scalars[0]);

// DataRefs tableaux (moteurs + train) : lus avec XPLMGetDatavi / XPLMGetDatavf
static XPLMDataRef g_refNumEngines   = nullptr;  // sim/aircraft/engine/acf_num_engines (int)
static XPLMDataRef g_refEngRunning   = nullptr;  // sim/flightmodel/engine/ENGN_running (int[])
static XPLMDataRef g_refEngRpm       = nullptr;  // sim/flightmodel/engine/ENGN_RPM (float[])
static XPLMDataRef g_refEngFuelFlow  = nullptr;  // sim/flightmodel/engine/ENGN_FF_ (float[], kg/s)
static XPLMDataRef g_refGearDeploy   = nullptr;  // sim/flightmodel2/gear/deploy_ratio (float[])

static SOCKET g_sock = INVALID_SOCKET;
static struct sockaddr_in g_dest;
static bool g_enabled = false;

// ---------------------------------------------------------------------------
static double now_epoch_ms()
{
#ifdef _WIN32
    FILETIME ft; GetSystemTimeAsFileTime(&ft);
    ULARGE_INTEGER u; u.LowPart = ft.dwLowDateTime; u.HighPart = ft.dwHighDateTime;
    return (double)(u.QuadPart / 10000ULL); // 100-ns -> ms depuis 1601 ; on ajoute l'epoch delta
    // (delta 1601->1970 = 11644473600000 ms)
#else
    struct timespec ts; clock_gettime(CLOCK_REALTIME, &ts);
    return (double)ts.tv_sec * 1000.0 + (double)ts.tv_nsec / 1e6;
#endif
}

static double read_scalar(const ScalarRef& s)
{
    if (!s.handle) return 0.0;
    double raw = 0.0;
    switch (s.kind) {
        case 'd': raw = XPLMGetDatad(s.handle); break;
        case 'f': raw = (double)XPLMGetDataf(s.handle); break;
        case 'i': raw = (double)XPLMGetDatai(s.handle); break;
        case 'b': { int bv = 0; XPLMGetDatavi(s.handle, &bv, 0, 1); raw = (double)bv; } break; // SDK 4.x : lecture byte via tableau d'entiers
        default: raw = 0.0; break;
    }
    return raw * s.scale + s.add;
}

// Ajoute "key":value au buffer JSON (value déjà formatée)
static void json_num(char* buf, size_t& len, const char* key, double v, const char* fmt = "%.4f")
{
    char tmp[128];
    snprintf(tmp, sizeof(tmp), fmt, v);
    int n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"%s\":%s,", key, tmp);
    if (n > 0) len += (size_t)n < (SIMBRIDGE_BUF_SIZE - len) ? (size_t)n : (SIMBRIDGE_BUF_SIZE - len - 1);
}

static void json_int(char* buf, size_t& len, const char* key, long long v)
{
    int n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"%s\":%lld,", key, v);
    if (n > 0) len += (size_t)n < (SIMBRIDGE_BUF_SIZE - len) ? (size_t)n : (SIMBRIDGE_BUF_SIZE - len - 1);
}

// Construit la trame JSON complète ; retourne la longueur écrite
static size_t build_frame(char* buf)
{
    size_t len = 0;
    int n = snprintf(buf, SIMBRIDGE_BUF_SIZE, "{");
    if (n > 0) len = (size_t)n;

    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"type\":\"simbridge\",\"v\":1,");
    if (n > 0) len += (size_t)n;

#ifdef _WIN32
    double ts = now_epoch_ms() + 11644473600000.0;
#else
    double ts = now_epoch_ms();
#endif
    json_num(buf, len, "ts_ms", ts, "%.0f");

    // scalaires (lat/lon en haute précision)
    for (int i = 0; i < g_numScalars; i++) {
        const char* fmt = (g_scalars[i].kind == 'd') ? "%.8f" : "%.4f";
        json_num(buf, len, g_scalars[i].key, read_scalar(g_scalars[i]), fmt);
    }

    // alt_ft dérivée
    double alt_m = read_scalar(g_scalars[2]);
    json_num(buf, len, "alt_ft", alt_m * 3.280839895, "%.2f");

    // moteurs
    int numEng = 0;
    if (g_refNumEngines) numEng = XPLMGetDatai(g_refNumEngines);
    if (numEng < 0) numEng = 0;
    if (numEng > SIMBRIDGE_MAX_ENGINES) numEng = SIMBRIDGE_MAX_ENGINES;
    json_int(buf, len, "num_engines", numEng);

    int   running[SIMBRIDGE_MAX_ENGINES] = {0};
    float rpm[SIMBRIDGE_MAX_ENGINES]     = {0};
    float ff[SIMBRIDGE_MAX_ENGINES]      = {0};
    if (numEng > 0) {
        if (g_refEngRunning)  XPLMGetDatavi(g_refEngRunning, running, 0, numEng);
        if (g_refEngRpm)      XPLMGetDatavf(g_refEngRpm,     rpm,     0, numEng);
        if (g_refEngFuelFlow) XPLMGetDatavf(g_refEngFuelFlow, ff,     0, numEng);
    }
    float gear = 0.0f;
    if (g_refGearDeploy) XPLMGetDatavf(g_refGearDeploy, &gear, 0, 1);

    // tableaux JSON
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"eng_running\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%d%s", running[i], i + 1 < numEng ? "," : "");
        if (n > 0) len += (size_t)n;
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "],\"eng_rpm\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%.1f%s", (double)rpm[i], i + 1 < numEng ? "," : "");
        if (n > 0) len += (size_t)n;
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "],\"eng_ff_kg_s\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%.3f%s", (double)ff[i], i + 1 < numEng ? "," : "");
        if (n > 0) len += (size_t)n;
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "],\"gear_deploy\":%.3f,", (double)gear);
    if (n > 0) len += (size_t)n;

    // retire la dernière virgule et ferme l'objet
    if (len > 0 && buf[len - 1] == ',') len--;
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "}");
    if (n > 0) len += (size_t)n;
    return len;
}

static float flight_loop_cb(float, float, int, void*)
{
    if (!g_enabled || g_sock == INVALID_SOCKET) return SIMBRIDGE_INTERVAL_SEC;
    char buf[SIMBRIDGE_BUF_SIZE];
    size_t len = build_frame(buf);
    if (len > 0) {
        sendto(g_sock, buf, (int)len, 0, (struct sockaddr*)&g_dest, sizeof(g_dest));
    }
    return SIMBRIDGE_INTERVAL_SEC; // re-cadence à 10 Hz
}

// ---------------------------------------------------------------------------
// Plugin X-Plane
// ---------------------------------------------------------------------------
PLUGIN_API int XPluginStart(char* outName, char* outSig, char* outDesc)
{
    strcpy(outName, "SimBridge");
    strcpy(outSig,  "simweb.simbridge.poc");
    strcpy(outDesc, "PoC pont X-Plane 12 -> UDP JSON pour SimAddon (remplace XUIPC, stall warning inclus)");
    return 1;
}

PLUGIN_API void XPluginStop(void)
{
}

PLUGIN_API int XPluginEnable(void)
{
    // DataRefs scalaires
    for (int i = 0; i < g_numScalars; i++) {
        g_scalars[i].handle = XPLMFindDataRef(g_scalars[i].dataref);
        if (!g_scalars[i].handle) {
            char msg[256];
            snprintf(msg, sizeof(msg), "SimBridge: DataRef introuvable (champ a 0): %s\n", g_scalars[i].dataref);
            XPLMDebugString(msg);
        }
    }
    g_refNumEngines  = XPLMFindDataRef("sim/aircraft/engine/acf_num_engines");
    g_refEngRunning  = XPLMFindDataRef("sim/flightmodel/engine/ENGN_running");
    g_refEngRpm      = XPLMFindDataRef("sim/flightmodel/engine/ENGN_RPM");
    g_refEngFuelFlow = XPLMFindDataRef("sim/flightmodel/engine/ENGN_FF_");
    g_refGearDeploy  = XPLMFindDataRef("sim/flightmodel2/gear/deploy_ratio");

    // Socket UDP vers 127.0.0.1:49180
#ifdef _WIN32
    WSADATA wsa;
    if (WSAStartup(MAKEWORD(2, 2), &wsa) != 0) return 0;
#endif
    g_sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP);
    if (g_sock == INVALID_SOCKET) {
        XPLMDebugString("SimBridge: impossible de creer le socket UDP\n");
        return 0;
    }
    memset(&g_dest, 0, sizeof(g_dest));
    g_dest.sin_family = AF_INET;
    g_dest.sin_port = htons(SIMBRIDGE_UDP_PORT);
    inet_pton(AF_INET, "127.0.0.1", &g_dest.sin_addr);

    XPLMRegisterFlightLoopCallback(flight_loop_cb, SIMBRIDGE_INTERVAL_SEC, nullptr);
    g_enabled = true;

    char msg[128];
    snprintf(msg, sizeof(msg), "SimBridge: actif, UDP 127.0.0.1:%d a 10 Hz\n", SIMBRIDGE_UDP_PORT);
    XPLMDebugString(msg);
    return 1;
}

PLUGIN_API void XPluginDisable(void)
{
    g_enabled = false;
    XPLMUnregisterFlightLoopCallback(flight_loop_cb, nullptr);
    if (g_sock != INVALID_SOCKET) {
        closesocket(g_sock);
        g_sock = INVALID_SOCKET;
    }
#ifdef _WIN32
    WSACleanup();
#endif
    XPLMDebugString("SimBridge: desactive\n");
}

PLUGIN_API void XPluginReceiveMessage(int, long, void*)
{
}
