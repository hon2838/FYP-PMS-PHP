<?php
require_once 'vendor/autoload.php';
// Create PDF document with custom dimensions for a poster
$pdf = new TCPDF('P', 'mm', array(841.89, 1189.41)); // A0 size
$pdf->SetCreator('SOC PMS');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Paperwork Management System');

// Set margins
$pdf->SetMargins(40, 40, 40);

// Add page
$pdf->AddPage();

// Define colors based on project's theme
$primaryColor = array(0, 123, 255); // Bootstrap primary blue
$secondaryColor = array(108, 117, 125); // Bootstrap secondary gray
$lightBg = array(248, 249, 250); // Bootstrap light background

// Top section - Header
$pdf->SetFillColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
$pdf->Rect(0, 0, 841.89, 100, 'F');

// PID at top left
$pdf->SetFont('helvetica', 'B', 36);
$pdf->SetTextColor(255, 255, 255);
$pdf->Text(50, 50, 'PID: 39');

// Title at top right
$pdf->SetFont('helvetica', 'B', 48);
$pdf->Cell(0, 100, 'School of Computing Paperwork Management System', 0, 1, 'R');

// Project Description Section (First Half)
$pdf->SetY(120);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(400, 20, 'Project Description', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 18);
$pdf->MultiCell(400, 20, 'A web-based system for managing academic paperwork submissions and approvals between students, HODs, and Dean. Features secure document handling, automated workflows, and real-time status tracking.', 0, 'L');

// Method Section (Second Half)
$pdf->SetXY(440, 120);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 20, 'Methodology', 0, 1, 'L');

$pdf->SetXY(440, 150);
$pdf->SetFont('helvetica', '', 18);
$pdf->MultiCell(360, 20, "• User Authentication & Role Management\n• Document Upload & Validation\n• Automated Workflow System\n• Real-time Status Tracking\n• Email Notifications\n• Audit Logging", 0, 'L');

// Screenshots Section
$pdf->SetY(400);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 20, 'System Screenshots', 0, 1, 'C');
// Add placeholder for screenshots (25% of poster height)
$pdf->Rect(50, 430, 741.89, 297, 'D');

// Feasibility Section (First Half)
$pdf->SetY(750);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(400, 20, 'Feasibility & Impact', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 18);
$pdf->MultiCell(400, 20, "• Reduces paper usage and administrative overhead\n• Improves tracking and transparency\n• Speeds up approval processes\n• Enhances document security\n• Provides audit trail", 0, 'L');

// Future Works Section (Second Half)
$pdf->SetXY(440, 750);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 20, 'Future Enhancements', 0, 1, 'L');

$pdf->SetXY(440, 780);
$pdf->SetFont('helvetica', '', 18);
$pdf->MultiCell(360, 20, "• Mobile application development\n• AI-powered document classification\n• Integration with other university systems\n• Advanced analytics dashboard\n• Blockchain-based verification", 0, 'L');

// Innovators Section
$pdf->SetY(900);
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 20, 'Project Team', 0, 1, 'C');

// Placeholder for profile pictures
$pdf->Rect(200, 930, 150, 150, 'D'); // Student
$pdf->Rect(491.89, 930, 150, 150, 'D'); // Supervisor

$pdf->SetY(1090);
$pdf->SetFont('helvetica', '', 18);
$pdf->Cell(841.89/2, 20, 'Student Name', 0, 0, 'C');
$pdf->Cell(841.89/2, 20, 'Supervisor Name', 0, 1, 'C');

// Output PDF
$pdf->Output('conference_poster.pdf', 'I');