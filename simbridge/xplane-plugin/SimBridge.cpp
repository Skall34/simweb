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
#define SIMBRIDGE_BUF_SIZE 4096
// Guard au sol : sous ce IAS au sol (parking/taxi), aucun stall possible — alpha wrappe
// a ±180 au parking et les flags warning peuvent s'activer seuls. Au-dela (rotation,
// roulement rapide), on laisse passer les vrais flags.
#define SIMBRIDGE_STALL_GROUND_IAS_KT 40.0
// Seuil AoA générique (deg) si l'avion n'expose pas acf_stall_warn_alpha (avions sans REP)
#define SIMBRIDGE_STALL_ALPHA_FALLBACK_DEG 15.0

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
    // stall_warning / stall_warning_ratio : calculés plus bas (warning OU ratio >= 0.9)
    // << le champ que XUIPC ne renvoie pas
    // overspeed_warning est calculé (ias > Vne), pas de DataRef direct dans XP12
    { "crashed",         "sim/flightmodel2/misc/has_crashed",            'i', 1.0,       0.0, nullptr },
    { "fuel_total_kg",   "sim/flightmodel/weight/m_fuel_total",          'f', 1.0,       0.0, nullptr },
    { "plane_weight_kg", "sim/flightmodel/weight/m_total",               'f', 1.0,       0.0, nullptr }, // poids brut
    { "payload_kg",      "sim/flightmodel/weight/m_fixed",               'f', 1.0,       0.0, nullptr }, // poids des stations (payload)
    { "battery_on",      "sim/cockpit/electrical/battery_on",            'i', 1.0,       0.0, nullptr },
    { "avionics_on",     "sim/cockpit2/switches/avionics_power_on",      'i', 1.0,       0.0, nullptr },
    { "flaps_ratio",     "sim/flightmodel2/controls/flap1_deploy_ratio", 'f', 1.0,       0.0, nullptr }, // cockpit2/controls/flap_ratio est REPLACED dans XP12
    { "com1_mhz",        "sim/cockpit2/radios/actuators/com1_frequency_hz", 'i', 1e-6,   0.0, nullptr }, // Hz -> MHz
    { "com1_stdby_mhz",  "sim/cockpit2/radios/actuators/com1_standby_frequency_hz", 'i', 1e-6, 0.0, nullptr }, // offset FSUIPC 0x311A
    { "squawk",          "sim/cockpit2/radios/actuators/transponder_code",  'i', 1.0,    0.0, nullptr },
    { "squawk_mode",     "sim/cockpit2/radios/actuators/transponder_mode",  'i', 1.0,    0.0, nullptr }, // offset FSUIPC 0x6D8C (off=0,stdby=1,on=2,alt=3...)
    { "paused",          "sim/time/paused",                                 'i', 1.0,       0.0, nullptr }, // offset FSUIPC 0x0264 (approx 0x3365 inMenu)
    { "gear_retractable","sim/aircraft/parts/acf_gear_type",                'i', 1.0,       0.0, nullptr }, // [0] != 0 => train rentrant (offset 0x060C) ; lu via index 0
    { "flaps_available", "sim/aircraft/parts/acf_flapEQ",                   'i', 1.0,       0.0, nullptr }, // offset FSUIPC 0x0778
    { "vert_accel_ms2",  "sim/flightmodel/position/local_ay",              'f', 1.0,       0.0, nullptr }, // offset FSUIPC 0x3068 (accél. verticale, m/s2)
    { "view_type",       "sim/graphics/view/view_type",                     'i', 1.0,       0.0, nullptr }, // offset FSUIPC 0x8320 (mode caméra)
};
static const int g_numScalars = sizeof(g_scalars) / sizeof(g_scalars[0]);

// DataRefs chaînes (lus avec XPLMGetDatab) : immat / modèle / type avion
struct StringRef {
    const char* key;
    const char* dataref;
    XPLMDataRef handle;
};
static StringRef g_strings[] = {
    { "tail_number",       "sim/aircraft/view/acf_tailnum",        nullptr }, // offset FSUIPC 0x313C (et 0x3130 flight number)
    { "aircraft_model",    "sim/aircraft/view/acf_descrip",        nullptr }, // offset FSUIPC 0x3500 (description)
    { "aircraft_type",     "sim/aircraft/view/acf_relative_path",  nullptr }, // offset FSUIPC 0x3D00 : basename du path ACF sans .acf (= ce que XPUIPC mettait dans 0x3D00, ex "Cessna 172 SP GTN REP ")
    { "aircraft_ui_name",  "sim/aircraft/view/acf_ui_name",        nullptr }, // nom vu dans l'UI (debug, ex "Cessna Skyhawk + GTN + REP")
};
static const int g_numStrings = sizeof(g_strings) / sizeof(g_strings[0]);

// DataRefs tableaux (moteurs + train) : lus avec XPLMGetDatavi / XPLMGetDatavf
static XPLMDataRef g_refNumEngines   = nullptr;  // sim/aircraft/engine/acf_num_engines (int)
static XPLMDataRef g_refEngRunning   = nullptr;  // sim/flightmodel/engine/ENGN_running (int[])
static XPLMDataRef g_refEngN1        = nullptr;  // sim/flightmodel/engine/ENGN_N1_ (float[], % of max) — pas de RPM direct dans XP12
static XPLMDataRef g_refVne          = nullptr;  // sim/aircraft/view/acf_Vne (kias) pour calculer overspeed_warning
static XPLMDataRef g_refStallWarn    = nullptr;  // sim/cockpit2/annunciators/stall_warning (bool, cockpit)
static XPLMDataRef g_refStallFm      = nullptr;  // sim/flightmodel/failures/stallwarning (bool, physique — fiable aussi sur avions SASL/REP)
static XPLMDataRef g_refStallRatio   = nullptr;  // sim/cockpit2/annunciators/stall_warning_ratio (0..1)
static XPLMDataRef g_refAlpha        = nullptr;  // sim/flightmodel/position/alpha (AoA, degrés)
static XPLMDataRef g_refStallAlpha   = nullptr;  // sim/aircraft/overflow/acf_stall_warn_alpha (AoA d'alerte, degrés)
static XPLMDataRef g_refEngFuelFlow  = nullptr;  // sim/flightmodel/engine/ENGN_FF_ (float[], kg/s)
static XPLMDataRef g_refGearDeploy   = nullptr;  // sim/flightmodel2/gear/deploy_ratio (float[])
static XPLMDataRef g_refEngMap       = nullptr;  // sim/cockpit2/engine/indicators/MPR_in_hg (float[], inHg) — offset FSUIPC 0x08C0

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

// Ajoute "key":"valeur" au buffer JSON (échappe " et \)
static void json_str(char* buf, size_t& len, const char* key, const char* v)
{
    int n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"%s\":\"", key);
    if (n > 0) len += (size_t)n < (SIMBRIDGE_BUF_SIZE - len) ? (size_t)n : (SIMBRIDGE_BUF_SIZE - len - 1);
    for (const char* p = v; *p && len < SIMBRIDGE_BUF_SIZE - 4; p++) {
        if (*p == '"' || *p == '\\') {
            buf[len++] = '\\';
            buf[len++] = *p;
        } else if ((unsigned char)*p >= 0x20) {
            buf[len++] = *p;
        }
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\",");
    if (n > 0) len += (size_t)n < (SIMBRIDGE_BUF_SIZE - len) ? (size_t)n : (SIMBRIDGE_BUF_SIZE - len - 1);
}

// Lit un DataRef chaîne dans out (tronqué à max-1, terminé par \0)
static void read_string(XPLMDataRef ref, char* out, int max)
{
    out[0] = '\0';
    if (!ref) return;
    XPLMGetDatab(ref, out, 0, max - 1);
    out[max - 1] = '\0';
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
        if (g_refEngN1)       XPLMGetDatavf(g_refEngN1,      rpm,     0, numEng);
        if (g_refEngFuelFlow) XPLMGetDatavf(g_refEngFuelFlow, ff,     0, numEng);
    }
    float gear = 0.0f;
    if (g_refGearDeploy) XPLMGetDatavf(g_refGearDeploy, &gear, 0, 1);

    // overspeed_warning calculé : IAS > Vne (pas d'annonciateur overspeed dans XP12)
    double iasKt = 0.0, vneKt = 0.0;
    for (int i = 0; i < g_numScalars; i++)
        if (strcmp(g_scalars[i].key, "ias_kt") == 0) iasKt = read_scalar(g_scalars[i]);
    if (g_refVne) vneKt = (double)XPLMGetDataf(g_refVne);
    json_num(buf, len, "overspeed_warning", (vneKt > 0.0 && iasKt > vneKt) ? 1.0 : 0.0, "%.0f");

    // stall : flag flightmodel physique (universel, indépendant SASL/REP) OU annonciateur
    // cockpit OU ratio >= 0.9 OU AoA >= AoA d'alerte.
    // Guard "ceinture" : au sol a basse vitesse, aucun stall possible (alpha wrappe a ±180
    // au parking et stallWarn/stallFm peuvent s'activer seuls) -> stall force a 0.
    // Fallback universel : si acf_stall_warn_alpha absent (avion sans REP), seuil 15 deg.
    int stallWarn = 0;       if (g_refStallWarn)  stallWarn  = XPLMGetDatai(g_refStallWarn);
    int stallFm = 0;         if (g_refStallFm)    stallFm    = XPLMGetDatai(g_refStallFm);
    double stallRatio = 0.0; if (g_refStallRatio) stallRatio = (double)XPLMGetDataf(g_refStallRatio);
    double alpha = 0.0;      if (g_refAlpha)      alpha      = (double)XPLMGetDataf(g_refAlpha);
    double stallAlpha = 0.0; if (g_refStallAlpha) stallAlpha = (double)XPLMGetDataf(g_refStallAlpha);
    double stallAlphaEff = (stallAlpha > 0.0) ? stallAlpha : SIMBRIDGE_STALL_ALPHA_FALLBACK_DEG;
    double onGround = 0.0;
    for (int i = 0; i < g_numScalars; i++)
        if (strcmp(g_scalars[i].key, "on_ground") == 0) onGround = read_scalar(g_scalars[i]);
    bool groundSlow = (onGround != 0.0 && iasKt < SIMBRIDGE_STALL_GROUND_IAS_KT);
    bool stall = !groundSlow && ((stallFm != 0) || (stallWarn != 0)
                 || (stallRatio >= 0.9) || (alpha >= stallAlphaEff));
    json_num(buf, len, "stall_warning", stall ? 1.0 : 0.0, "%.0f");
    json_num(buf, len, "stall_warning_ratio", stallRatio, "%.3f");
    json_num(buf, len, "alpha", alpha, "%.2f");
    json_num(buf, len, "stall_alpha", stallAlpha, "%.2f");

    // tableaux JSON
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"eng_running\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%d%s", running[i], i + 1 < numEng ? "," : "");
        if (n > 0) len += (size_t)n;
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "],\"eng_n1_pct\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%.2f%s", (double)rpm[i], i + 1 < numEng ? "," : "");
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

    // manifold pressure (inHg) par moteur — offset FSUIPC 0x08C0
    float map[SIMBRIDGE_MAX_ENGINES] = {0};
    if (numEng > 0 && g_refEngMap) XPLMGetDatavf(g_refEngMap, map, 0, numEng);
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "\"eng_map_inhg\":[");
    if (n > 0) len += (size_t)n;
    for (int i = 0; i < numEng; i++) {
        n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "%.2f%s", (double)map[i], i + 1 < numEng ? "," : "");
        if (n > 0) len += (size_t)n;
    }
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "],");
    if (n > 0) len += (size_t)n;

    // chaînes : immat, modèle, type avion (offsets FSUIPC 0x313C/0x3500/0x3D00)
    for (int i = 0; i < g_numStrings; i++) {
        char s[512];
        read_string(g_strings[i].handle, s, sizeof(s));
        if (strcmp(g_strings[i].key, "aircraft_type") == 0) {
            // Nom du DOSSIER contenant l'acf -> colle a XPUIPC 0x3D00
            // (ex: ".../Cessna 172 SP GTN REP /Cessna_172SP.acf" -> "Cessna 172 SP GTN REP ")
            char* last = nullptr;  // séparateur avant le fichier .acf
            char* prev = nullptr;  // séparateur avant le dossier
            for (char* p = s; *p; p++) if (*p == '\\' || *p == '/') { prev = last; last = p; }
            if (last && prev && last > prev + 1) {
                size_t l = (size_t)(last - prev - 1);
                if (l > 500) l = 500;
                char dir[512];
                memcpy(dir, prev + 1, l);
                dir[l] = '\0';
                json_str(buf, len, g_strings[i].key, dir);
            } else {
                json_str(buf, len, g_strings[i].key, s);
            }
        } else {
            json_str(buf, len, g_strings[i].key, s);
        }
    }

    // retire la dernière virgule et ferme l'objet
    if (len > 0 && buf[len - 1] == ',') len--;
    n = snprintf(buf + len, SIMBRIDGE_BUF_SIZE - len, "}");
    if (n > 0) len += (size_t)n;
    return len;
}

static long g_loopTicks = 0;

static float flight_loop_cb(float, float, int, void*)
{
    if (!g_enabled || g_sock == INVALID_SOCKET) return SIMBRIDGE_INTERVAL_SEC;
    char buf[SIMBRIDGE_BUF_SIZE];
    size_t len = build_frame(buf);
    if (len > 0) {
        int rc = sendto(g_sock, buf, (int)len, 0, (struct sockaddr*)&g_dest, sizeof(g_dest));
        g_loopTicks++;
        if (rc == SOCKET_ERROR || g_loopTicks % 100 == 1) { // log 1ère trame puis toutes les ~10 s
            char msg[160];
#ifdef _WIN32
            snprintf(msg, sizeof(msg), "SimBridge: tick=%ld sendto rc=%d wsaErr=%d len=%zu\n", g_loopTicks, rc, WSAGetLastError(), len);
#else
            snprintf(msg, sizeof(msg), "SimBridge: tick=%ld sendto rc=%d len=%zu\n", g_loopTicks, rc, len);
#endif
            XPLMDebugString(msg);
        }
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
    g_refEngN1       = XPLMFindDataRef("sim/flightmodel/engine/ENGN_N1_");
    g_refVne         = XPLMFindDataRef("sim/aircraft/view/acf_Vne");
    g_refStallWarn   = XPLMFindDataRef("sim/cockpit2/annunciators/stall_warning");
    g_refStallFm     = XPLMFindDataRef("sim/flightmodel/failures/stallwarning");
    g_refStallRatio  = XPLMFindDataRef("sim/cockpit2/annunciators/stall_warning_ratio");
    g_refAlpha       = XPLMFindDataRef("sim/flightmodel/position/alpha");
    g_refStallAlpha  = XPLMFindDataRef("sim/aircraft/overflow/acf_stall_warn_alpha");
    g_refEngFuelFlow = XPLMFindDataRef("sim/flightmodel/engine/ENGN_FF_");
    g_refGearDeploy  = XPLMFindDataRef("sim/flightmodel2/gear/deploy_ratio");
    g_refEngMap      = XPLMFindDataRef("sim/cockpit2/engine/indicators/MPR_in_hg");
    for (int i = 0; i < g_numStrings; i++) {
        g_strings[i].handle = XPLMFindDataRef(g_strings[i].dataref);
        if (!g_strings[i].handle) {
            char msg[256];
            snprintf(msg, sizeof(msg), "SimBridge: DataRef string introuvable (champ vide): %s\n", g_strings[i].dataref);
            XPLMDebugString(msg);
        }
    }

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
