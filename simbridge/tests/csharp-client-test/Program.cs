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
    // Vérifie les bretelles côté récepteur (guard au sol + fallback AoA sans REP)
    // sur des trames parsées à la main, sans réseau.
    static bool UnitGuards()
    {
        int fails = 0;
        void Check(bool cond, string label)
        {
            Console.WriteLine((cond ? "  ok   " : "  FAIL ") + label);
            if (!cond) fails++;
        }

        // 1. Parking : alpha wrappe a ±180, le plugin (vieux) émet stall_warning=1 -> filtre
        var ground = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":1,\"on_ground\":1,\"ias_kt\":0.0," +
            "\"stall_warning\":1,\"stall_warning_ratio\":0.0,\"alpha\":179.1,\"stall_alpha\":12.0}");
        Check(ground != null && ground.StallWarningEffective == 0, "sol parking (alpha=179, stall brut=1) -> effective 0");
        Check(ground != null && !ground.StallByAoA, "sol parking -> StallByAoA false");

        // 2. Sol rapide (rotation, IAS 55 kt, on_ground traine) : on laisse passer le vrai flag
        var rot = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":1,\"on_ground\":1,\"ias_kt\":55.0," +
            "\"stall_warning\":1,\"stall_warning_ratio\":0.0,\"alpha\":16.0,\"stall_alpha\":12.0}");
        Check(rot != null && rot.StallWarningEffective == 1, "sol rapide IAS=55 stall brut=1 -> effective 1");

        // 3. Vol, avion SANS REP (stall_alpha=0, pas d'annonciateur) : fallback AoA 15 deg
        var norep = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":1,\"on_ground\":0,\"ias_kt\":68.0," +
            "\"stall_warning\":0,\"stall_warning_ratio\":0.0,\"alpha\":16.5,\"stall_alpha\":0.0}");
        Check(norep != null && norep.StallAlphaEffective == 15.0, "vol sans REP -> StallAlphaEffective 15.0");
        Check(norep != null && norep.StallByAoA, "vol alpha=16.5 stall_alpha=0 -> StallByAoA true (fallback)");

        // 4. Vol normal sous le seuil AoA : pas de stall
        var cruise = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":1,\"on_ground\":0,\"ias_kt\":98.0," +
            "\"stall_warning\":0,\"stall_warning_ratio\":0.2,\"alpha\":4.2,\"stall_alpha\":15.8}");
        Check(cruise != null && !cruise.StallByAoA && cruise.StallWarningEffective == 0, "vol alpha=4.2 -> pas de stall");

        return fails == 0;
    }

    static int Main()
    {
        Console.WriteLine("== tests unitaires guard stall (ParseFrame) ==");
        bool unitOk = UnitGuards();
        Console.WriteLine(unitOk ? "UNIT PASS" : "UNIT FAIL");
        if (!unitOk) return 1;

        using var client = new SimBridgeClient();
        int received = 0, stallEffSeen = 0, stallEffGroundSeen = 0, engineCutSeen = 0;
        client.FrameReceived += f =>
        {
            Interlocked.Increment(ref received);
            if (f.StallWarningEffective == 1)
            {
                Interlocked.Increment(ref stallEffSeen);
                if (f.OnGround == 1) Interlocked.Increment(ref stallEffGroundSeen); // ne doit jamais arriver
            }
            if (f.IsAnyEngineCut) Interlocked.Increment(ref engineCutSeen);
        };
        // Port configurable (49181 si 49180 est occupé par une instance SimAddon en cours)
        int port = int.Parse(Environment.GetEnvironmentVariable("SIMBRIDGE_TEST_PORT") ?? SimBridgeClient.DefaultPort.ToString());
        client.Start(port);

        // Émetteur mock (équivalent C# de mock_plugin.py)
        using var tx = new UdpClient();
        var dest = new IPEndPoint(IPAddress.Loopback, port);
        var t0 = DateTime.UtcNow;
        for (int seq = 0; seq < 60; seq++)
        {
            double t = (DateTime.UtcNow - t0).TotalSeconds;
            bool stallAir = t >= 2.0 && t <= 3.0;  // vrai stall en vol
            bool stallRaw = stallAir || t <= 1.0;   // + faux stall au sol (wrap ±180 au parking, comme dans SimAddon.log)
            bool cut = t > 4.0;
            double ias = stallAir ? 68.0 : (t <= 1.0 ? 0.0 : 98.2);
            string json = "{\"type\":\"simbridge\",\"v\":1,\"ts_ms\":" + ((long)((DateTime.UtcNow - new DateTime(1970, 1, 1)).TotalMilliseconds)) + ","
                + "\"lat\":45.7277,\"lon\":4.9444,\"alt_m\":210.5,\"alt_ft\":691.0,"
                + "\"ias_kt\":" + ias.ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"tas_kt\":101.0,\"gs_kt\":97.5,\"vs_fpm\":0.0,"
                + "\"hdg_true\":182.4,\"pitch\":" + (stallAir ? 8.0 : 0.5).ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"bank\":-1.2,\"magvar_deg\":-1.8,"
                + "\"on_ground\":" + (t <= 1.0 ? 1 : 0) + ",\"stall_warning\":" + (stallRaw ? 1 : 0) + ",\"stall_warning_ratio\":" + (stallAir ? 1.0 : 0.0).ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"alpha\":" + (t <= 1.0 ? 179.1 : (stallAir ? 16.5 : 4.2)).ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"stall_alpha\":15.8,\"overspeed_warning\":0,\"crashed\":0,"
                + "\"fuel_total_kg\":740.0,\"plane_weight_kg\":4200.0,\"payload_kg\":622.0,"
                + "\"battery_on\":1,\"avionics_on\":1,\"flaps_ratio\":0.0,\"com1_mhz\":118.05,\"squawk\":1234,"
                + "\"num_engines\":2,"
                + "\"eng_running\":[" + (cut ? "1,0" : "1,1") + "],"
                + "\"eng_n1_pct\":[" + (cut ? "0.0,0.0" : "65.2,64.9") + "],"
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
            Console.WriteLine($"latest: lat={f.Lat} alt_ft={f.AltFt} ias={f.IasKt} stall={f.StallWarning} stallEff={f.StallWarningEffective} squawk={f.Squawk} engines={f.NumEngines} running=[{string.Join(",", f.EngRunning)}]");

        bool pass = received >= 50 && stallEffSeen > 0 && stallEffGroundSeen == 0 && engineCutSeen > 0 && ok
                    && f.Lat > 45.0 && f.Squawk == 1234 && f.NumEngines == 2;
        Console.WriteLine(pass
            ? $"PASS — stall effectif en vol sur {stallEffSeen} trames, AUCUN au sol (ground={stallEffGroundSeen}), arrêt moteur sur {engineCutSeen} trames"
            : $"FAIL — stallEffSeen={stallEffSeen} stallEffGroundSeen={stallEffGroundSeen} engineCutSeen={engineCutSeen}");
        return pass ? 0 : 1;
    }
}
