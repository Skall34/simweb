# Mémoire permanente GitHub Copilot — SimWeb

Ce fichier est un miroir de ce que GitHub Copilot conserve en mémoire persistante entre les conversations.
Dernière mise à jour : 30 mars 2026.

## Règles de workflow

- **Toujours vérifier l'impact sur la documentation** après chaque modification de code/logique. Inclure les mises à jour doc (`pages/doc_scripts/*`, lang keys `doc_*`) dans le scope de chaque changement sans qu'on ait besoin de le demander.
- **Documentation trilingue** : FR/EN/ES (`lang/fr.php`, `lang/en.php`, `lang/es.php`)
- **Projet SimWeb** : compagnie aérienne virtuelle, PHP/MySQL
- **Pas de CSS inline** : utiliser `css/styles.css` pour tous les styles. Ne pas mettre de `style="..."` dans le HTML sauf exception justifiée. Quand on crée ou modifie une page, ajouter les classes CSS dans la feuille de style externe.
- **Synchroniser ce fichier** à chaque ajout/modification de mémoire permanente.
