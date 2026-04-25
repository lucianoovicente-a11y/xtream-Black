<?php
/**
 * API REST para Gerenciamento de Conexões
 * Endpoints: GET /api/connection/stats, POST /api/connection/logout, etc.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../Classes/ConnectionManager.class.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$connectionManager = new ConnectionManager();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'stats':
            $stats = $connectionManager->getConnectionStats();
            echo json_encode([
                'success' => true,
                'total_connections' => $stats['total_connections'],
                'total_users_online' => $stats['total_users_online'],
                'connections_at_limit' => $stats['connections_at_limit'],
                'connections' => $stats['recent_connections'] ?? []
            ]);
            break;

        case 'logout':
            $sessionId = $_POST['session_id'] ?? '';
            if ($sessionId) {
                $result = $connectionManager->closeConnection($sessionId);
                echo json_encode(['success' => $result, 'message' => $result ? 'Desconectado' : 'Falha']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Session ID necessário']);
            }
            break;

        default:
            // Se não houver action, retorna stats por padrão
            $stats = $connectionManager->getConnectionStats();
            echo json_encode([
                'success' => true,
                'total_connections' => $stats['total_connections'],
                'connections' => $stats['recent_connections'] ?? []
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
