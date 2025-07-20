<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/menu_logged.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
    <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;">Script : Paiement mensuel des salaires des pilotes</h1>
    <section>
        <h2>Objectif</h2>
        <p>
            Ce script automatise le paiement mensuel des salaires des pilotes de la compagnie. Il garantit un calcul juste, une traçabilité complète et une centralisation des flux financiers liés à la masse salariale.
        </p>
    </section>
    <section>
        <h2>Principe de calcul</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">Heures de vol</h4>
                <ul>
                    <li>Le nombre d'heures de vol du mois précédent est calculé pour chaque pilote.</li>
                    <li>Le taux horaire dépend du grade du pilote (<code>GRADES.taux_horaire</code>).</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Bonus fret</h4>
                <ul>
                    <li>Un bonus de <strong>2&nbsp;€/kg</strong> de fret transporté (champ <code>payload</code> dans <code>CARNET_DE_VOL_GENERAL</code>).</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">3. Versement et enregistrement</h4>
                <ul>
                    <li>Le montant total (heures + bonus fret) est versé au pilote et enregistré dans <code>SALAIRES</code>.</li>
                    <li>Le revenu cumulé du pilote (<code>PILOTES.revenus</code>) est mis à jour.</li>
                    <li>La dépense globale est enregistrée dans <code>finances_depenses</code> (une seule ligne pour l'ensemble des salaires du mois).</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Déroulement du script</h2>
        <ol>
            <li>
                <h4 class="sous-chapitre">Sélection des pilotes</h4>
                <ul>
                    <li>Sélectionne tous les pilotes actifs dans <code>PILOTES</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Calcul du salaire</h4>
                <ul>
                    <li>Calcule les heures de vol du mois précédent.</li>
                    <li>Récupère le taux horaire selon le grade.</li>
                    <li>Calcule le bonus fret (2&nbsp;€/kg transporté).</li>
                    <li>Calcule le montant total du salaire.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Paiement et notifications</h4>
                <ul>
                    <li>Insère le salaire dans <code>SALAIRES</code>.</li>
                    <li>Met à jour le revenu cumulé du pilote.</li>
                    <li>Envoie un mail individuel au pilote avec le détail du calcul.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre">Dépense globale et log</h4>
                <ul>
                    <li>Enregistre la dépense globale dans <code>finances_depenses</code> (type <code>salaire</code>, commentaire récapitulatif).</li>
                    <li>Envoie un mail récapitulatif à l'administrateur (<code>ADMIN_EMAIL</code>) avec le détail des salaires versés et la somme totale.</li>
                    <li>Logue chaque étape dans <code>scripts/logs/paiement_salaires.log</code> (démarrage, calculs, insertions, mails, anomalies éventuelles).</li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2>Automatisation &amp; utilisation</h2>
        <ul>
            <li>Le script est prévu pour être lancé automatiquement chaque mois (ex&nbsp;: cron le 1er à 1h du matin), mais peut aussi être lancé manuellement.</li>
            <li>Le mode test permet d'envoyer tous les mails aux pilotes à l'administrateur uniquement (variable <code>$test_mode</code>).</li>
            <li>En cas d'anomalie ou d'alerte, consulter le log <code>paiement_salaires.log</code> pour diagnostic.</li>
        </ul>
    </section>
    <section>
        <h2>Exemple de log</h2>
        <pre style="background:#f7f7fa;padding:12px;border-radius:6px;font-size:0.98em;overflow-x:auto;">
2025-07-20 01:00:01 [SALAIRE] Début du script de paiement des salaires
2025-07-20 01:00:01 [TRACE] Nombre de pilotes trouvés : 9 | Callsigns : SKY001, SKY002, ...
2025-07-20 01:00:01 [TRACE] Pilote : SKY001
2025-07-20 01:00:01 [TRACE] Taux horaire : 45
2025-07-20 01:00:01 [TRACE] Total secondes vol : 10800 | Heures mois : 3
2025-07-20 01:00:01 [TRACE] Total fret (kg) : 120 | Bonus fret : 240
2025-07-20 01:00:01 [TRACE] Montant calculé : 375
2025-07-20 01:00:01 [TRACE] Insertion salaire OK
2025-07-20 01:00:01 [TRACE] Update revenus pilote OK
2025-07-20 01:00:01 [TRACE] Salaire: SKY001 (Jean Dupont) - Heures: 3.00 - Fret: 120.00kg - Bonus fret: 240.00€ - Montant: 375.00€
2025-07-20 01:00:01 [TRACE] Mail de salaire envoyé à jean.dupont@skywings.fr
2025-07-20 01:00:01 [TRACE] Fin traitement pilote : SKY001
2025-07-20 01:00:01 [TRACE] Dépense globale enregistrée dans finances_depenses : 1181897.42 € pour 9 pilotes
2025-07-20 01:00:01 [TRACE] Mail récapitulatif des salaires envoyé à admin@skywings.fr
2025-07-20 01:00:01 [SALAIRE] Fin du script de paiement des salaires
        </pre>
    </section>
    <div style="text-align:center; margin-top:38px;">
        <a href="/pages/documentation.php" class="btn" style="min-width:180px;text-decoration:none;">← Retour à la documentation</a>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
