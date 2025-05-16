<?php
require_once 'database.php';

try {
    $conn->beginTransaction();
    
    // Update PC statuses
    $update_stmt = $conn->prepare("
        UPDATE lab_pcs 
        SET status = CASE 
            WHEN status = 'available' THEN 'active' 
            WHEN status = 'maintenance' THEN 'inactive' 
            ELSE status 
        END
    ");
    $update_stmt->execute();
    
    $conn->commit();
    echo "Successfully updated PC status values in the database.";
} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 