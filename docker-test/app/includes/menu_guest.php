<?php
/*
-------------------------------------------------------------
 Script : menu_guest.php
 Emplacement : includes/

 Description :
 Menu de navigation pour les visiteurs non connectés.
 Affiche les liens vers les pages publiques : accueil, à propos, contact, inscription.

 Utilisation :
 - À inclure après header.php pour les utilisateurs non authentifiés.
 - Utilise les clés de traduction pour l'internationalisation.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
?>
<nav class="menu-guest">
    <a href="/index.php"><?= t('menu_home') ?></a>
    <a href="/pages/about.php"><?= t('about_title') ?></a>
    <a href="/pages/contact.php"><?= t('contact_title') ?></a>
    <a href="/pages/register.php"><?= t('register_title') ?></a>
</nav>
