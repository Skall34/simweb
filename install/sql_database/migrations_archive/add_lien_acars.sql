-- Script pour ajouter la variable lien_acars dans VARIABLES_CONFIG
-- À exécuter une seule fois pour les installations existantes

INSERT INTO VARIABLES_CONFIG (nom, valeur) 
VALUES ('lien_acars', 'assets/acars/simaddon_setup.zip')
ON DUPLICATE KEY UPDATE valeur = valeur;

-- Note: ON DUPLICATE KEY UPDATE valeur = valeur permet d'éviter une erreur 
-- si la variable existe déjà, sans écraser la valeur existante
