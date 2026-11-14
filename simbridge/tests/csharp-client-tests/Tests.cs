// Tests de non-regression du client SimBridge (SimBridgeClient.cs), destines a
// etre executes AVANT toute distribution de SimAddon branche sur SimBridge.
//
// Couvre :
//  1. Golden schema v1 : trame de reference complete, chaque champ parse a la
//     bonne valeur -> detecte tout renommage de cle, changement de type, champ
//     oublie dans PROTOCOL.md.
//  2. Robustesse du parser : trames invalides/tronquees/etrangeres ne doivent
//     jamais lever d'exception (le thread reseau de SimAddon ne doit pas mourir).
//  3. Guards stall (ceinture/bretelles) : faux stall au sol filtre, vrai stall
//     en vol conserve, fallback AoA 15 deg pour avions sans REP.
//  4. Compatibilite recorder SimAddon : sequence type SimAddon.log rejouee, le
//     stall "affiche" (StallWarningEffective) ne doit etre 1 qu'en vol.
//  5. IsAnyEngineCut : regles moteur.
//
// Zero dependance NuGet (runner maison). Exit 0 = PASS, 1 = FAIL.

using System;
using System.Collections.Generic;
using System.Linq;
using SimDataManager;

static class Tests
{
    static int _fails = 0;
    static int _total = 0;

    static void Check(bool cond, string label)
    {
        _total++;
        Console.WriteLine((cond ? "  ok   " : "  FAIL ") + label);
        if (!cond) _fails++;
    }

    static void Eq(double expected, double actual, string label, double tol = 1e-6)
        => Check(Math.Abs(expected - actual) <= tol, $"{label} (attendu={expected}, obtenu={actual})");

    // Trame de reference : couvre TOUS les champs du schema v1 de PROTOCOL.md
    // avec des valeurs connues et distinctes.
    const string GoldenFrame =
        "{\"type\":\"simbridge\",\"v\":2,\"ts_ms\":1761000000000," +
        "\"lat\":45.72777778,\"lon\":4.94444444,\"alt_m\":210.5,\"alt_ft\":690.95," +
        "\"ias_kt\":98.2,\"tas_kt\":101.0,\"gs_kt\":97.5,\"vs_fpm\":120.0," +
        "\"hdg_true\":182.4,\"pitch\":0.5,\"bank\":-1.2,\"magvar_deg\":-1.8," +
        "\"on_ground\":0,\"stall_warning\":1,\"stall_warning_ratio\":0.95," +
        "\"alpha\":16.5,\"stall_alpha\":15.8," +
        "\"overspeed_warning\":1,\"crashed\":0," +
        "\"fuel_total_kg\":750.5,\"plane_weight_kg\":4200.0,\"payload_kg\":622.0," +
        "\"battery_on\":1,\"avionics_on\":1,\"flaps_ratio\":0.5,\"gear_deploy\":1.0," +
        "\"com1_mhz\":118.05,\"com1_stdby_mhz\":121.5,\"squawk\":1234,\"squawk_mode\":3," +
        "\"paused\":0,\"gear_retractable\":1,\"flaps_available\":1," +
        "\"vert_accel_ms2\":0.15,\"view_type\":1," +
        "\"tail_number\":\"F-GJF\\\"K\",\"aircraft_model\":\"Cessna 172\",\"aircraft_type\":\"C172 Skyhawk\"," +
        "\"num_engines\":2,\"eng_running\":[1,0],\"eng_n1_pct\":[65.2,0.0],\"eng_ff_kg_s\":[0.033,0.0],\"eng_map_inhg\":[24.5,0.0]}";

    static void GoldenSchema()
    {
        Console.WriteLine("== 1. Golden schema v1 (trame de reference complete) ==");
        var f = SimBridgeClient.ParseFrame(GoldenFrame);
        Check(f != null, "trame golden parsee");
        if (f == null) return;
        Eq(1761000000000.0, f.TsMs, "ts_ms");
        Eq(45.72777778, f.Lat, "lat", 1e-7);
        Eq(4.94444444, f.Lon, "lon", 1e-7);
        Eq(210.5, f.AltM, "alt_m");
        Eq(690.95, f.AltFt, "alt_ft");
        Eq(98.2, f.IasKt, "ias_kt");
        Eq(101.0, f.TasKt, "tas_kt");
        Eq(97.5, f.GsKt, "gs_kt");
        Eq(120.0, f.VsFpm, "vs_fpm");
        Eq(182.4, f.HdgTrue, "hdg_true");
        Eq(0.5, f.Pitch, "pitch");
        Eq(-1.2, f.Bank, "bank");
        Eq(-1.8, f.MagvarDeg, "magvar_deg");
        Eq(0, f.OnGround, "on_ground");
        Eq(1, f.StallWarning, "stall_warning");
        Eq(0.95, f.StallWarningRatio, "stall_warning_ratio");
        Eq(16.5, f.Alpha, "alpha");
        Eq(15.8, f.StallAlpha, "stall_alpha");
        Eq(1, f.OverspeedWarning, "overspeed_warning");
        Eq(0, f.Crashed, "crashed");
        Eq(750.5, f.FuelTotalKg, "fuel_total_kg");
        Eq(4200.0, f.PlaneWeightKg, "plane_weight_kg");
        Eq(622.0, f.PayloadKg, "payload_kg");
        Eq(1, f.BatteryOn, "battery_on");
        Eq(1, f.AvionicsOn, "avionics_on");
        Eq(0.5, f.FlapsRatio, "flaps_ratio");
        Eq(1.0, f.GearDeploy, "gear_deploy");
        Eq(118.05, f.Com1Mhz, "com1_mhz");
        Eq(1234, f.Squawk, "squawk");
        Eq(2, f.NumEngines, "num_engines");
        Check(f.EngRunning.Length == 2 && f.EngRunning[0] == 1 && f.EngRunning[1] == 0, "eng_running=[1,0]");
        Check(f.EngN1Pct.Length == 2 && Math.Abs(f.EngN1Pct[0] - 65.2) < 1e-6, "eng_n1_pct[0]=65.2");
        Check(f.EngFfKgS.Length == 2 && Math.Abs(f.EngFfKgS[0] - 0.033) < 1e-6, "eng_ff_kg_s[0]=0.033");
        Eq(121.5, f.Com1StdbyMhz, "com1_stdby_mhz");
        Eq(3, f.SquawkMode, "squawk_mode");
        Eq(0, f.Paused, "paused");
        Eq(1, f.GearRetractable, "gear_retractable");
        Eq(1, f.FlapsAvailable, "flaps_available");
        Eq(0.15, f.VertAccelMs2, "vert_accel_ms2");
        Eq(1, f.ViewType, "view_type");
        Check(f.TailNumber == "F-GJF\"K", $"tail_number avec échappement (obtenu='{f.TailNumber}')");
        Check(f.AircraftModel == "Cessna 172", "aircraft_model");
        Check(f.AircraftType == "C172 Skyhawk", "aircraft_type");
        Check(f.EngMapInHg.Length == 2 && Math.Abs(f.EngMapInHg[0] - 24.5) < 1e-6, "eng_map_inhg[0]=24.5");
    }

    // Tout champ du mapping offsets SimAddon (offsets/simaddon-offsets-mapping.tsv)
    // doit exister comme propriete publique de SimBridgeFrame -> couverture complete.
    static void CouvertureMappingOffsets()
    {
        Console.WriteLine("== 6. Couverture mapping offsets SimAddon -> SimBridgeFrame ==");
        string[] champs = {
            "TsMs", "Lat", "Lon", "AltM", "AltFt", "IasKt", "TasKt", "GsKt", "VsFpm",
            "HdgTrue", "Pitch", "Bank", "MagvarDeg", "OnGround",
            "StallWarning", "StallWarningRatio", "Alpha", "StallAlpha",
            "OverspeedWarning", "Crashed", "Paused",
            "NumEngines", "EngRunning", "EngN1Pct", "EngFfKgS", "EngMapInHg",
            "FuelTotalKg", "PlaneWeightKg", "PayloadKg",
            "BatteryOn", "AvionicsOn", "GearRetractable", "FlapsAvailable",
            "GearDeploy", "FlapsRatio",
            "Com1Mhz", "Com1StdbyMhz", "Squawk", "SquawkMode",
            "VertAccelMs2", "ViewType",
            "TailNumber", "AircraftModel", "AircraftType",
            "StallWarningEffective", "StallAlphaEffective", "StallByAoA", "IsAnyEngineCut",
        };
        var props = new HashSet<string>(
            typeof(SimBridgeFrame).GetProperties().Select(p => p.Name)
                .Concat(typeof(SimBridgeFrame).GetFields().Select(f => f.Name)));
        foreach (var c in champs)
            Check(props.Contains(c), $"propriete SimBridgeFrame.{c} existe");
    }

    static void ParserRobustesse()
    {
        Console.WriteLine("== 2. Robustesse du parser (aucune exception, jamais) ==");
        Check(SimBridgeClient.ParseFrame("") == null, "chaine vide -> null");
        Check(SimBridgeClient.ParseFrame("not json") == null, "texte non JSON -> null");
        Check(SimBridgeClient.ParseFrame("{") == null, "JSON tronque -> null");
        Check(SimBridgeClient.ParseFrame("{\"type\":\"simbridge\",\"v\":1,\"lat\":45.7") == null
              || SimBridgeClient.ParseFrame("{\"type\":\"simbridge\",\"v\":1,\"lat\":45.7") != null,
              "trame tronquee ne plante pas");
        Check(SimBridgeClient.ParseFrame("{\"type\":\"autre\",\"v\":1}") == null, "type != simbridge -> null");
        var noFields = SimBridgeClient.ParseFrame("{\"type\":\"simbridge\",\"v\":1}");
        Check(noFields != null && noFields.Lat == 0.0 && noFields.StallWarning == 0 && noFields.NumEngines == 0,
              "champs manquants -> trame non null, valeurs a 0");
        // eng_running plus long que num_engines : IsAnyEngineCut ne doit pas planter
        var mismatch = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"num_engines\":1,\"eng_running\":[1,0,1,0]}");
        Check(mismatch != null && !mismatch.IsAnyEngineCut, "eng_running > num_engines -> pas de crash, IsAnyEngineCut safe");
        var neg = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"ias_kt\":-5.0,\"alpha\":-179.2,\"lat\":-45.0,\"squawk\":-1}");
        Check(neg != null && neg.IasKt == -5.0 && neg.Alpha == -179.2 && neg.Squawk == -1, "valeurs negatives conservees");
        var big = SimBridgeClient.ParseFrame("{\"type\":\"simbridge\",\"ts_ms\":9999999999999,\"lat\":1e300}");
        Check(big != null && big.TsMs > 0, "grands nombres OK");
        var spaces = SimBridgeClient.ParseFrame("{ \"type\" : \"simbridge\" , \"v\" : 1 , \"ias_kt\" : 100.0 }");
        Check(spaces != null && Math.Abs(spaces.IasKt - 100.0) < 1e-6, "espaces autour des tokens OK");
        var emptyArr = SimBridgeClient.ParseFrame("{\"type\":\"simbridge\",\"num_engines\":0,\"eng_running\":[],\"eng_n1_pct\":[],\"eng_ff_kg_s\":[]}");
        Check(emptyArr != null && emptyArr.EngRunning.Length == 0 && !emptyArr.IsAnyEngineCut, "tableaux vides OK");
    }

    static void GuardsStall()
    {
        Console.WriteLine("== 3. Guards stall (ceinture + bretelles) ==");
        // Parking : alpha wrappe a ±180, flag parasite emis -> filtre cote client
        var ground = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"on_ground\":1,\"ias_kt\":0.0,\"stall_warning\":1," +
            "\"stall_warning_ratio\":0.0,\"alpha\":179.1,\"stall_alpha\":12.0}");
        Check(ground != null && ground.StallWarningEffective == 0, "parking (alpha=179, stall brut=1) -> effective 0");
        Check(ground != null && !ground.StallByAoA, "parking -> StallByAoA false");
        // Sol rapide (rotation, IAS 55 kt, on_ground qui traine) : vrai flag conserve
        var rot = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"on_ground\":1,\"ias_kt\":55.0,\"stall_warning\":1," +
            "\"stall_warning_ratio\":0.0,\"alpha\":16.0,\"stall_alpha\":12.0}");
        Check(rot != null && rot.StallWarningEffective == 1, "sol rapide IAS=55 stall=1 -> effective 1");
        // Vol, avion SANS REP (stall_alpha=0) : fallback AoA 15 deg
        var norep = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"on_ground\":0,\"ias_kt\":68.0,\"stall_warning\":0," +
            "\"stall_warning_ratio\":0.0,\"alpha\":16.5,\"stall_alpha\":0.0}");
        Check(norep != null && norep.StallAlphaEffective == 15.0, "sans REP -> StallAlphaEffective 15.0");
        Check(norep != null && norep.StallByAoA, "vol alpha=16.5 stall_alpha=0 -> StallByAoA true (fallback)");
        // Vol normal sous le seuil : rien
        var cruise = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"on_ground\":0,\"ias_kt\":98.0,\"stall_warning\":0," +
            "\"stall_warning_ratio\":0.2,\"alpha\":4.2,\"stall_alpha\":15.8}");
        Check(cruise != null && !cruise.StallByAoA && cruise.StallWarningEffective == 0, "vol alpha=4.2 -> pas de stall");
        // Vol avec annonciateur cockpit seul (avion REP) : effective = brut
        var rep = SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"on_ground\":0,\"ias_kt\":67.0,\"stall_warning\":1," +
            "\"stall_warning_ratio\":1.0,\"alpha\":20.6,\"stall_alpha\":12.0}");
        Check(rep != null && rep.StallWarningEffective == 1, "vol REP stall=1 -> effective 1");
    }

    static void SimAddonDbgCompat()
    {
        Console.WriteLine("== 4. Compat recorder SimAddon (sequence type SimAddon.log) ==");
        // Rejoue un extrait du log : parking avec faux stalls (alpha ±180),
        // decollage, vol avec vrai stall, atterrissage, parking.
        var seq = new (int onGround, double ias, int stallRaw, double alpha)[]
        {
            (1, 0.0, 0, -176.7),   // parking
            (1, 0.0, 1, 166.3),    // faux stall au sol (wrap alpha)
            (1, 0.0, 1, 14.2),     // faux stall au sol
            (1, 54.0, 0, 2.9),     // roulage
            (0, 120.6, 0, 5.2),    // decollage
            (0, 83.1, 1, 13.8),    // VRAI stall en vol
            (0, 67.7, 1, 20.6),    // stall profund
            (0, 130.7, 0, 5.3),    // remonte
            (1, 6.5, 0, 0.4),      // atterri
            (1, 0.0, 1, 179.1),    // parking, faux stall
            (1, 0.0, 0, 4.0),      // parking
        };
        int stallAfficheAuSol = 0, stallAfficheEnVol = 0;
        foreach (var s in seq)
        {
            var f = SimBridgeClient.ParseFrame(
                "{\"type\":\"simbridge\",\"on_ground\":" + s.onGround +
                ",\"ias_kt\":" + s.ias.ToString(System.Globalization.CultureInfo.InvariantCulture) +
                ",\"stall_warning\":" + s.stallRaw +
                ",\"stall_warning_ratio\":0.0,\"alpha\":" +
                s.alpha.ToString(System.Globalization.CultureInfo.InvariantCulture) + ",\"stall_alpha\":12.0}");
            int eff = f.StallWarningEffective;
            if (eff == 1 && s.onGround == 1) stallAfficheAuSol++;
            if (eff == 1 && s.onGround == 0) stallAfficheEnVol++;
        }
        Check(stallAfficheAuSol == 0, $"AUCUN stall affiche au sol (obtenu={stallAfficheAuSol})");
        Check(stallAfficheEnVol == 2, $"les 2 vrais stalls en vol conserves (obtenu={stallAfficheEnVol})");
    }

    static void EngineCut()
    {
        Console.WriteLine("== 5. IsAnyEngineCut ==");
        SimBridgeFrame Mk(int n, string run) => SimBridgeClient.ParseFrame(
            "{\"type\":\"simbridge\",\"num_engines\":" + n + ",\"eng_running\":[" + run + "]}");
        Check(!Mk(1, "0").IsAnyEngineCut, "1 moteur arrete -> false (mono-moteur)");
        Check(Mk(2, "1,0").IsAnyEngineCut, "[1,0] -> true");
        Check(!Mk(2, "0,0").IsAnyEngineCut, "[0,0] -> false (tout coupe)");
        Check(!Mk(2, "1,1").IsAnyEngineCut, "[1,1] -> false");
        Check(Mk(4, "1,1,0,1").IsAnyEngineCut, "[1,1,0,1] -> true");
        Check(!Mk(0, "").IsAnyEngineCut, "0 moteur -> false");
    }

    static int Main()
    {
        GoldenSchema();
        ParserRobustesse();
        GuardsStall();
        SimAddonDbgCompat();
        EngineCut();
        CouvertureMappingOffsets();
        Console.WriteLine();
        Console.WriteLine(_fails == 0
            ? $"PASS — {_total}/{_total} tests OK"
            : $"FAIL — {_fails}/{_total} tests en echec");
        return _fails == 0 ? 0 : 1;
    }
}
