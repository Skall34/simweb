# SimBridge — protocole UDP (v1)

Trame JSON UTF-8, une datagramme UDP par trame, émise par le plugin X-Plane
`SimBridge.xpl` vers `127.0.0.1:49180` à **10 Hz**.

## Enveloppe

| Champ | Type | Description |
|---|---|---|
| `type` | string | toujours `"simbridge"` |
| `v` | number | version du schéma (`1`) |
| `ts_ms` | number | horodatage émetteur, ms epoch UTC |

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
| **`stall_warning`** | 0/1 | — | calculé par le plugin : `sim/cockpit2/annunciators/stall_warning` != 0 **OU** `sim/flightmodel/failures/stallwarning` != 0 **OU** `stall_warning_ratio` ≥ 0.9 **OU** `alpha` ≥ `stall_alpha` — **le champ que XUIPC ne renvoie pas** (robuste avions SASL/REP) |
| `stall_warning_ratio` | number | 0..1 | `sim/cockpit2/annunciators/stall_warning_ratio` |
| `alpha` | number | degrés | `sim/flightmodel/position/alpha` (angle d'attaque) |
| `stall_alpha` | number | degrés | `sim/aircraft/overflow/acf_stall_warn_alpha` (AoA d'alerte de l'avion) |
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
| `gear_deploy` | number | 0..1 (1 = sorti) | `sim/flightmodel2/gear/deploy_ratio[0]` |
| `flaps_ratio` | number | 0..1 | `sim/cockpit2/controls/flap_ratio` |
| `com1_mhz` | number | MHz | `sim/cockpit2/radios/actuators/com1_frequency_hz` |
| `squawk` | number | — | `sim/cockpit2/radios/actuators/transponder_code` |

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

## Transport

- UDP, perdable et non ordonné par nature : le récepteur prend toujours la trame
  la plus récente (pas de file d'attente).
- Port par défaut **49180**, constant `SIMBRIDGE_UDP_PORT` dans `SimBridge.cpp`.
