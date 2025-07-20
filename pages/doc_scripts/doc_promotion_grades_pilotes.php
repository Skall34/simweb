<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php';
// Récupérer tous les grades
$stmt = $pdo->query('SELECT nom, description, taux_horaire, niveau FROM GRADES ORDER BY niveau ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Promotion des pilotes</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script automatise la promotion des pilotes selon leurs heures de vol cumulées. Il met à jour le grade, envoie un mail de notification au pilote promu, logue chaque promotion et envoie un récapitulatif à l'administrateur.
        </p>
    </section>
    <section>
        <h2>Étapes du traitement</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">Calcul des heures de vol</h4>
                <ul>
                    <li>Pour chaque pilote, calcule le total d'heures de vol cumulées dans le mois précédent.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Détermination du grade</h4>
                <ul>
                    <li>Détermine le grade éligible selon les seuils d'heures définis.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Promotion et notifications</h4>
                <ul>
                    <li>Si le pilote est éligible à un grade supérieur, met à jour le grade en base.</li>
                    <li>Envoie un mail de notification au pilote promu.</li>
                    <li>Logue la promotion dans <code>scripts/logs/promotion_grades.log</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Récapitulatif administrateur</h4>
                <ul>
                    <li>Envoie un mail récapitulatif à l'administrateur avec la liste des promotions effectuées.</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Le script est lancé automatiquement chaque 1er du mois à 23h, mais peut aussi être lancé manuellement.</li>
            <li>En cas d'anomalie ou d'alerte, consulter le log <code>promotion_grades.log</code> pour diagnostic.</li>
        </ul>
    </section>
    <section>
        <h2>Table des grades pilotes</h2>
        <div class="compte-section" style="max-width:700px;margin:32px auto 0 auto;">
            <table style="width:100%;border-collapse:collapse;font-size:1.08rem;background:transparent;">
                <thead>
                    <tr style="background:#eaf2fb;">
                        <th style="padding:12px 10px;color:#2a4d7a;font-weight:600;border-bottom:1px solid #e0e6ed;">Grade</th>
                        <th style="padding:12px 10px;color:#2a4d7a;font-weight:600;border-bottom:1px solid #e0e6ed;">Taux horaire</th>
                        <th style="padding:12px 10px;color:#2a4d7a;font-weight:600;border-bottom:1px solid #e0e6ed;">Condition d'accès</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td style="padding:12px 10px;border-bottom:1px solid #e0e6ed;"><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td style="padding:12px 10px;border-bottom:1px solid #e0e6ed;"><?= htmlspecialchars($grade['taux_horaire']) ?>&nbsp;€</td>
                        <td style="padding:12px 10px;border-bottom:1px solid #e0e6ed;"><?= htmlspecialchars($grade['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-20 23:00:01 [PROMOTION] Début du script de promotion automatique
2025-07-20 23:00:01 Promotion: SKY001 (Jean Dupont) promu au grade Commandant (heures: 412.00)
2025-07-20 23:00:01 Mail de promotion envoyé à jean.dupont@skywings.fr
2025-07-20 23:00:01 Mail récapitulatif envoyé à admin@skywings.fr
2025-07-20 23:00:01 [PROMOTION] Fin du script de promotion automatique
        </pre>
    </section>
   
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
