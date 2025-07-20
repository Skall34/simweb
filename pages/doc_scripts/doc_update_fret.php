<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Mise à jour hebdomadaire du frêt disponible</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script met à jour chaque semaine le fret disponible dans tous les aéroports. Il ajoute une valeur aléatoire (entre 1 et 100 kg) au fret de chaque aéroport, simule l'arrivée de fret et assure la disponibilité pour les missions.
        </p>
    </section>
    <section>
        <h2>Étapes du traitement</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">Sélection des aéroports</h4>
                <ul>
                    <li>Sélectionne tous les aéroports et leur fret actuel dans la table <code>AEROPORTS</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Mise à jour du fret</h4>
                <ul>
                    <li>Pour chaque aéroport, ajoute une valeur aléatoire (entre 1 et 100 kg) au fret existant.</li>
                    <li>Met à jour la base de données avec la nouvelle valeur de fret.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Log et notifications</h4>
                <ul>
                    <li>Logue chaque mise à jour et erreur dans <code>scripts/logs/update_fret.log</code>.</li>
                    <li>Envoie un mail récapitulatif à l’administrateur à la fin du script (nombre d’aéroports mis à jour, cohérence, bornes utilisées).</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Le script est lancé automatiquement chaque vendredi à 4h (cron), mais peut aussi être lancé manuellement.</li>
            <li>En cas d’anomalie ou d’alerte, consulter le log <code>update_fret.log</code> pour diagnostic.</li>
        </ul>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-18 04:00:01 Traitement hebdomadaire terminé : 87 aéroports mis à jour (attendu : 87) [COHERENT]
2025-07-18 04:00:01 Mail récapitulatif envoyé à admin@skywings.fr
        </pre>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
