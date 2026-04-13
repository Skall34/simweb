-- Migration : ajout des colonnes nécessaires au bon fonctionnement du crédit
-- À exécuter une seule fois sur la base existante

-- Compteur mensuel de mois restants (remplace le décrément annuel de nb_annees_credit)
ALTER TABLE FLOTTE ADD COLUMN nb_mois_restants INT DEFAULT NULL AFTER nb_annees_credit;

-- Date de la dernière mensualité prélevée (protection contre double exécution)
ALTER TABLE FLOTTE ADD COLUMN derniere_mensualite DATE DEFAULT NULL AFTER reste_a_payer;

-- Initialiser nb_mois_restants pour les crédits en cours
UPDATE FLOTTE
SET nb_mois_restants = nb_annees_credit * 12
WHERE mode_achat = 'credit' AND reste_a_payer > 0 AND nb_annees_credit > 0;

-- Corriger remboursement pour les crédits en cours (doit = montant total du prêt)
UPDATE FLOTTE
SET remboursement = traite_payee_cumulee + reste_a_payer
WHERE mode_achat = 'credit' AND reste_a_payer > 0;
