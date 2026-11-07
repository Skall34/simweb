<?php
/**
 * Générateur de fiche de paie PDF pour les pilotes
 * Utilise FPDF pour créer un document professionnel
 */

require_once __DIR__ . '/../assets/fpdf.php';

class FichePaie extends FPDF {
    private $companyName;
    private $companyAddress;
    
    function __construct($companyName = 'Virtual Airline', $companyAddress = '') {
        parent::__construct();
        $this->companyName = $companyName;
        $this->companyAddress = $companyAddress;
    }
    
    function Header() {
        // Logo
        $logoPath = __DIR__ . '/../assets/images/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 6, 30);
        }
        
        // Titre
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('BULLETIN DE PAIE'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Document confidentiel NON OFFICIEL Faut pas déconner quand même ! - ' . date('d/m/Y')), 0, 0, 'C');
    }
}

/**
 * Génère une fiche de paie au format PDF
 * 
 * @param array $data Données du pilote et du salaire
 * @return string Chemin du fichier PDF généré
 */
function genererFichePaiePDF($data) {
    $pdf = new FichePaie($data['company_name'] ?? 'Virtual Airline', $data['company_address'] ?? '');
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);
    
    // Période de paie
    $periode = $data['periode'] ?? date('m/Y');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, utf8_decode('Période : ' . $periode), 0, 1, 'R');
    $pdf->Ln(3);
    
    // Informations entreprise
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(95, 6, utf8_decode('EMPLOYEUR'), 1, 0, 'L', true);
    $pdf->Cell(95, 6, utf8_decode('SALARIÉ'), 1, 1, 'L', true);
    
    $pdf->SetFont('Arial', '', 9);
    // Colonne employeur
    $pdf->Cell(95, 5, utf8_decode($data['company_name'] ?? 'Virtual Airline'), 'LR', 0);
    // Colonne salarié
    $pdf->Cell(95, 5, utf8_decode(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? '')), 'LR', 1);
    
    $pdf->Cell(95, 5, utf8_decode($data['company_address'] ?? 'Siège social'), 'LR', 0);
    $pdf->Cell(95, 5, utf8_decode('Callsign : ' . ($data['callsign'] ?? '')), 'LR', 1);
    
    $pdf->Cell(95, 5, utf8_decode('SIRET : ' . ($data['siret'] ?? '123 456 789 00012')), 'LBR', 0);
    $pdf->Cell(95, 5, utf8_decode('Matricule : ' . ($data['matricule'] ?? str_pad($data['pilote_id'] ?? '1', 6, '0', STR_PAD_LEFT))), 'LBR', 1);
    
    $pdf->Ln(5);
    
    // Tableau des éléments de rémunération
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(90, 7, utf8_decode('LIBELLÉ'), 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'BASE', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'TAUX', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'MONTANT', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 9);
    
    // Salaire de base (heures de vol)
    $heures = $data['heures'] ?? 0;
    $taux_horaire = $data['taux_horaire'] ?? 0;
    $salaire_base = round($heures * $taux_horaire, 2);
    
    $pdf->Cell(90, 6, utf8_decode('Salaire de base (heures de vol)'), 1, 0);
    $pdf->Cell(30, 6, number_format($heures, 2, ',', ' ') . ' h', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($taux_horaire, 2, ',', ' ') . ' ' . chr(128), 1, 0, 'R');
    $pdf->Cell(40, 6, number_format($salaire_base, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    
    // Bonus fret
    $bonus_fret = $data['bonus_fret'] ?? 0;
    $fret_kg = $data['fret_kg'] ?? 0;
    $taux_fret = ($fret_kg > 0) ? round($bonus_fret / $fret_kg, 2) : 0;
    
    if ($bonus_fret > 0) {
        $pdf->Cell(90, 6, utf8_decode('Prime de fret'), 1, 0);
        $pdf->Cell(30, 6, number_format($fret_kg, 0, ',', ' ') . ' kg', 1, 0, 'R');
        $pdf->Cell(30, 6, number_format($taux_fret, 2, ',', ' ') . ' ' . chr(128) . '/kg', 1, 0, 'R');
        $pdf->Cell(40, 6, number_format($bonus_fret, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    }
    
    // Salaire brut
    $salaire_brut = $salaire_base + $bonus_fret;
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(150, 6, utf8_decode('SALAIRE BRUT'), 1, 0, 'R');
    $pdf->Cell(40, 6, number_format($salaire_brut, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    
    $pdf->Ln(2);
    
    // Cotisations sociales (fictives mais réalistes)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(90, 7, utf8_decode('COTISATIONS ET CONTRIBUTIONS'), 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'BASE', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'TAUX', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'MONTANT', 1, 1, 'C', true);
    
    $pdf->SetFont('Arial', '', 8);
    
    // Cotisations patronales (affichage simplifié)
    $cotisations = [
        ['lib' => 'CSG déductible', 'taux' => 6.8, 'base' => $salaire_brut],
        ['lib' => 'CSG/CRDS non déductible', 'taux' => 2.9, 'base' => $salaire_brut],
        ['lib' => 'Sécurité sociale', 'taux' => 7.5, 'base' => $salaire_brut],
        ['lib' => 'Retraite complémentaire', 'taux' => 3.15, 'base' => $salaire_brut],
        ['lib' => 'Prévoyance', 'taux' => 1.5, 'base' => $salaire_brut],
        ['lib' => 'Assurance chômage', 'taux' => 2.4, 'base' => $salaire_brut],
        ['lib' => 'Tickets resto', 'taux' => 0.5, 'base' => $salaire_brut],
        ['lib' => 'Backshish', 'taux' => 0.1, 'base' => $salaire_brut],
    ];
    
    $total_cotisations = 0;
    foreach ($cotisations as $cot) {
        $montant = round($cot['base'] * $cot['taux'] / 100, 2);
        $total_cotisations += $montant;
        
        $pdf->Cell(90, 5, utf8_decode($cot['lib']), 1, 0);
        $pdf->Cell(30, 5, number_format($cot['base'], 2, ',', ' ') . ' ' . chr(128), 1, 0, 'R');
        $pdf->Cell(30, 5, $cot['taux'] . ' %', 1, 0, 'R');
        $pdf->Cell(40, 5, number_format($montant, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    }
    
    // Total des cotisations
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(150, 6, utf8_decode('TOTAL DES RETENUES'), 1, 0, 'R');
    $pdf->Cell(40, 6, number_format($total_cotisations, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    
    $pdf->Ln(3);
    
    // Net à payer
    $net_imposable = $salaire_brut - $total_cotisations + ($salaire_brut * 6.8 / 100); // CSG déductible
    $net_a_payer = $salaire_brut - $total_cotisations;
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(150, 6, utf8_decode('NET IMPOSABLE'), 1, 0, 'R');
    $pdf->Cell(40, 6, number_format($net_imposable, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(150, 8, utf8_decode('NET À PAYER'), 1, 0, 'R', true);
    $pdf->Cell(40, 8, number_format($net_a_payer, 2, ',', ' ') . ' ' . chr(128), 1, 1, 'R', true);
    
    $pdf->Ln(5);
    
    // Informations complémentaires
    $pdf->SetFont('Arial', 'I', 8);
    $infos = "Heures de vol effectuées : " . number_format($heures, 2, ',', ' ') . " h\n";
    if ($fret_kg > 0) {
        $infos .= "Fret transporté : " . number_format($fret_kg, 0, ',', ' ') . " kg\n";
    }
    $infos .= "Date de paiement : " . ($data['date_paiement'] ?? date('d/m/Y')) . "\n";
    $infos .= "Virement bancaire effectué sur le compte du pilote.";
    $pdf->MultiCell(0, 4, utf8_decode($infos), 0, 'L');
    
    // Génération du fichier
    $filename = 'fiche_paie_' . ($data['callsign'] ?? 'pilote') . '_' . str_replace('/', '-', $periode) . '.pdf';
    $filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
    $pdf->Output('F', $filepath);
    
    return $filepath;
}
