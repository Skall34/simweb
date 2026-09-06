<?php

function afficherCoefficientMission() {
    global $pdo;

    $nomFichier = pathinfo(basename($_SERVER['SCRIPT_FILENAME'] ?? ''), PATHINFO_FILENAME);
    $libelleMission = str_replace('+', ' ', $nomFichier);
    if ($libelleMission === '') {
        return;
    }

    try {
        $stmt = $pdo->prepare('SELECT majoration_mission FROM MISSIONS WHERE UPPER(libelle) = UPPER(:libelle) LIMIT 1');
        $stmt->execute(['libelle' => $libelleMission]);
        $coefficient = $stmt->fetchColumn();
    } catch (PDOException $e) {
        return;
    }

    if ($coefficient === false) {
        return;
    }

    $coefficientFormate = number_format((float)$coefficient, 2, ',', ' ');
    ?>
    <p class="mission-revenue-coefficient"><?= htmlspecialchars(t('mission_revenue_coefficient', ['coefficient' => $coefficientFormate]), ENT_QUOTES, 'UTF-8') ?></p>
    <?php
}