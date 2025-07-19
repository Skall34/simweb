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
        <h2>Fonction métier</h2>
        <p>Ce script automatise la promotion des pilotes selon leurs heures de vol cumulées. Il met à jour le grade, envoie un mail de notification au pilote promu, logue chaque promotion et envoie un récapitulatif à l'administrateur.</p>

         <section>
            <h2>Automatisation</h2>
            <ul>
                <li>Tourne tous les 1er du mois à 23h.</li>
                <li>Calcul des heures de vol pour chaque pilote.</li>
                <li>Détermination du grade éligible et mise à jour en base.</li>
                <li>Envoi d'un mail de notification au pilote promu.</li>
                <li>Envoi d'un mail récapitulatif à l'administrateur.</li>
            </ul>
        </section>

        <h3 style="margin-top:32px;">Table des grades pilotes</h3>
        <div class="grades-table">
            <table>
                <thead>
                    <tr>
                        <th>Grade</th>
                        <th>Taux horaire</th>
                        <th>Condition d'accès</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($grade['taux_horaire']) ?>&nbsp;€</td>
                        <td><?= htmlspecialchars($grade['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
        .grades-table {
            margin: 32px auto;
            max-width: 700px;
            background: #f8fbff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 24px;
        }
        .grades-table table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.08rem;
        }
        .grades-table th, .grades-table td {
            border-bottom: 1px solid #e0e6ed;
            padding: 12px 10px;
            text-align: left;
        }
        .grades-table th {
            background: #eaf2fb;
            color: #2a4d7a;
            font-weight: 600;
        }
        .grades-table tr:last-child td {
            border-bottom: none;
        }
        </style>
    </section>
   
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
