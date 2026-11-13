// Test du vrai SimBridgeClient.cs : un émetteur mock envoie des trames SimBridge
// (même schéma que mock_plugin.py, avec un épisode stall_warning=1 et un arrêt
// moteur eng_running=[1,0]), le client C# doit les recevoir et les parser.
// Exit 0 = PASS, 1 = FAIL.

using System;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading;
using SimDataManager;

class Program
{
    static int Main()
    {
        using var client = new SimBridgeClient();
        int received = 0, stallSeen = 0, engineCutSeen = 0;
        client.FrameReceived += f =>
        {
            Interlocked.Increment(ref received);
            if (f.StallWarning == 1) Interlocked.Increment(ref stallSeen);
            if (f.IsAnyEngineCut) Interlocked.Increment(ref engineCutSeen);
        };
        client.Start(SimBridgeClient.DefaultPort);

        // Émetteur mock (équivalent C# de mock_plugin.py)
        using var tx = new UdpClient();
        var dest = new IPEndPoint(IPAddress.Loopback, SimBridgeClient.DefaultPort);
        var t0 = DateTime.UtcNow;
        for (int seq = 0; seq < 60; seq++)
        {
            double t = (DateTime.UtcNow - t0).TotalSeconds;
            bool stall = t >= 2.0 && t <= 3.0;
            bool cut = t > 4.0;
            string json = "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":" + ((long)((DateTime.UtcNow - new DateTime(1970, 1, 1)).TotalMilliseconds)) + ","
                + "\"lat\":45.7277,\"lon\":4.9444,\"alt_m\":210.5,\"alt_ft\":691.0,"
                + "\"ias_kt\":" + (stall ? 68.0 : 98.2).ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"tas_kt\":101.0,\"gs_kt\":97.5,\"vs_fpm\":0.0,"
                + "\"hdg_true\":182.4,\"pitch\":" + (stall ? 8.0 : 0.5).ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"bank\":-1.2,\"magvar_deg\":-1.8,"
                + "\"on_ground\":" + (t <= 1.0 ? 1 : 0) + ",\"stall_warning\":" + (stall ? 1 : 0) + ",\"overspeed_warning\":0,\"crashed\":0,"
                + "\"fuel_total_kg\":740.0,\"flaps_ratio\":0.0,\"com1_mhz\":118.05,\"squawk\":1234,"
                + "\"num_engines\":2,"
                + "\"eng_running\":[" + (cut ? "1,0" : "1,1") + "],"
                + "\"eng_rpm\":[" + (cut ? "0.0,0.0" : "2450.0,2448.0") + "],"
                + "\"eng_ff_kg_s\":[" + (cut ? "0.0,0.0" : "0.033,0.033") + "],"
                + "\"gear_deploy\":" + (t <= 1.0 ? "1.0" : "0.0") + "}";
            byte[] payload = Encoding.UTF8.GetBytes(json);
            tx.Send(payload, payload.Length, dest);
            Thread.Sleep(100);
        }

        Thread.Sleep(500); // laisse arriver les dernières trames

        bool ok = client.TryGetLatest(out var f) && f != null;
        Console.WriteLine($"trames reçues={received}, latest ok={ok}");
        if (ok)
            Console.WriteLine($"latest: lat={f.Lat} alt_ft={f.AltFt} ias={f.IasKt} stall={f.StallWarning} squawk={f.Squawk} engines={f.NumEngines} running=[{string.Join(",", f.EngRunning)}]");

        bool pass = received >= 50 && stallSeen > 0 && engineCutSeen > 0 && ok
                    && f.Lat > 45.0 && f.Squawk == 1234 && f.NumEngines == 2;
        Console.WriteLine(pass
            ? $"PASS — stall_warning détecté sur {stallSeen} trames, arrêt moteur sur {engineCutSeen} trames"
            : $"FAIL — stallSeen={stallSeen} engineCutSeen={engineCutSeen}");
        return pass ? 0 : 1;
    }
}
