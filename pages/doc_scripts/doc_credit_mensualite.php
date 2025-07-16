<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Calcul les mensualités de crédits que la compagnie doit rembourser</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script calcule et applique chaque mois les mensualités des appareils achetés à crédit par la compagnie.
        Il met à jour les champs financiers de chaque appareil concerné dans la table FINANCES.</p>
    </section>
    <section>
        <h2>Fonctionnement :</h2>
        <ol style="margin-left:18px;">
          <li>Sélectionne tous les appareils à crédit <span style="font-size:0.95em;">(nb_annees_credit &gt; 0 et reste_a_payer &gt; 0)</span>.</li>
          <li>Pour chaque appareil :
            <ul style="font-size:0.95em;margin-top:4px;padding-left:28px;">
              <li>Décrémente le nombre d'années de crédit en janvier.</li>
              <li>Calcule la mensualité selon le taux et la durée restante.</li>
              <li>Met à jour les champs <b>traite_payee_cumulee</b>, <b>reste_a_payer</b> et <b>remboursement</b>.</li>
            </ul>
          </li>
          <li>Logue le nombre d'appareils mis à jour et toute anomalie détectée.</li>
          <li>Envoie un mail récapitulatif automatique à la fin du script.</li>
        </ol>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les 1er du mois à 2h</li>
            <li>Calcul et paiement des mensualités de crédit.</li>
            <li>Mise à jour des finances de la compagnie.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
