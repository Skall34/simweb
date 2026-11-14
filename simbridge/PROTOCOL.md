# SimBridge — protocole UDP (v2, rétrocompatible v1)

Trame JSON UTF-8, une datagramme UDP par trame, émise par le plugin X-Plane
`SimBridge.xpl` vers `127.0.0.1:49180` à **10 Hz**.

## Enveloppe

| Champ | Type | Description |
|---|---|---|
| `type` | string | toujours `"simbridge"` |
| `v` | number | version du schéma (`2` ; un récepteur v1 ignore les champs inconnus) |
| `ts_ms` | number | horodatage émetteur, ms epoch UTC (sert aussi d'« initialized » FSUIPC 0x04D2 et de date/heure UTC 0x023B-0x240) |

## Champs (schéma v1)

| Champ | Type | Unité | DataRef X-Plane source |
|---|---|---|---|
| `lat` / `lon` | number | degrés décimaux | `sim/flightmodel/position/latitude` / `longitude` |
| `alt_m` / `alt_ft` | number | m / ft | `sim/flightmodel/position/elevation` (ft dérivé) |
| `ias_kt` / `tas_kt` / `gs_kt` | number | nœuds | `sim/flightmodel/position/{indicated,true}_airspeed`, `groundspeed` (m/s → kt) |
| `vs_fpm` | number | ft/min | `sim/flightmodel/position/vh_ind_fpm` |
| `hdg_true` / `pitch` / `bank` | number | degrés | `sim/flightmodel/position/psi` / `theta` / `phi` |
| `magvar_deg` | number | degrés | `sim/flightmodel/position/magnetic_variation` |
| `on_ground` | 0/1 | — | `sim/flightmodel/failures/onground_any` |
| **`stall_warning`** | 0/1 | — | calculé par le plugin : `sim/flightmodel/failures/stallwarning` != 0 **OU** `sim/cockpit2/annunciators/stall_warning` != 0 **OU** `stall_warning_ratio` ≥ 0.9 **OU** `alpha` ≥ `stall_alpha` (fallback 15° si l'avion n'expose pas `acf_stall_warn_alpha`) — **le champ que XUIPC ne renvoie pas** (robuste avions SASL/REP). **Guard au sol** : si `on_ground` = 1 et `ias_kt` < 40, `stall_warning` est forcé à 0 (au parking, `alpha` wrappe à ±180 et les flags peuvent s'activer seuls) |
| `stall_warning_ratio` | number | 0..1 | `sim/cockpit2/annunciators/stall_warning_ratio` |
| `alpha` | number | degrés | `sim/flightmodel/position/alpha` (angle d'attaque ; wrappe à ±180 au sol, ne pas l'interpréter si `on_ground` = 1) |
| `stall_alpha` | number | degrés | `sim/aircraft/overflow/acf_stall_warn_alpha` (AoA d'alerte de l'avion ; 0 si non exposé) |
| `overspeed_warning` | 0/1 | — | calculé par le plugin : `ias_kt > sim/aircraft/view/acf_Vne` (pas d'annonciateur overspeed dans XP12) |
| `crashed` | 0/1 | — | `sim/flightmodel2/misc/has_crashed` |
| `num_engines` | number | — | `sim/aircraft/engine/acf_num_engines` |
| `eng_running[]` | number[] | 0/1 par moteur | `sim/flightmodel/engine/ENGN_running` |
| `eng_n1_pct[]` | number[] | % N1 max par moteur (XP12 n'expose pas de RPM direct) | `sim/flightmodel/engine/ENGN_N1_` |
| `eng_ff_kg_s[]` | number[] | kg/s par moteur | `sim/flightmodel/engine/ENGN_FF_` |
| `fuel_total_kg` | number | kg | `sim/flightmodel/weight/m_fuel_total` |
| `plane_weight_kg` | number | kg (poids brut) | `sim/flightmodel/weight/m_total` |
| `payload_kg` | number | kg (somme des stations) | `sim/flightmodel/weight/m_fixed` |
| `battery_on` | 0/1 | — | `sim/cockpit/electrical/battery_on` |
| `avionics_on` | 0/1 | — | `sim/cockpit2/switches/avionics_power_on` |
| `gear_deploy` | number | 0..1 (1 = sorti) | `sim/flightmodel2/gear/deploy_ratio[0]` (FSUIPC 0x0BF0 = ×16383, fait côté client) |
| `flaps_ratio` | number | 0..1 | `sim/flightmodel2/controls/flap1_deploy_ratio` (FSUIPC 0x0BE0 = ×16383, côté client) |
| `com1_mhz` | number | MHz | `sim/cockpit2/radios/actuators/com1_frequency_hz` (FSUIPC 0x034E BCD, décodé côté client) |
| `com1_stdby_mhz` | number | MHz | `sim/cockpit2/radios/actuators/com1_standby_frequency_hz` (FSUIPC 0x311A) |
| `squawk` | number | — | `sim/cockpit2/radios/actuators/transponder_code` (FSUIPC 0x0354 BCD) |
| `squawk_mode` | number | 0..7 | `sim/cockpit2/radios/actuators/transponder_mode` (FSUIPC 0x6D8C : off=0, stdby=1, on=2, alt=3...) |
| `paused` | 0/1 | — | `sim/time/paused` (FSUIPC 0x0264 ; approx de 0x3365 inMenu) |
| `gear_retractable` | 0/1 | — | `sim/aircraft/parts/acf_gear_type[0]` != 0 (FSUIPC 0x060C) |
| `flaps_available` | 0/1 | — | `sim/aircraft/parts/acf_flapEQ` (FSUIPC 0x0778) |
| `vert_accel_ms2` | number | m/s² | `sim/flightmodel/position/local_ay` (FSUIPC 0x3068) |
| `view_type` | number | enum caméra | `sim/graphics/view/view_type` (FSUIPC 0x8320) |
| `eng_map_inhg[]` | number[] | inHg par moteur | `sim/cockpit2/engine/indicators/MPR_in_hg` (FSUIPC 0x08C0) |
| `tail_number` | string | immat | `sim/aircraft/view/acf_tailnum` (FSUIPC 0x313C, aussi 0x3130 flight number) |
| `aircraft_model` | string | description avion | `sim/aircraft/view/acf_descrip` (FSUIPC 0x3500) |
| `aircraft_type` | string | **nom du dossier contenant l'acf** — identique à ce que XPUIPC met dans 0x3D00 (clé de `planesdb.json`, ex `"Cessna 172 SP GTN REP "` avec espace final) | `sim/aircraft/view/acf_relative_path`, segment dossier extrait (FSUIPC 0x3D00) |
| `aircraft_ui_name` | string | nom vu dans l'UI X-Plane (debug, ex `"Cessna Skyhawk + GTN + REP"`) | `sim/aircraft/view/acf_ui_name` |

## Champs sans équivalent X-Plane (laissés de côté)

FSUIPC 0x0024 (`startSituation`, string écrite par un autre soft), 0x3123 (swap COM1,
commande write-only), 0x0848 (crash hors piste : pas de distinction directe, `crashed` seul),
0x0898/0x08C8 (RPM : XP12 n'expose pas de RPM direct, `eng_n1_pct` en % N1 est envoyé à la place).

## Exemple de trame

```json
{"type":"simbridge","v":1,"ts_ms":1761000000000,"lat":45.72777778,"lon":4.94444444,
"alt_m":210.5,"ias_kt":98.2,"tas_kt":101.0,"gs_kt":97.5,"vs_fpm":0.0,"hdg_true":182.4,
"pitch":0.5,"bank":-1.2,"magvar_deg":-1.8,"on_ground":1,"stall_warning":0,
"overspeed_warning":0,"crashed":0,"fuel_total_kg":750.5,"flaps_ratio":0.0,
"com1_mhz":118.05,"squawk":1234,"alt_ft":690.95,"num_engines":2,
"eng_running":[1,1],"eng_rpm":[2450.0,2448.0],"eng_ff_kg_s":[0.033,0.033],"gear_deploy":1.0}
```

## Côté récepteur (SimAddon)

`simaddon-client/SimBridgeClient.cs` (copié dans `SimDataManager/`) : `Start()` →
réception en arrière-plan, `TryGetLatest(out SimBridgeFrame)` → dernière trame.
`SimBridgeFrame.IsAnyEngineCut` reproduit la règle `simData.IsAnyEngineCut()`
(au moins 2 moteurs, un arrêté pendant qu'un autre tourne) pour `engine_cut_ok`.

Bretelles côté récepteur (même si le `.xpl` n'est pas recompilé) :

- `StallWarningEffective` — `stall_warning` filtré : au sol (`on_ground`=1) sous 40 kt, vaut 0 quoi qu'émette le plugin. **Utiliser celle-ci dans le recorder.**
- `StallAlphaEffective` — `stall_alpha` de l'avion, ou 15° si l'avion n'expose pas `acf_stall_warn_alpha` (avions sans REP).
- `StallByAoA` — vrai si en vol `alpha` ≥ `StallAlphaEffective` : détecte le stall par AoA même sans annonciateur (avions SASL/REP sans REP cockpit).

## Transport

- UDP, perdable et non ordonné par nature : le récepteur prend toujours la trame
  la plus récente (pas de file d'attente).
- Port par défaut **49180**, constant `SIMBRIDGE_UDP_PORT` dans `SimBridge.cpp`.
