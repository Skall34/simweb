<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Paiement des salaires des pilotes</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère le paiement mensuel des salaires des pilotes. Il calcule le salaire selon les heures de vol du mois précédent (taux horaire selon grade) et le fret transporté (bonus 2€/kg), insère le salaire dans la table SALAIRES, met à jour le revenu cumulé du pilote, met à jour le paiement des salaires dans BALANCE_COMMERCIALE, envoie un mail au pilote et un récapitulatif à l'administrateur, et logue chaque étape.</p>
        <h3 style="margin-top:18px;">Fonctionnement détaillé :</h3>
        <ol style="margin-left:18px;">
          <li>Sélectionne tous les pilotes dans <b>PILOTES</b>.</li>
          <li>Pour chaque pilote :
            <ul style="font-size:0.95em;margin-top:4px;padding-left:28px;">
              <li>Calcule le nombre d'heures de vol du mois précédent.</li>
              <li>Récupère le taux horaire selon le grade.</li>
              <li>Calcule le bonus fret (2€/kg transporté).</li>
              <li>Calcule le montant total du salaire.</li>
              <li>Insère le salaire dans la table SALAIRES.</li>
              <li>Met à jour le revenu cumulé du pilote.</li>
              <li>Met à jour le paiement des salaires global de la compagnie.</li>
              <li>Envoie un mail au pilote avec le détail.</li>
            </ul>
          </li>
          <li>Envoie un mail récapitulatif à l'administrateur.</li>
        </ol>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les 1er du mois à 1h (tâche planifiée).</li>
            <li>Calcul et paiement des salaires.</li>
            <li>Mise à jour des finances et reporting par mail.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
