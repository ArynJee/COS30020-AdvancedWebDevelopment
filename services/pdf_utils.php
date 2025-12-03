<?php
include 'tcpdf/tcpdf.php'; 
require_once 'pdfparser-master/alt_autoload.php-dist';

date_default_timezone_set('Asia/Kuching');

class PDFUtils {
    // Extract text from PDF file using Smalot PDF Parser
    public static function extractTextFromPDF($pdf_path) {
        if (!file_exists($pdf_path)) {
            return "Description not available (file not found).";
        }
        
        try {
            // initialize parser
            $parser = new \Smalot\PdfParser\Parser();
            
            // parse pdf file
            $pdf = $parser->parseFile($pdf_path);
            
            // extract text
            $text = $pdf->getText();
            
            // clean up by removing whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            if (strlen($text) > 1000) {
                $text = substr($text, 0, 1000) . '...';
            }
            
            return $text;
            
        } catch (\Exception $e) {
            error_log("PDF Parser Error: " . $e->getMessage() . " - File: " . $pdf_path);
        }
    }

    public static function getFlowerDescription($flower_row) {
        // If description_extracted already exists, return it
        if (!empty($flower_row['description_extracted'])) {
            return $flower_row['description_extracted'];
        }
        
        $description = $flower_row['description'] ?? '';
        
        // Check if description is a PDF file path
        if (strpos($description, '.pdf') !== false && file_exists($description)) {
            // Extract text from PDF using PDFParser
            $extracted = self::extractTextFromPDF($description);
            
            // Return extracted text
            return $extracted;
        }
        
        // If it's already text, return it
        return $description;
    }

    // use TCPDF create PDF report 
    public static function createFlowerPDF($flower_data, $source = 'AI') {
        // create new pdf document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 20);
        
        $pdf->SetTextColor(89, 0, 43); // #59002b
        $pdf->Cell(0, 10, 'RootFlower Flower Identifier Result', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetLineWidth(0.5);
        $pdf->SetDrawColor(89, 0, 43);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Common Name:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, htmlspecialchars($flower_data['Common_Name'] ?? 'N/A'), 0, 1);
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Scientific Name:', 0, 1);
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 8, htmlspecialchars($flower_data['Scientific_Name'] ?? 'N/A'), 0, 1);
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Description:', 0, 1);
        $pdf->SetFont('helvetica', '', 12);
        
        $description = $flower_data['description'] ?? 'No description available.';
        $pdf->MultiCell(0, 8, htmlspecialchars($description), 0, 'L', false, 1);
        $pdf->Ln(10);
        
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, 'Identified via: ' . ($source === 'database' ? 'RootFlower Database' : 'AI Analysis'), 0, 1);
        $pdf->Cell(0, 8, 'Report generated on: ' . date('F j, Y, g:i a'), 0, 1);
        
        return $pdf->Output('', 'S');
    }
    
    // generate pdf from text data (from AI results)
    public static function generatePDFFromText($data) {
        return self::createFlowerPDF($data, 'AI');
    }
    
    // extract and store pdf text in database
    public static function processUploadedPDF($pdf_path, $conn, $flower_id) {
        $extracted_text = self::extractTextFromPDF($pdf_path);
        
        // prevent sql injection
        $escaped_text = $conn->real_escape_string($extracted_text);
        
        $sql = "UPDATE flower_table SET description_extracted = '$escaped_text' WHERE id = $flower_id";
        
        if ($conn->query($sql)) {
            return true;
        }
        
        return false;
    }
}
?>