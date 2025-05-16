<?php
/**
 * Error logging helper
 * For debugging database issues with announcements
 */

// Function to log errors to a file
function log_error($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if (!empty($context)) {
        $log_message .= " Context: " . json_encode($context);
    }
    
    $log_message .= PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    // Append to log file
    file_put_contents('logs/error.log', $log_message, FILE_APPEND);
}

// Function to log database queries
function log_query($query, $params = [], $result = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] Query: $query";
    
    if (!empty($params)) {
        $log_message .= " Params: " . json_encode($params);
    }
    
    if ($result !== null) {
        if ($result === false) {
            $log_message .= " Result: FAILED";
        } else {
            $log_message .= " Result: SUCCESS";
        }
    }
    
    $log_message .= PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    
    // Append to log file
    file_put_contents('logs/query.log', $log_message, FILE_APPEND);
}
?> 