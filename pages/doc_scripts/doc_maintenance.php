<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Maintenance automatique flotte</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script gère la maintenance automatique des appareils de la flotte : usure normale, sortie de maintenance, et maintenance après crash (3 jours). Toutes les opérations et erreurs sont loguées dans <code>scripts/logs/maintenance.log</code> et un mail récapitulatif est envoyé à l’administrateur.
        </p>
    </section>
    <section>
        <h2>Étapes du traitement</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">Sélection des appareils</h4>
                <ul>
                    <li>Sélectionne tous les appareils actifs dans la table <code>FLOTTE</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Maintenance usure normale</h4>
                <ul>
                    <li>Si <b>usure &lt; 30%</b> et <b>statut normal</b> (<code>status=0</code>), passage en maintenance : <code>status=1</code>, <code>etat=0</code>, <code>compteur_immo=1</code>, incrément <code>nb_maintenance</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Sortie de maintenance</h4>
                <ul>
                    <li>Si <b>en maintenance</b> (<code>status=1</code>) :</li>
                    <li>Si <code>compteur_immo=1</code> : sortie de maintenance après 1 jour, <code>etat=100</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                    <li>Si <code>compteur_immo&gt;1</code> : réinitialisation, <code>etat=1</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Maintenance après crash</h4>
                <ul>
                    <li>Si <b>crash</b> (<code>status=2</code>) :</li>
                    <li>Si <code>compteur_immo=0</code> : passage en maintenance crash (3 jours), <code>compteur_immo=1</code>, incrément <code>nb_maintenance</code>.</li>
                    <li>Si <code>compteur_immo=1</code> ou <code>2</code> : incrémentation du compteur.</li>
                    <li>Si <code>compteur_immo≥3</code> : sortie de maintenance crash, <code>etat=100</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Log et notifications</h4>
                <ul>
                    <li>Logue chaque étape et erreur dans <code>scripts/logs/maintenance.log</code>.</li>
                    <li>Envoie un mail récapitulatif à l’administrateur avec la liste des appareils entrés/sortis de maintenance.</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Ce script est lancé automatiquement chaque nuit (ex : 2h du matin).</li>
            <li>En cas d’anomalie, consulter le log <code>maintenance.log</code> pour diagnostic.</li>
            <li>Le mail récapitulatif peut être désactivé via la variable <code>$mailSummaryEnabled</code>.</li>
        </ul>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-20 02:00:01 --- Début maintenance ---
2025-07-20 02:00:01 Avion F-ABCD : état=28 / statut=0 / compteur_immo=0
2025-07-20 02:00:01 L'avion F-ABCD passe en maintenance (usure normale)
2025-07-20 02:00:01 Avion F-ESKY : état=100 / statut=1 / compteur_immo=1
2025-07-20 02:00:01 L'avion F-ESKY sort de maintenance après 1 jour (usure)
2025-07-20 02:00:01 --- Maintenance flotte ---
Appareils entrés en maintenance : 1
 - F-ABCD
Appareils sortis de maintenance : 1
 - F-ESKY
------------------------
2025-07-20 02:00:01 Mail récapitulatif envoyé à admin@skywings.com
        </pre>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
