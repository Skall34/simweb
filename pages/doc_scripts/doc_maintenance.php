<?php
// ...existing code...
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Maintenance</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script gère la maintenance automatique des appareils de la flotte. Il traite l'usure normale, la sortie de maintenance, et la maintenance après crash (3 jours). Toutes les opérations et erreurs sont loguées dans <b>maintenance.log</b> et un mail récapitulatif est envoyé à l'administrateur.</p>
        <h3 style="margin-top:18px;">Fonctionnement détaillé :</h3>
        <ol style="margin-left:18px;">
          <li>Sélectionne tous les appareils actifs dans <b>FLOTTE</b>.</li>
          <li>Pour chaque appareil :
            <ul style="font-size:0.95em;margin-top:4px;padding-left:28px;">
              <li>Si usure &lt; 30% et statut normal, passage en maintenance (usure normale).</li>
              <li>Si en maintenance, sortie ou réinitialisation selon le compteur.</li>
              <li>Si crash, passage en maintenance crash et gestion du compteur sur 3 jours.</li>
            </ul>
          </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne toutes les nuits à 2h</li>
            <li>Planification et exécution de la maintenance des appareils.</li>
            <li>Mise à jour des statuts des appareils.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
