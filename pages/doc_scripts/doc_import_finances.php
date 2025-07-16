<?php
// ...existing code...
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : import_finances.php</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script permet d'importer des données financières dans le système. Il vérifie, insère et met à jour les finances de la compagnie selon les fichiers ou sources importées.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Importation de données financières.</li>
            <li>Vérification et mise à jour des finances.</li>
            <li>Log des opérations dans le fichier dédié.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
