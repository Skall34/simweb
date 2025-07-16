<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php include_once("../includes/header.php"); ?>
    <?php include_once("../includes/menu_logged.php"); ?>
    <div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Documentation du site Skywings</h1>
        <section>
            <h2>Fonctionnement général</h2>
            <p>Le site Skywings gère la vie d'une compagnie virtuelle de pilotes, avec automatisation des promotions, gestion des salaires, statistiques, et finances.</p>
            <p>Il fonctionne avec l'acars que vous pouvez télécharger ici :
                <a href="../assets/acars/acars.zip" download style="color:#1a3552;font-weight:bold;text-decoration:underline;">Télécharger ACARS</a>
            </p>
            <p>Et son mode d'emploi que vous pouvez télécharger ici :
                <a href="../assets/acars/DocumentationUtilisateurSimAddon.pdf" download style="color:#1a3552;font-weight:bold;text-decoration:underline;">Télécharger documentation acars</a>
            </p>
        </section>
        <section>
            <h2>Traitements automatiques</h2>
            <ul>
                <li><strong>Promotion des grades :</strong> Script nocturne qui promeut les pilotes selon leurs heures de vol, envoie un mail et logue chaque promotion.</li>
                <li><strong>Paiement des salaires :</strong> Script mensuel qui calcule le salaire de chaque pilote selon ses heures de vol et le fret transporté (2€/kg), met à jour les finances, envoie un mail au pilote et un récapitulatif à l'administrateur.</li>
            </ul>
        </section>
        <section>
            <h2>Documentation métier des scripts</h2>
            <ul>
                <li><a href="doc_scripts/doc_assurance_mensuelle.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Assurance mensuelle</a></li>
                <li><a href="doc_scripts/doc_calcul_cout.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Calcul du cout d'un vol</a></li>
                <li><a href="doc_scripts/doc_credit_mensualite.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Calcul des mensualités de crédits</a></li>
                <li><a href="doc_scripts/doc_importer_vol.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Importer vol</a></li>
                <li><a href="doc_scripts/doc_maintenance.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Maintenance appareils</a></li>
                <li><a href="doc_scripts/doc_paiement_salaires_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Paiement du salaires des pilotes</a></li>
                <li><a href="doc_scripts/doc_update_fret.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Update Frêt</a></li>
                <li><a href="doc_scripts/doc_promotion_grades_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;">Promotions grades des pilotes</a></li>
            </ul>
        </section>
    </div>
</body>
</html>
