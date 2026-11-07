# Mémoire permanente GitHub Copilot — SimWeb

Ce fichier est un miroir de ce que GitHub Copilot conserve en mémoire persistante entre les conversations.
Dernière mise à jour : 13 avril 2026.

## Règles de workflow

- **Toujours vérifier l'impact sur la documentation** après chaque modification de code/logique. Inclure les mises à jour doc (`pages/doc_scripts/*`, lang keys `doc_*`) dans le scope de chaque changement sans qu'on ait besoin de le demander.
- **Documentation trilingue** : FR/EN/ES (`lang/fr.php`, `lang/en.php`, `lang/es.php`)
- **Projet SimWeb** : compagnie aérienne virtuelle, PHP/MySQL
- **Pas de CSS inline** : utiliser `css/styles.css` pour tous les styles. Ne pas mettre de `style="..."` dans le HTML sauf exception justifiée. Quand on crée ou modifie une page, ajouter les classes CSS dans la feuille de style externe.
- **Synchroniser ce fichier** à chaque ajout/modification de mémoire permanente.

## Structure de la base de données

La structure complète est documentée dans `/memories/repo/database_structure.md`.

### Tables principales

| Table | Description |
|-------|-------------|
| `PILOTES` | Comptes pilotes (callsign, grade_id, email, admin, revenus) |
| `FLOTTE` | Avions individuels (immat, status 0/1/2, etat %, localisation, hub) |
| `FLEET_TYPE` | Types d'appareils (cout_horaire, cout_appareil, cout_maintenance, type) |
| `CARNET_DE_VOL_GENERAL` | Entrées carnet de vol (pilote_id, appareil_id, mission_id) |
| `MAINTENANCES_LOG` | Historique maintenances (appareil_id, type: usure/crash/sortie) |
| `MISSIONS` | Types de missions avec libelle et majoration_mission |
| `GRADES` | Grades pilotes avec seuil_heures et taux_horaire |
| `LIGNES_REGULIERES` | Lignes régulières (icao_dep, icao_arr, type_ligne) |
| `RESERVATIONS` | Réservations de lignes par pilotes |
| `finances_depenses` / `finances_recettes` | Écritures comptables |
| `BALANCE_COMMERCIALE` | Solde financier de la compagnie |
| `VARIABLES_CONFIG` | Configuration clé-valeur |

### Colonnes critiques

- `FLOTTE.status` : 0=OK, 1=Maintenance, 2=Crash
- `FLOTTE.etat` : pourcentage état (0-100), déclenche maintenance si <10%
- `MISSIONS.libelle` : nom de la mission (pas "nom")
- `PILOTES.admin` : 1=administrateur
