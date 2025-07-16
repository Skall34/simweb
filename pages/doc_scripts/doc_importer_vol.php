<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Importer un vol qui vient d'être soumis</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Ce script traite les vols ACARS non encore importés dans la base. Il vérifie et formate les données, rejette les vols invalides, met à jour le fret, la flotte, les finances, le carnet de vol, et applique l'usure. Il marque chaque vol comme traité et met à jour la balance commerciale si besoin.</p>
        <h3 style="margin-top:18px;">Fonctionnement détaillé :</h3>
        <ol style="margin-left:18px;">
          <li>Sélectionne tous les vols non traités dans la base de données.</li>
          <li>Pour chaque vol :
            <ul style="font-size:0.95em;margin-top:4px;padding-left:28px;">
              <li>Vérifie la validité des données et rejette si besoin (avec log et motif).</li>
              <li>Détecte les doublons et rejette le vol si doublon.</li>
              <li>Met à jour le fret (départ/destination), la flotte, les finances, le carnet de vol, et applique l'usure sur les appareils, en fonction de la note du vol.</li>
              <li>Marque le vol comme traité.</li>
            </ul>
          </li>
          <li>Met à jour la balance commerciale si au moins un vol importé.</li>
        </ol>
    </section>
    <section>
        <h2>Automatisation</h2>
        <ul>
            <li>Il est executé toutes les heures. Ne concerne que les vols en saisie manuelle. Les vols provenant de l'acars sont importés directement.</li>
            <li>Vérification et formatage des données, détection des doublons.</li>
            <li>Mise à jour du fret, de la flotte, des finances, du carnet de vol, et de la balance commerciale.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
