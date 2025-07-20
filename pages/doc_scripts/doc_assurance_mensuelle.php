<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Paiement de l'assurance mensuelle</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script automatise le prélèvement mensuel de l'assurance pour la compagnie aérienne virtuelle. Il vise à garantir que l'assurance soit toujours prélevée de façon cohérente et traçable, en fonction de la santé financière réelle de la compagnie.
        </p>
    </section>
    <section>
        <h2>Principe de calcul</h2>
        <ul>
            <li><b>Assiette :</b> <strong>0,2% de la valeur absolue de la balance commerciale actuelle</strong> (champ <code>balance_actuelle</code> dans la table <code>BALANCE_COMMERCIALE</code>).</li>
            <li>Le calcul s'applique même si la balance est négative (déficit).</li>
            <li>Le montant prélevé est enregistré comme dépense dans <code>finances_depenses</code> avec un commentaire explicite.</li>
        </ul>
    </section>
    <section>
        <h2>Déroulement du script</h2>
        <ol>
            <li>Récupère la valeur de <code>balance_actuelle</code> dans <code>BALANCE_COMMERCIALE</code>.</li>
            <li>Calcule l'assurance mensuelle : <code>assurance = abs(balance_actuelle) × 0,002</code>.</li>
            <li>Insère la dépense dans <code>finances_depenses</code> (type <code>assurance</code>, commentaire détaillé).</li>
            <li>Met à jour la balance commerciale via recalcul automatique.</li>
            <li>Logue toutes les opérations dans <code>scripts/logs/assurance_mensuelle.log</code> (démarrage, calcul, insertion, fin, anomalies éventuelles).</li>
            <li>Envoie un mail récapitulatif automatique à l'administrateur (<code>ADMIN_EMAIL</code>), avec le montant prélevé, la base de calcul, la balance avant/après, et toute alerte éventuelle.</li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Le script est prévu pour être lancé automatiquement chaque mois (ex : cron le 1er à 3h du matin), mais peut aussi être lancé manuellement.</li>
            <li>Le pourcentage peut être adapté si besoin (variable <code>$pourcentage</code> dans le script).</li>
            <li>En cas d'anomalie ou d'alerte, consulter le log <code>assurance_mensuelle.log</code> pour diagnostic.</li>
        </ul>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-20 03:00:01 --- Démarrage du script d'assurance mensuelle ---
2025-07-20 03:00:01 Balance actuelle (balance_actuelle): -4922283538.42
2025-07-20 03:00:01 Assurance mensuelle enregistrée dans finances_depenses: 9844567.08 | Prélèvement assurance mensuelle (0.2% de la valeur absolue de la balance actuelle : 4922283538.42 €)
2025-07-20 03:00:01 Traitement terminé.
        </pre>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
