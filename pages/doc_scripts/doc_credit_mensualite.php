<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : credit_mensualite</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère le paiement mensuel des crédits liés à l'achat d'appareils ou d'autres investissements. Il met à jour les finances.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les 1er du mois à 2h</li>
            <li>Calcul et paiement des mensualités de crédit.</li>
            <li>Mise à jour des finances de la compagnie.</li>
        </ul>
    </section>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
