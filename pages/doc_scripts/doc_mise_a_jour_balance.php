<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : mise_a_jour_balance.php</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script met à jour la balance commerciale de la compagnie. Il agrège les recettes, dépenses et autres flux financiers, puis logue chaque opération.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Calcul et mise à jour de la balance commerciale.</li>
            <li>Log des opérations dans le fichier dédié.</li>
        </ul>
    </section>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
