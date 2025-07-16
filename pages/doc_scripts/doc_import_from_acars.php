<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : import_from_acars.php</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère l'importation automatique des vols depuis le logiciel ACARS. Il vérifie, insère ou rejette les vols, met à jour les carnets de vol et logue chaque opération.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Importation automatique des vols depuis ACARS.</li>
            <li>Vérification et insertion dans la base de données.</li>
            <li>Gestion des rejets et notifications par mail.</li>
            <li>Log des opérations dans le fichier dédié.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
