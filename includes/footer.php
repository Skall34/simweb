<?php
/*
-------------------------------------------------------------
 Script : footer.php
 Emplacement : includes/

 Description :
 Pied de page HTML de l'application affichant les informations de copyright.

 Utilisation :
 - À inclure en fin de page : require_once __DIR__ . '/includes/footer.php';
 - Utilise les clés de traduction pour l'internationalisation.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
?>
<footer>
    &copy; 2025 <?= t('footer_copyright') ?>. <?= t('footer_rights') ?>
</footer>
