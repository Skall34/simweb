<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Importer un vol ACARS</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script permet d’importer un vol ACARS dans la base de données via une requête POST (API REST). Il vérifie et formate les données reçues, rejette les vols invalides, met à jour le fret, la flotte, les finances, le carnet de vol, applique l’usure, et logue toutes les opérations.
        </p>
    </section>
    <section>
        <h2>Étapes du traitement</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">1. Contrôle de la requête</h4>
                <ul>
                    <li>Refuse toute méthode autre que POST.</li>
                    <li>Vérifie la présence et la validité des champs obligatoires dans <code>$_POST</code> : callsign, immatriculation, départ/arrivée, carburant, payload, note, mission…</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">2. Nettoyage et formatage</h4>
                <ul>
                    <li>Formate les dates/heures, majuscules sur les ICAO, conversion des valeurs numériques.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">3. Contrôles métier</h4>
                <ul>
                    <li>Vérifie l’existence du pilote et de l’avion (actif).</li>
                    <li>Vérifie la validité de la note (1 à 10).</li>
                    <li>Détecte les doublons de vol (même callsign, route, payload, etc.).</li>
                    <li>En cas d’erreur, logue et rejette le vol avec le motif.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">4. Insertion et traitements</h4>
                <ul>
                    <li>Insère le vol dans <code>FROM_ACARS</code> (marqué comme traité).</li>
                    <li>Déduit le fret au départ, ajoute à l’arrivée.</li>
                    <li>Calcule le coût du vol (voir <a href="doc_calcul_cout.php">calcul du revenu net</a>).</li>
                    <li>Ajoute le vol au carnet de vol général avec le coût calculé.</li>
                    <li>Met à jour la flotte (localisation, fuel, dernier utilisateur).</li>
                    <li>Met à jour les finances et la balance commerciale.</li>
                    <li>Ajoute la recette dans <code>finances_recettes</code>.</li>
                    <li>Applique l’usure à l’avion selon la note.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">5. Log et notifications</h4>
                <ul>
                    <li>Logue chaque étape et erreur dans <code>scripts/logs/importer_vol_direct.log</code>.</li>
                    <li>Envoie un mail récapitulatif à l’administrateur avec les infos du vol et le résultat.</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Ce script est appelé automatiquement lors de l’import d’un vol ACARS (POST API).</li>
            <li>En cas d’anomalie, consulter le log <code>importer_vol_direct.log</code> pour diagnostic.</li>
            <li>Le mail récapitulatif peut être désactivé via la variable <code>$mailSummaryEnabled</code>.</li>
        </ul>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-20 14:12:01 --- Démarrage import vol direct ---
2025-07-20 14:12:01 ✅ Vol traité avec succès (callsign: F-TEST)
2025-07-20 14:12:01 Ajout au carnet de vol : callsign=F-TEST, immat=F-ABCD, depart=LFPO, dest=LFML, payload=1200, cout_vol=16772
2025-07-20 14:12:01 Mise à jour flotte : immat=F-ABCD, fuel=500, callsign=F-TEST, localisation=LFML
2025-07-20 14:12:01 Mise à jour finances : immat=F-ABCD, cout_vol=16772
2025-07-20 14:12:01 Ajout recette dans finances_recettes : cout_vol=16772, vol_id=123
2025-07-20 14:12:01 Usure avion F-ABCD, note=8
2025-07-20 14:12:01 Mail récapitulatif envoyé à admin@skywings.com
        </pre>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
