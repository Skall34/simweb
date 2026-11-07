# Migrations SQL Archivées

Ces fichiers sont des **scripts de migration** pour les bases de données existantes.

⚠️ **Ne pas exécuter sur une nouvelle installation** — toutes ces modifications sont déjà incluses dans `01_Main_Database.sql`.

## Fichiers

| Fichier | Description | Condition d'exécution |
|---------|-------------|----------------------|
| `add_credit_fields.sql` | Colonnes crédit dans FLOTTE | Si colonnes `nb_mois_restants`, `derniere_mensualite` absentes |
| `add_maintenance_cost.sql` | Colonne `cout_maintenance` dans FLEET_TYPE | Si colonne absente |
| `add_lien_acars.sql` | Variable `lien_acars` dans VARIABLES_CONFIG | Si variable absente |
| `add_maintenances_log.sql` | Table `MAINTENANCES_LOG` | Si table absente |

## Usage

Pour une base existante qui n'a pas ces éléments :

```sql
-- Vérifier si la migration est nécessaire
SHOW TABLES LIKE 'MAINTENANCES_LOG';
DESCRIBE FLOTTE;
SELECT * FROM VARIABLES_CONFIG WHERE nom = 'lien_acars';

-- Si nécessaire, exécuter le script approprié
SOURCE migrations_archive/add_maintenances_log.sql;
```
