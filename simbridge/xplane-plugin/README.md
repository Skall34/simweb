# SimBridge.xpl — build et installation

Plugin X-Plane 12 (C++17, X-Plane SDK) qui émet les DataRefs choisis en JSON
sur UDP `127.0.0.1:49180` à 10 Hz (voir `../PROTOCOL.md`).

## Prérequis (à faire une fois)

1. **Charge de travail C++ dans Visual Studio** : VS Installer → Modifier →
   onglet « Charges de travail » → cocher **« Développement Desktop en C++ »**
   (apporte `cl.exe`, la prise en charge des `.vcxproj` dans la boîte de dialogue
   Ouvrir, et le support CMake). Sans elle, VS dira « n'est pas un fichier projet ».
2. **SDK X-Plane 4.x** : télécharger
   `https://developer.x-plane.com/wp-content/plugins/code-sample-generation/sdk_zip_files/XPSDK430.zip`
   et dézipper (le dossier resulting contient `SDK/CHeaders` et `SDK/Libraries`).
   Note : SDK 4.3.0 = X-Plane 12. Recent : essayer `XPSDK470.zip`, etc.

## Build — méthode A : projet VS (recommandé)

1. Fichier > Ouvrir > Projet/Solution → `SimBridge.vcxproj`
   (visible dans la liste une fois la workload C++ installée).
2. Propriété du projet **XPlaneSDK** : mettre le chemin du dossier `SDK`
   (ex. `C:\X-Plane SDK\SDK`), ou passer `/p:XPlaneSDK=...` en ligne de commande,
   ou définir la variable d'environnement `XPlaneSDK`.
3. Sélectionner **Release | x64**, puis Générer → produit
   `build\x64\Release\SimBridge.xpl`.

En ligne de commande (Developer PowerShell / Prompt) :

```powershell
& "C:\Program Files\Microsoft Visual Studio\18\Professional\MSBuild\Current\Bin\MSBuild.exe" `
  "F:\GitHub\simweb\simbridge\xplane-plugin\SimBridge.vcxproj" `
  /p:Configuration=Release /p:Platform=x64 /p:XPlaneSDK="C:\X-Plane SDK\SDK"
```

## Build — méthode B : CMake (Open Folder)

Fichier > Ouvrir > Dossier → ouvrir `simbridge/xplane-plugin`, éditer
`CMakeSettings.json` (`cmakeCommandArgs` → `-DXP_SDK=...`), build **x64-Release**.
Ou en CLI : `cmake -B build -DXP_SDK="C:\X-Plane SDK\SDK" -G "Visual Studio 18 2026" -A x64`
puis `cmake --build build --config Release`.

## Installation

```
X-Plane 12/Resources/plugins/SimBridge/win_x64/SimBridge.xpl    (Windows)
X-Plane 12/Resources/plugins/SimBridge/lin_x64/SimBridge.xpl    (Linux)
X-Plane 12/Resources/plugins/SimBridge/mac_x64/SimBridge.xpl    (macOS)
```

Redémarrer X-Plane. Vérifier dans `Log.txt` :
`SimBridge: actif, UDP 127.0.0.1:49180 a 10 Hz`, et le menu **Plugins → SimBridge**.
Toute ligne `SimBridge: DataRef introuvable (champ a 0): ...` = un DataRef à
corriger dans `g_scalars[]` (voir DataRef Editor de XP12, menu Developer).

## DataRefs lus

Tableau `g_scalars[]` dans `SimBridge.cpp` (1 ligne par champ JSON) + DataRefs
tableaux moteurs/train. **Tout DataRef introuvable est null-guardé** : champ à 0
+ ligne dans `Log.txt`, jamais de crash. À valider dans le DataRef Editor :
`sim/flightmodel/failures/crashed` et `sim/cockpit2/annunciators/overspeed_warning`.

## Test sans X-Plane

`../tests/mock_plugin.py` émet les mêmes trames JSON :

```bash
python3 ../tests/mock_plugin.py --seconds 5
```
