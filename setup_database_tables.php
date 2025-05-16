<?php
require_once 'database.php';

try {
    // Create reservations table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS sitin_reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            lab VARCHAR(50) NOT NULL,
            pc_number VARCHAR(20) NOT NULL,
            reservation_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            purpose VARCHAR(100) NOT NULL,
            other_purpose TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            admin_notes TEXT,
            admin_id INT
        )
    ");
    echo "Reservations table created successfully.<br>";

    // Create notifications table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            reference_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Notifications table created successfully.<br>";

    // Create computer labs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS computer_labs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lab_name VARCHAR(50) NOT NULL UNIQUE,
            total_pcs INT NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Computer labs table created successfully.<br>";

    // Create lab PCs table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS lab_pcs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lab_id INT NOT NULL,
            pc_number VARCHAR(20) NOT NULL,
            status ENUM('active', 'inactive', 'in-use') DEFAULT 'active',
            specs TEXT,
            last_used TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY lab_pc_unique (lab_id, pc_number),
            FOREIGN KEY (lab_id) REFERENCES computer_labs(id) ON DELETE CASCADE
        )
    ");
    echo "Lab PCs table created successfully.<br>";
    
    // Insert default labs if they don't exist
    $labs = [
        ['Lab 524', 30],
        ['Lab 526', 30],
        ['Lab 528', 30],
        ['Lab 530', 30],
        ['Lab 542', 30],
        ['Lab 544', 30],
        ['Lab 517', 30]
    ];
    
    $lab_check = $conn->prepare("SELECT id FROM computer_labs WHERE lab_name = ?");
    $lab_insert = $conn->prepare("INSERT INTO computer_labs (lab_name, total_pcs) VALUES (?, ?)");
    $pc_insert = $conn->prepare("INSERT INTO lab_pcs (lab_id, pc_number, status) VALUES (?, ?, 'active')");
    
    foreach ($labs as $lab) {
        $lab_check->execute([$lab[0]]);
        if ($lab_check->rowCount() == 0) {
            $lab_insert->execute([$lab[0], $lab[1]]);
            $lab_id = $conn->lastInsertId();
            
            // Create PCs for each lab
            for ($i = 1; $i <= $lab[1]; $i++) {
                $pc_number = "PC" . str_pad($i, 2, "0", STR_PAD_LEFT);
                $pc_insert->execute([$lab_id, $pc_number]);
            }
            echo "Created {$lab[1]} PCs for {$lab[0]}.<br>";
        }
    }
    
    echo "Setup completed successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 