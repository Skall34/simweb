<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Mise à jour hebdomadaire du frêt disponible</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script met à jour le fret disponible dans les aéroports. Il attribue chaque semaine une quantité aléatoire de fret (entre 1 et 100 kg) à chaque aéroport, permettant de renouveler le stock et d'assurer la disponibilité pour les missions.</p>
        <h3 style="margin-top:18px;">Fonctionnement détaillé :</h3>
        <ol style="margin-left:18px;">
          <li>Sélectionne tous les aéroports dans la base.</li>
          <li>Pour chaque aéroport :
            <ul style="font-size:0.95em;margin-top:4px;padding-left:28px;">
              <li>Attribue une quantité de fret aléatoire (entre 1 et 100 kg).</li>
              <li>Met à jour le stock de fret de l'aéroport.</li>
            </ul>
          </li>
          <li>Envoie un mail récapitulatif à l'administrateur à la fin du script.</li>
        </ol>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Tourne tous les vendredis à 4h (tâche planifiée).</li>
            <li>Renouvellement automatique du fret disponible dans les aéroports.</li>
            <li>Log exhaustif et reporting par mail à l'administrateur.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
