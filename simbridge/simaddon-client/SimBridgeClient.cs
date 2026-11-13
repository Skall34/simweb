// SimBridgeClient — récepteur UDP du pont SimBridge (PoC, branche poc/simbridge-client)
//
// Reçoit les trames JSON émises par le plugin X-Plane SimBridge.xpl
// (UDP 127.0.0.1:49180, 10 Hz, schéma dans simbridge/PROTOCOL.md) et expose
// la dernière trame reçue sous forme d'objet typé SimBridgeFrame.
//
// Objectif : alimenter les mêmes champs que simData.cs (qui lit via FSUIPC/XUIPC),
// notamment stall_warning (offset FSUIPC 0x036C) que XUIPC ne renvoie pas sur X-Plane.
//
// Aucune dépendance externe (parser JSON minimal intégré) : le fichier se compile
// aussi bien dans SimDataManager (net8.0) que dans un projet de test isolé.

using System;
using System.Collections.Generic;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;

namespace SimDataManager
{
    /// <summary>Trame SimBridge (schéma v1, voir PROTOCOL.md).</summary>
    public class SimBridgeFrame
    {
        public long TsMs;
        public double Lat, Lon, AltM, AltFt;
        public double IasKt, TasKt, GsKt, VsFpm;
        public double HdgTrue, Pitch, Bank, MagvarDeg;
        public int OnGround, StallWarning, OverspeedWarning, Crashed;
        public int NumEngines;
        public int[] EngRunning = new int[0];
        public double[] EngN1Pct = new double[0]; // % N1 max (XP12 n'expose pas de RPM direct)
        public double[] EngFfKgS = new double[0];
        public double FuelTotalKg, GearDeploy, FlapsRatio;
        public double PlaneWeightKg, PayloadKg;
        public int BatteryOn, AvionicsOn;
        public double Com1Mhz;
        public int Squawk;

        public bool IsAnyEngineCut
        {
            get
            {
                if (NumEngines < 2) return false;
                bool anyRunning = false, anyCut = false;
                for (int i = 0; i < NumEngines && i < EngRunning.Length; i++)
                {
                    if (EngRunning[i] == 1) anyRunning = true; else anyCut = true;
                }
                return anyCut && anyRunning;
            }
        }
    }

    /// <summary>
    /// Client UDP SimBridge : thread récepteur + última trame thread-safe.
    /// Usage PoC : var client = new SimBridgeClient(); client.Start(); ...
    /// client.TryGetLatest(out var f); puis comparer avec la source FSUIPC.
    /// </summary>
    public sealed class SimBridgeClient : IDisposable
    {
        public const int DefaultPort = 49180;

        private UdpClient _udp;
        private Thread _thread;
        private volatile bool _running;
        private readonly object _lock = new object();
        private SimBridgeFrame _latest;

        /// <summary>Levée à chaque trame reçue (sur le thread récepteur).</summary>
        public event Action<SimBridgeFrame> FrameReceived;

        public bool IsRunning => _running;

        public void Start(int port = DefaultPort)
        {
            if (_running) return;
            _udp = new UdpClient();
            _udp.Client.SetSocketOption(SocketOptionLevel.Socket, SocketOptionName.ReuseAddress, true);
            _udp.Client.Bind(new IPEndPoint(IPAddress.Any, port)); // reçoit les trames envoyées à 127.0.0.1:port
            _running = true;
            _thread = new Thread(ReceiveLoop) { IsBackground = true, Name = "SimBridgeClient" };
            _thread.Start();
        }

        public void Stop()
        {
            _running = false;
            try { _udp?.Close(); } catch { /* ignore */ }
            _udp = null;
        }

        /// <summary>Retourne la dernière trame reçue (null si aucune).</summary>
        public bool TryGetLatest(out SimBridgeFrame frame)
        {
            lock (_lock)
            {
                frame = _latest;
                return frame != null;
            }
        }

        private void ReceiveLoop()
        {
            var ep = new IPEndPoint(IPAddress.Any, 0);
            while (_running)
            {
                byte[] data;
                try { data = _udp.Receive(ref ep); }
                catch { break; } // socket fermé par Stop()
                if (data == null || data.Length == 0) continue;

                SimBridgeFrame f = null;
                try { f = ParseFrame(Encoding.UTF8.GetString(data)); }
                catch { /* trame ignorée */ }
                if (f == null) continue;

                lock (_lock) { _latest = f; }
                try { FrameReceived?.Invoke(f); } catch { /* ne pas tuer la boucle */ }
            }
        }

        // ------------------------------------------------------------------
        // Mapping trame JSON -> SimBridgeFrame
        // ------------------------------------------------------------------
        public static SimBridgeFrame ParseFrame(string json)
        {
            var o = MiniJson.ParseObject(json);
            if (o == null) return null;
            if (!"simbridge".Equals(o.TryGetValue("type", out var t) ? t as string : null)) return null;

            var f = new SimBridgeFrame();
            f.TsMs             = GetLong(o, "ts_ms");
            f.Lat              = GetDouble(o, "lat");
            f.Lon              = GetDouble(o, "lon");
            f.AltM             = GetDouble(o, "alt_m");
            f.AltFt            = GetDouble(o, "alt_ft");
            f.IasKt            = GetDouble(o, "ias_kt");
            f.TasKt            = GetDouble(o, "tas_kt");
            f.GsKt             = GetDouble(o, "gs_kt");
            f.VsFpm            = GetDouble(o, "vs_fpm");
            f.HdgTrue          = GetDouble(o, "hdg_true");
            f.Pitch            = GetDouble(o, "pitch");
            f.Bank             = GetDouble(o, "bank");
            f.MagvarDeg        = GetDouble(o, "magvar_deg");
            f.OnGround         = (int)GetDouble(o, "on_ground");
            f.StallWarning     = (int)GetDouble(o, "stall_warning");
            f.OverspeedWarning = (int)GetDouble(o, "overspeed_warning");
            f.Crashed          = (int)GetDouble(o, "crashed");
            f.NumEngines       = (int)GetDouble(o, "num_engines");
            f.EngRunning       = GetIntArray(o, "eng_running");
            f.EngN1Pct         = GetDoubleArray(o, "eng_n1_pct");
            f.EngFfKgS         = GetDoubleArray(o, "eng_ff_kg_s");
            f.FuelTotalKg      = GetDouble(o, "fuel_total_kg");
            f.PlaneWeightKg    = GetDouble(o, "plane_weight_kg");
            f.PayloadKg        = GetDouble(o, "payload_kg");
            f.BatteryOn        = (int)GetDouble(o, "battery_on");
            f.AvionicsOn       = (int)GetDouble(o, "avionics_on");
            f.GearDeploy       = GetDouble(o, "gear_deploy");
            f.FlapsRatio       = GetDouble(o, "flaps_ratio");
            f.Com1Mhz          = GetDouble(o, "com1_mhz");
            f.Squawk           = (int)GetDouble(o, "squawk");
            return f;
        }

        private static double GetDouble(Dictionary<string, object> o, string k)
            => o.TryGetValue(k, out var v) && v is double d ? d : 0.0;

        private static long GetLong(Dictionary<string, object> o, string k)
            => o.TryGetValue(k, out var v) && v is double d ? (long)d : 0L;

        private static int[] GetIntArray(Dictionary<string, object> o, string k)
        {
            var src = GetDoubleArray(o, k);
            var dst = new int[src.Length];
            for (int i = 0; i < src.Length; i++) dst[i] = (int)src[i];
            return dst;
        }

        private static double[] GetDoubleArray(Dictionary<string, object> o, string k)
        {
            if (!o.TryGetValue(k, out var v) || !(v is List<object> list)) return new double[0];
            var dst = new double[list.Count];
            for (int i = 0; i < list.Count; i++) dst[i] = list[i] is double d ? d : 0.0;
            return dst;
        }

        // ------------------------------------------------------------------
        // Mini parser JSON (schéma plat : objet de nombres/chaînes/tableaux de nombres)
        // ------------------------------------------------------------------
        private static class MiniJson
        {
            public static Dictionary<string, object> ParseObject(string s)
            {
                int i = 0;
                var r = ParseValue(s, ref i) as Dictionary<string, object>;
                return r;
            }

            private static object ParseValue(string s, ref int i)
            {
                SkipWs(s, ref i);
                if (i >= s.Length) return null;
                char c = s[i];
                if (c == '{') return ParseObj(s, ref i);
                if (c == '[') return ParseArr(s, ref i);
                if (c == '"') return ParseStr(s, ref i);
                if (c == 't' && s.Substring(i).StartsWith("true")) { i += 4; return 1.0; }
                if (c == 'f' && s.Substring(i).StartsWith("false")) { i += 5; return 0.0; }
                if (c == 'n' && s.Substring(i).StartsWith("null")) { i += 4; return null; }
                return ParseNum(s, ref i);
            }

            private static Dictionary<string, object> ParseObj(string s, ref int i)
            {
                var d = new Dictionary<string, object>();
                i++; // {
                SkipWs(s, ref i);
                if (i < s.Length && s[i] == '}') { i++; return d; }
                while (i < s.Length)
                {
                    SkipWs(s, ref i);
                    string key = ParseStr(s, ref i);
                    SkipWs(s, ref i);
                    if (i < s.Length && s[i] == ':') i++;
                    var val = ParseValue(s, ref i);
                    d[key] = val;
                    SkipWs(s, ref i);
                    if (i < s.Length && s[i] == ',') { i++; continue; }
                    if (i < s.Length && s[i] == '}') { i++; }
                    break;
                }
                return d;
            }

            private static List<object> ParseArr(string s, ref int i)
            {
                var l = new List<object>();
                i++; // [
                SkipWs(s, ref i);
                if (i < s.Length && s[i] == ']') { i++; return l; }
                while (i < s.Length)
                {
                    l.Add(ParseValue(s, ref i));
                    SkipWs(s, ref i);
                    if (i < s.Length && s[i] == ',') { i++; continue; }
                    if (i < s.Length && s[i] == ']') { i++; }
                    break;
                }
                return l;
            }

            private static string ParseStr(string s, ref int i)
            {
                var sb = new StringBuilder();
                if (i < s.Length && s[i] == '"') i++;
                while (i < s.Length && s[i] != '"')
                {
                    if (s[i] == '\\' && i + 1 < s.Length) { sb.Append(s[i + 1]); i += 2; }
                    else { sb.Append(s[i]); i++; }
                }
                if (i < s.Length) i++; // closing quote
                return sb.ToString();
            }

            private static object ParseNum(string s, ref int i)
            {
                int start = i;
                while (i < s.Length && (char.IsDigit(s[i]) || s[i] == '-' || s[i] == '+' || s[i] == '.' || s[i] == 'e' || s[i] == 'E')) i++;
                if (double.TryParse(s.Substring(start, i - start), System.Globalization.NumberStyles.Float,
                        System.Globalization.CultureInfo.InvariantCulture, out double d))
                    return d;
                return 0.0;
            }

            private static void SkipWs(string s, ref int i)
            {
                while (i < s.Length && char.IsWhiteSpace(s[i])) i++;
            }
        }

        public void Dispose() => Stop();
    }
}
