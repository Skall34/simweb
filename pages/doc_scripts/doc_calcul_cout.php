<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Calcul du revenu net d'un vol</h1>
    <section>
        <h2>Calcul détaillé du revenu net d'un vol</h2>
        <p>Le calcul du revenu net d'un vol prend en compte de nombreux paramètres métier pour refléter la réalité économique de la compagnie. Voici la logique complète utilisée par le script&nbsp;:</p>
        <h3>1. Paramètres pris en compte</h3>
        <ul>
            <li><b>Fret transporté (payload)</b> : quantité de fret en kg.</li>
            <li><b>Durée du vol</b> : au format HH:MM:SS, convertie en heures décimales.</li>
            <li><b>Mission</b> : chaque mission peut avoir un coefficient de majoration (bonus/malus sur les recettes).</li>
            <li><b>Consommation de carburant</b> : en litres.</li>
            <li><b>Note du vol</b> : de 1 (crash) à 10 (vol parfait), influe fortement sur le coût d’utilisation de l’appareil.</li>
            <li><b>Coût horaire de l’appareil</b> : dépend du type d’appareil, récupéré via la flotte.</li>
        </ul>
        <h3>2. Étapes du calcul</h3>
        <ol>
            <li>
                <h4 class="sous-chapitre">Coefficient de note</h4>
                <ul>
                    <li>Crash ou incident grave (note 1 ou 2)&nbsp;: coût d’utilisation multiplié par 50 ou 100.</li>
                    <li>Note moyenne (3 à 6)&nbsp;: coût d’utilisation majoré modérément.</li>
                    <li>Bonne note (8 à 10)&nbsp;: coût d’utilisation réduit (jusqu’à 0,5x).</li>
                    <li>Objectif&nbsp;: inciter à voler proprement.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Majoration de mission</h4>
                <ul>
                    <li>Certaines missions (ponctuelles, spéciales) bénéficient d’un coefficient multiplicateur sur les recettes.</li>
                    <li>Si aucune majoration n’est définie, le coefficient est 1.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Coût horaire de l’appareil</h4>
                <ul>
                    <li>Récupéré selon l’immatriculation et le type d’appareil.</li>
                    <li>Permet de refléter le coût réel d’exploitation.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Calculs intermédiaires</h4>
                <ul>
                    <li><b>Revenu brut</b> = fret (kg) × 5 € × heures × majoration mission</li>
                    <li><b>Coût carburant</b> = carburant (L) × 0,88 €</li>
                    <li><b>Coût appareil</b> = coût horaire × heures × coefficient de note</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Revenu net</h4>
                <ul>
                    <li><b>Formule finale</b> :<br>
                        <code>Revenu net = Revenu brut – (Coût carburant + Coût appareil)</code>
                    </li>
                    <li>Toutes les étapes sont loguées pour audit.</li>
                </ul>
            </li>
        </ol>
        <h3>3. Exemple de calcul</h3>
        <ul>
            <li>Fret : 1200 kg</li>
            <li>Durée : 2h30 (2,5h)</li>
            <li>Mission : “OP FRANCE” (majoration 1,2)</li>
            <li>Carburant : 600 L</li>
            <li>Note : 8 (coef note : 0,8)</li>
            <li>Coût horaire appareil : 350 €</li>
        </ul>
        <ul style="font-size:0.97em;">
            <li>Revenu brut = 1200 × 5 × 2,5 × 1,2 = 18 000 €</li>
            <li>Coût carburant = 600 × 0,88 = 528 €</li>
            <li>Coût appareil = 350 × 2,5 × 0,8 = 700 €</li>
            <li>Revenu net = 18 000 – (528 + 700) = 16 772 €</li>
        </ul>
        <h3>4. Fonctions principales du script</h3>
        <ul>
            <li><code>coef_note($note)</code>.</li>
            <li><code>getMajorationMission($mission_libelle)</code> : récupère la majoration de la mission.</li>
            <li><code>getCoutHoraire($immat)</code> : récupère le coût horaire de l’appareil.</li>
            <li><code>calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire)</code> : effectue tous les calculs ci-dessus.</li>
        </ul>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
