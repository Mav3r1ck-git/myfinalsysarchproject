<?php
require_once 'database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get lab name from query parameter
$lab = isset($_GET['lab']) ? $_GET['lab'] : '';

if (empty($lab)) {
    echo json_encode([]);
    exit;
}

try {
    // Find lab ID
    $lab_stmt = $conn->prepare("SELECT id FROM computer_labs WHERE lab_name = ?");
    $lab_stmt->execute([$lab]);
    $lab_data = $lab_stmt->fetch(PDO::FETCH_ASSOC);
    
    $lab_id = $lab_data['id'] ?? 0;
    
    // Get existing PC statuses from database
    $pc_status_map = [];
    if ($lab_id) {
        $pc_stmt = $conn->prepare("
            SELECT pc_number, status 
            FROM lab_pcs 
            WHERE lab_id = ?
        ");
        $pc_stmt->execute([$lab_id]);
        $existing_pcs = $pc_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($existing_pcs as $pc) {
            // Extract number from PC format (e.g., "PC01" -> "1")
            $pc_num = preg_replace('/[^0-9]/', '', $pc['pc_number']);
            $pc_status_map[$pc_num] = $pc['status'];
        }
    }
    
    // Generate all 30 PCs with their status
    $pcs = [];
    for ($i = 1; $i <= 30; $i++) {
        // Format as "1" through "30"
        $pc_number = (string)$i;
        
        // Get status from map or default to 'available'
        $status = isset($pc_status_map[$pc_number]) ? $pc_status_map[$pc_number] : 'available';
        
        $pcs[] = [
            'pc_number' => $pc_number,
            'status' => $status
        ];
    }
    
    echo json_encode($pcs);
    
} catch (PDOException $e) {
    // Return empty array in case of error
    echo json_encode([]);
    
    // Log error
    if (function_exists('log_error')) {
        log_error("Error fetching PCs", [
            'message' => $e->getMessage(),
            'lab' => $lab
        ]);
    }
}
?> 