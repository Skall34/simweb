<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Récupérer tous les grades
$stmt = $pdo->query('SELECT nom, description, niveau FROM GRADES ORDER BY niveau ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir les conditions d'accès pour chaque grade
$conditions = [
    1 => 'Moins de 100 heures de vol',
    2 => '100 à 199 heures de vol',
    3 => '200 à 299 heures de vol',
    4 => '300 à 399 heures de vol',
    5 => '400 heures de vol et plus'
];
?>
<main>
    <h2>Les grades pilotes</h2>
    <div class="grades-table">
        <table>
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Description</th>
                    <th>Condition d'accès</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($grade['description']) ?></td>
                        <td><?= $conditions[$grade['niveau']] ?? '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
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
<?php
include __DIR__ . '/../includes/footer.php';
?>
