# SimBridge (PoC) — pont X-Plane 12 → SimAddon en UDP JSON

PoC de l'option B : remplacer XUIPC par **notre propre pont** pour SimAddon.
XUIPC ne renvoie pas le stall warning (offset FSUIPC 0x036C) sur X-Plane, ce qui
bloque le `stall_ok` du vol de contrôle post-maintenance (voir
`includes/fonctions_controle_vol.php` et `api/api_import_vol_direct.php`, qui
attendent déjà `stall_ok` / `engine_cut_ok` côté API).

```
X-Plane 12 ── DataRefs ──> SimBridge.xpl ── UDP/JSON 127.0.0.1:49180 (10 Hz) ──> SimAddon (SimBridgeClient.cs) ── HTTP ──> API simweb
```

## Contenu

```
simbridge/
├── README.md                  ← ce fichier
├── PROTOCOL.md                ← schéma JSON v1 + exemple de trame
├── xplane-plugin/
│   ├── SimBridge.cpp          ← plugin X-Plane 12 (XPLM, C++17)
│   ├── CMakeLists.txt         ← build avec le SDK X-Plane
│   └── README.md              ← build + installation du .xpl
├── simaddon-client/
│   └── SimBridgeClient.cs     ← récepteur UDP (drop-in SimDataManager, sans dépendance)
└── tests/
    ├── mock_plugin.py         ← émetteur Python qui simule le plugin (mêmes trames)
    ├── test_loopback.py       ← test de boucle loopback (python seul)
    └── csharp-client-test/    ← test du vrai SimBridgeClient.cs (dotnet) contre un émetteur mock
```

## Lancer le PoC

1. **Plugin** : builder `xplane-plugin/` (voir son README), copier le `.xpl` dans
   `X-Plane 12/Resources/plugins/SimBridge/win_x64/`, lancer X-Plane.
2. **SimAddon** : copier `SimBridgeClient.cs` dans `SimDataManager/`, puis dans le
   code : `var bridge = new SimBridgeClient(); bridge.Start();` et lire
   `bridge.TryGetLatest(out var f)` — `f.StallWarning` alimente le même champ que
   `simData.GetStallWarning()`.
3. **Sans X-Plane** (test du protocole seul) :
   ```bash
   python3 simbridge/tests/test_loopback.py          # test python end-to-end
   cd simbridge/tests/csharp-client-test && dotnet run   # test du client C# réel
   ```

## Feuille de route après PoC

- Câbler `simData.cs` : source « FSUIPC/XUIPC » (MSFS/P3D) vs « SimBridge UDP » (X-Plane),
  sélection auto à la détection du pont.
- Étendre le schéma (METAR, poids, etc.) au besoin — 1 ligne par DataRef dans
  `SimBridge.cpp`, 1 champ dans `SimBridgeFrame`.
- Publier un installateur du `.xpl` avec le setup SimAddon.

Branche : `poc/simbridge-xplane-udp` (simweb) / `poc/simbridge-client` (SimAddon).
Rien n'est posé sur `main`.
