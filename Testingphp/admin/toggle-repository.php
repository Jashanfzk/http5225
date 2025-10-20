<?php
/**
 * Toggle Repository Status
 * AJAX endpoint to enable/disable repositories
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require admin access
requireAdmin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$repo_id = intval($_POST['repo_id'] ?? 0);
$status = intval($_POST['status'] ?? 0);

if ($repo_id <= 0 || !in_array($status, [0, 1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update repository status
    $stmt = $db->prepare("UPDATE applications SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([$status, $repo_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Repository status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update repository status']);
    }
    
} catch (Exception $e) {
    error_log("Toggle repository error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
