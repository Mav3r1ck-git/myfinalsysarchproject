<?php
require_once 'database.php';

try {
    $conn->beginTransaction();
    
    // 1. Create a backup of lab_pcs table
    $conn->exec("CREATE TABLE IF NOT EXISTS lab_pcs_backup LIKE lab_pcs");
    $conn->exec("INSERT INTO lab_pcs_backup SELECT * FROM lab_pcs");
    echo "Created backup of lab_pcs table.<br>";
    
    // 2. Modify the table structure to handle the new status values
    $conn->exec("ALTER TABLE lab_pcs MODIFY COLUMN status ENUM('active', 'inactive', 'in-use', 'available', 'maintenance') DEFAULT 'active'");
    echo "Modified status column to accept new values.<br>";
    
    // 3. Update the status values
    $conn->exec("
        UPDATE lab_pcs 
        SET status = CASE 
            WHEN status = 'available' THEN 'active' 
            WHEN status = 'maintenance' THEN 'inactive' 
            ELSE status 
        END
    ");
    echo "Updated PC status values: available → active, maintenance → inactive.<br>";
    
    // 4. Modify the table structure again to remove old status options
    $conn->exec("ALTER TABLE lab_pcs MODIFY COLUMN status ENUM('active', 'inactive', 'in-use') DEFAULT 'active'");
    echo "Updated table structure to use only new status values.<br>";
    
    $conn->commit();
    echo "Database schema update completed successfully!";
} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}
?> 