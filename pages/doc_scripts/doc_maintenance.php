<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : maintenance</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère la maintenance automatique de la flotte. Il planifie, exécute et logue les opérations de maintenance, met à jour les statuts des appareils.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne toutes les nuits à 2h</li>
            <li>Planification et exécution de la maintenance des appareils.</li>
            <li>Mise à jour des statuts des appareils.</li>
        </ul>
    </section>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
