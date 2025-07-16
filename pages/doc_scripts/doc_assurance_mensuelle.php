<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Paiement de l'assurance</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère le paiement mensuel des assurances. Le calcul se base sur 2% de la valeur de notre flotte. Il calcule le coût d'assurance, met à jour les finances.</p>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les 1er du mois à 3h</li>
            <li>Calcul du coût d'assurance.</li>
            <li>Mise à jour des finances de la compagnie.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
