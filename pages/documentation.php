<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
    <div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Documentation du site Skywings</h1>
        <section>
            <h2>Fonctionnement général</h2>
            <p>Le site Skywings gère la vie d'une compagnie virtuelle de pilotes, avec automatisation des promotions, gestion des salaires, statistiques, et finances.</p>
            <p>Le système est valable sur tous les simulateurs de vols, en ayant pris soin d'installer FSUIPC (MSFS2020, MSFS2024, P3D) ou XUIPC (xPlane).</p>
            <p>Il fonctionne avec l'acars que vous pouvez télécharger ici :
                <a href="../assets/acars/acars.zip" download style="color:#1a3552;font-weight:bold;text-decoration:underline;">Télécharger ACARS</a>
            </p>
            <p>Et son mode d'emploi que vous pouvez télécharger ici :
                <a href="../assets/acars/DocumentationUtilisateurSimAddon.pdf" download style="color:#1a3552;font-weight:bold;text-decoration:underline;">Télécharger documentation acars</a>
            </p>
        </section>
        <BR>
        <section>
            <h2>Les missions</h2>
            <ul>
                <li><strong>Nous opérons différents types de missions:</strong>
                    <ul>
                        <li><strong>Missions ponctuelles :</strong> Un voyage défini à l'avance. Souvent une étape par semaine. (HYDRAVIONS, ESQUIMOS, etc.)</li>
                        <li><strong>Missions permanentes :</strong> OP FRANCE (transport de frêt d'un aéroport français à un autre) / OP LINER (transport de frêt depuis ou vers un aéroport hors France).</li>
                        Les missions bénéficient d'un coeficient multiplicateur pour le calcul des recettes des vols associés
                        <li><strong>Vols libre :</strong> Aucunes restrictions. Pas de coéficient appliqué.</li>
                    </ul>
            </ul>
        </section>
        <section>
        <?php
        // Connexion à la base
        require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php';
        try {
            $stmt = $pdo->query("SELECT libelle, majoration_mission, Active FROM MISSIONS ORDER BY libelle");
            $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $missions = [];
            echo '<p style="color:red;">Erreur lors de la récupération des missions : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        if (!empty($missions)) {
            echo '<h3 style="margin-top:30px;">Détail des missions</h3>';
            echo '<table style="max-width:480px;font-size:0.92em;border-collapse:collapse;margin-bottom:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">';
            echo '<tr style="background:#e6f0fa;color:#1a3552;font-weight:bold;">';
            echo '<th style="padding:4px 8px;">Libellé</th><th style="padding:4px 8px;">Majoration</th><th style="padding:4px 8px;">Active</th>';
            echo '</tr>';
            foreach ($missions as $m) {
                echo '<tr style="background:#fff;">';
                echo '<td style="padding:3px 8px;">' . htmlspecialchars($m['libelle']) . '</td>';
                $maj = $m['majoration_mission'];
                if (is_numeric($maj)) {
                    $maj = rtrim(rtrim(number_format($maj, 2, '.', ''), '0'), '.');
                }
                echo '<td style="padding:3px 8px;text-align:center;">' . htmlspecialchars($maj) . '</td>';
                if (isset($m['Active']) && (int)$m['Active'] === 1) {
                    echo '<td style="padding:3px 8px;text-align:center;">Oui</td>';
                } else {
                    echo '<td style="padding:3px 8px;text-align:center;color:#c0392b;font-weight:bold;">Non</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
        </section>
        <BR>
        <section>
            <h2>Traitements automatiques</h2>
            <ul>
                <li><strong>Paiement des assurances :</strong> Script mensuel qui calcule la prime d'assurance que Skywings doit payer pour les avions détenus. Déduit de la balance commerciale le 1er de chaque mois</li>
                <li><strong>Calcul de la recette d'un vol :</strong> Lors de chaque import d'un vol, le temps de vol, le frêt transporté, l'appareil utilisé, la note du vol sont autant de paramètres qui sont pris en compte. Pour plus de détails, consulter le lien ci-dessous.</li>
                <li><strong>Paiement des mensualités des crédits :</strong> Certains appareils sont achetés à crédit. Le paiement des mensualité se fait une fois / mois.</li>
                <li><strong>Maintenance des appareils :</strong> Script quotidien, qui en fonction de létat de chaque appareil, le fait passer en maintenance. Pour une usure normale, l'appareil passe en maintenance pour 24h lorsqu'il atteind 30% d'usure. Lors d'un crash (note de 1), l'appareil est immobilisé 3 jours</li>
                <li><strong>Paiement des salaires :</strong> Script mensuel qui calcule le salaire de chaque pilote selon ses heures de vol, son grade et le fret transporté (2€/kg), met à jour les finances, envoie un mail au pilote et un récapitulatif à l'administrateur.</li>
                <li><strong>Promotion des grades :</strong> Script qui promeut les pilotes selon leurs heures de vol, envoie un mail et logue chaque promotion. Est executé tous les 1er du mois.</li>
                <li><strong>Mise à jour du frêt :</strong> Une fois par semaine, le vendredi, une certaine quantité de frêt aléatoire (entre 1 et 100 Kg) est attribué aux aéroports.</li>
            </ul>
        </section>
        <BR>
        <section>
            <h2>Documentation détaillée des scripts</h2>
            <ul>
                <li><a href="doc_scripts/doc_assurance_mensuelle.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Assurance mensuelle</a></li>
                <li><a href="doc_scripts/doc_calcul_cout.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Calcul de la recette d'un vol</a></li>
                <li><a href="doc_scripts/doc_credit_mensualite.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Calcul des mensualités de crédits</a></li>
                <li><a href="doc_scripts/doc_importer_vol.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Importer vol</a></li>
                <li><a href="doc_scripts/doc_maintenance.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Maintenance appareils</a></li>
                <li><a href="doc_scripts/doc_paiement_salaires_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Paiement du salaires des pilotes</a></li>
                <li><a href="doc_scripts/doc_promotion_grades_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Promotions grades des pilotes</a></li>
                <li><a href="doc_scripts/doc_update_fret.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Update Fret</a></li>
            </ul>
        </section>
    </div>
<!-- Décalage des puces blanches déplacé dans css/styles.css -->
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
