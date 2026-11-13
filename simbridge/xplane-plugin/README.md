# SimBridge.xpl — build et installation

Plugin X-Plane 12 (C++17, X-Plane SDK) qui émet les DataRefs choisis en JSON
sur UDP `127.0.0.1:49180` à 10 Hz (voir `../PROTOCOL.md`).

## Build

Prérequis : le SDK X-Plane (CHeaders) et CMake ≥ 3.16.

```powershell
cmake -B build -DXP_SDK="C:\chemin\vers\X-Plane SDK\CHeaders" -DCMAKE_BUILD_TYPE=Release
cmake --build build --config Release
```

Le binaire produit est `build/Release/SimBridge.xpl`.

## Installation

```
X-Plane 12/Resources/plugins/SimBridge/win_x64/SimBridge.xpl    (Windows)
X-Plane 12/Resources/plugins/SimBridge/lin_x64/SimBridge.xpl    (Linux)
X-Plane 12/Resources/plugins/SimBridge/mac_x64/SimBridge.xpl    (macOS)
```

Redémarrer X-Plane. Le plugin apparaît dans **Plugins → SimBridge** ; le
`Log.txt` de X-Plane contient `SimBridge: actif, UDP 127.0.0.1:49180 a 10 Hz`.

## DataRefs lus

Voir le tableau `g_scalars[]` dans `SimBridge.cpp` (1 ligne par champ JSON) et
les DataRefs tableaux moteurs/train. **Tout DataRef introuvable est null-guardé**
(champ à 0 + ligne dans `Log.txt`) : un nom erroné ne fait pas planter le plugin.

À valider dans le DataRef Editor de X-Plane 12 : `crashed`
(`sim/flightmodel/failures/crashed`) et `overspeed_warning`.

## Test sans X-Plane

`../tests/mock_plugin.py` émet exactement les mêmes trames JSON :

```bash
python3 ../tests/mock_plugin.py --seconds 5
```
