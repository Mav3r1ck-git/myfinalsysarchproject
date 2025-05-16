<?php
require_once 'database.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// Get filter parameters
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$purpose_filter = isset($_GET['purpose']) ? $_GET['purpose'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Build query for logged out sessions
$sql = "SELECT s.*, u.first_name, u.middle_name, u.last_name, u.user_id 
        FROM sitin_sessions s 
        JOIN users u ON s.user_id = u.user_id 
        WHERE s.status IN ('logged_out', 'rewarded')";
$params = [];

if ($lab_filter) {
    $sql .= " AND s.lab = ?";
    $params[] = $lab_filter;
}

if ($purpose_filter) {
    $sql .= " AND s.purpose = ?";
    $params[] = $purpose_filter;
}

$sql .= " ORDER BY s.ended_at DESC";

// Fetch logged out sessions
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format current date for filename
$today = date('Y-m-d');

// Process based on requested format
switch($format) {
    case 'csv':
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sitin_sessions_' . $today . '.csv"');

        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add column headers
        fputcsv($output, ['Student ID', 'Name', 'Lab', 'PC Number', 'Purpose', 'Start Time', 'End Time', 'Status']);
        
        // Add data rows
        foreach ($sessions as $session) {
            $name = $session['last_name'] . ', ' . $session['first_name'] . ' ' . $session['middle_name'];
            $purpose = $session['purpose'];
            if ($session['other_purpose']) {
                $purpose .= ' - ' . $session['other_purpose'];
            }
            $row = [
                $session['user_id'],
                $name,
                $session['lab'],
                $session['pc_number'],
                $purpose,
                date('Y-m-d H:i:s', strtotime($session['created_at'])),
                date('Y-m-d H:i:s', strtotime($session['ended_at'])),
                $session['status']
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    case 'excel':
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="sitin_sessions_' . $today . '.xls"');
        header('Cache-Control: max-age=0');
        
        // Start HTML output
        echo '<table border="1">';
        
        // Add header row
        echo '<tr>';
        echo '<th>Student ID</th>';
        echo '<th>Name</th>';
        echo '<th>Lab</th>';
        echo '<th>PC Number</th>';
        echo '<th>Purpose</th>';
        echo '<th>Start Time</th>';
        echo '<th>End Time</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        
        // Add data rows
        foreach ($sessions as $session) {
            $name = $session['last_name'] . ', ' . $session['first_name'] . ' ' . $session['middle_name'];
            $purpose = $session['purpose'];
            if ($session['other_purpose']) {
                $purpose .= ' - ' . $session['other_purpose'];
            }
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($session['user_id']) . '</td>';
            echo '<td>' . htmlspecialchars($name) . '</td>';
            echo '<td>' . htmlspecialchars($session['lab']) . '</td>';
            echo '<td>' . htmlspecialchars($session['pc_number']) . '</td>';
            echo '<td>' . htmlspecialchars($purpose) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($session['created_at'])) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($session['ended_at'])) . '</td>';
            echo '<td>' . htmlspecialchars($session['status']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        exit;
        
    case 'print':
        // Output printable HTML page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sit-in Sessions Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @media print {
                    body {
                        font-size: 12pt;
                    }
                    .print-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .print-button {
                        display: none;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                    }
                    th {
                        background-color: #f2f2f2;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container-fluid mt-4">
                <div class="print-header">
                    <h4>University of Cebu-Main</h4>
                    <h5>College of Computer Studies</h5>
                    <h5>Computer Laboratory Sitin Monitoring</h5>
                    <h5>System Report</h5>
                    <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
                    
                    <?php if ($lab_filter || $purpose_filter): ?>
                        <p>
                            <?php if ($lab_filter): ?>
                                <strong>Lab:</strong> <?php echo htmlspecialchars($lab_filter); ?> 
                            <?php endif; ?>
                            
                            <?php if ($purpose_filter): ?>
                                <strong>Purpose:</strong> <?php echo htmlspecialchars($purpose_filter); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mb-4 print-button">
                    <button onclick="window.print();" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Lab</th>
                                <th>PC Number</th>
                                <th>Purpose</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sessions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No sit-in sessions found with the specified filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($session['user_id']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($session['last_name'] . ', ' . $session['first_name'] . ' ' . $session['middle_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($session['lab']); ?></td>
                                        <td><?php echo htmlspecialchars($session['pc_number']); ?></td>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($session['purpose']);
                                            if ($session['other_purpose']) {
                                                echo ' - ' . htmlspecialchars($session['other_purpose']);
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($session['created_at'])); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($session['ended_at'])); ?></td>
                                        <td>
                                            <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
                // Auto-print when page loads
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                }
            </script>
        </body>
        </html>
        <?php
        exit;
        
    default:
        // Redirect back to admin page if format is invalid
        header("Location: admin_manage_sitin.php");
        exit;
}
?> 