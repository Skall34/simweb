# Offsets XPUIPC / FSUIPC — table de référence

`xpuipc-offsets.tsv` : les **575 offsets** documentés par XPUIPC 2.0.3.8 pour
X-Plane 12 (extraits automatiquement du PDF `XPUIPC Offsets 20140803.pdf` livré
dans `X:\X-Plane12\Resources\plugins\XPUIPC\`).

| Colonne | Contenu |
|---|---|
| `offset` | adresse FSUIPC-style, ex `0x036C` |
| `size_octets` | taille en octets (1, 2, 4, 8...) |
| `acces` | `r` lecture, `w` écriture, `rw` les deux (vide = lecture seule implicite) |
| `description` | description FSUIPC de l'offset |

## Ce qu'il faut savoir

- **0x036C = Stall warning (1 octet)** et **0x42C5 = Overspeed warning** sont
  documentés par XPUIPC, mais XPUIPC ne les remplit pas sur X-Plane 12
  (son binaire ne mappe que `sim/flightmodel/failures/stallwarning` /
  `sim/aircraft/view/acf_has_stallwarn`, insuffisant). C'est précisément le
  trou que SimBridge bouche (champ `stall_warning` calculé multi-sources +
  guard au sol, voir PROTOCOL.md).
- La table hard-codée dans `win.xpl` n'est pas récupérable proprement (offsets
  compilés en binaire) ; le PDF + `XPUIPCOffsets.cfg` (66 offsets custom) sont
  les sources fiables.
- **FSUIPC (MSFS2020/2024)** : sa doc liste ~5000 offsets, mais ils ne servent
  que côté MSFS — SimAddon les lit déjà via FSUIPC7. Pour X-Plane 12, seule la
  table XPUIPC ci-dessus est pertinente.

## Usage prévu

Pour chaque offset réellement lu par SimAddon (lister avec un grep `0x` dans
`simData.cs` côté projet Visual Studio), on écrit le DataRef X-Plane équivalent
(dans `SimBridge.cpp`, validé contre `X:\X-Plane12\Resources\plugins\DataRefs.txt`)
et on l'ajoute au schéma JSON v1. Ainsi SimAddon peut lire n'importe quel offset
XPUIPC connu sans régression — y compris ceux que le PoC n'a pas prévus.
