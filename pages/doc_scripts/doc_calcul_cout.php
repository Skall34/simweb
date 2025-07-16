<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Calcul du revenu net d'un vol</h1>
    <section>
        <h2>Fonction métier</h2>
        <p>Lors de l'importation d'un vol, de nombreux paramètres sont pris en compte pour le calcul des coûts de vol.
         Ce script calcule le coût d'exploitation pour un vol donné.</p><br>
         <p>Voici les opérations effectuées par le script :</p>
        <ul>
            <li>Récupération des paramètres du vol (fret, durée, etc.).</li>
            <li>Recherche du coût horaire de l'appareil en fonction de son fleet_type, et application sur la durée du vol.</li>
            <li>Application des coefficients de majoration en fonction de la note du vol. Permet d'inciter les pilotes à voler proprement et à éviter les incidents.</li>
                <ul style="font-size:0.92em;margin-top:4px;padding-left:28px;">
                  <li>Une mauvaise note (1 ou 2) majore fortement le coût d'utilisation (pénalité crash ou incident grave).</li>
                  <li>Une note moyenne majore modérément le coût.</li>
                  <li>Une bonne note réduit le coût d'utilisation.</li>
                </ul>
                
            <li>Application des coefficients de majoration en fonction de la mission.</li>
                

            <li>Calcul du revenu net du vol. </li>
            <ul style="font-size:0.92em;margin-top:4px;padding-left:28px;">
              <li>Le transport de frêt est payé à <b>5 € / Kg</b></li>
              <li>Nous payons <b>0.88 €</b> le litre de carburant</li>
              <li><b>Revenu brut</b> = Payload * 5 * heures * majoration_mission</li>
              <li><b>Coût en carburant</b> = carburant * 0.88</li>
              <li><b>Coût appareil</b> = cout_horaire * heures * coef note appliqué</li>
              <li><b>Revenu net</b> = Revenu brut - (Coût en carburant + Coût appareil)</li>
            </ul>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
