<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : paiement_salaires_pilotes</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère le paiement mensuel des salaires des pilotes. Il calcule le salaire selon les heures de vol et le fret transporté, met à jour les finances.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les 1er du mois à 1h</li>
            <li>Calcul du salaire selon les heures de vol et le fret du mois précédent.</li>
            <li>Mise à jour des finances et des revenus des pilotes.</li>
            <li>Envoi de mails de notification.</li>
        </ul>
    </section>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
