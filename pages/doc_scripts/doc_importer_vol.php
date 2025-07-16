<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : importer_vol</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script permet d'importer manuellement ou automatiquement des vols dans la base. Il vérifie les données, insère le vol, met à jour les statistiques.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne toutes les heures</li>
            <li>Importation manuelle ou automatique des vols.</li>
            <li>Vérification et insertion dans la base de données.</li>
            <li>Mise à jour des statistiques de vol.</li>
        </ul>
    </section>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
