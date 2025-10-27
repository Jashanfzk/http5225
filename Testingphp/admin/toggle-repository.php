<?php
/**
 * Toggle repository is_active status (AJAX / POST endpoint)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'invalid_method']);
    exit;
}

$repoId = isset($_POST['repo_id']) ? (int) $_POST['repo_id'] : 0;
$status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;

if ($repoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'invalid_id']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('UPDATE applications SET is_active = ?, updated_at = NOW() WHERE id = ?');
    $ok = $stmt->execute([$status, $repoId]);
    echo json_encode(['success' => (bool) $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

