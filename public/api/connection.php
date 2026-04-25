<?php
/**
 * API REST para gerenciamento de conexões
 * Endpoints: GET, POST, PUT, DELETE
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../classes/ConnectionManager.class.php';
require_once __DIR__ . '/../../classes/Auth.class.php';

class ConnectionAPI {
    private $connectionManager;
    private $auth;
    
    public function __construct() {
        $this->connectionManager = new ConnectionManager();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * Roteador da API
     */
    public function handleRequest() {
        try {
            // Verifica autenticação (exceto para health check)
            if ($_SERVER['REQUEST_URI'] !== '/api/connection/health') {
                $this->verifyAuth();
            }
            
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $segments = array_filter(explode('/', $path));
            
            // Remove 'api' e 'connection' do path
            $segments = array_slice($segments, 2);
            $action = !empty($segments[0]) ? $segments[0] : 'index';
            $param = !empty($segments[1]) ? $segments[1] : null;
            
            switch ($method) {
                case 'GET':
                    $this->handleGet($action, $param);
                    break;
                case 'POST':
                    $this->handlePost($action, $param);
                    break;
                case 'PUT':
                    $this->handlePut($action, $param);
                    break;
                case 'DELETE':
                    $this->handleDelete($action, $param);
                    break;
                default:
                    $this->response(405, ['error' => 'Método não permitido']);
            }
        } catch (Exception $e) {
            $this->response(500, ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Verifica autenticação
     */
    private function verifyAuth() {
        $headers = getallheaders();
        
        // Verifica API Key
        if (isset($headers['X-API-Key'])) {
            $apiKey = $headers['X-API-Key'];
            if (!$this->validateApiKey($apiKey)) {
                $this->response(401, ['error' => 'API Key inválida']);
            }
            return;
        }
        
        // Verifica token de sessão
        if ($this->auth->isLoggedIn()) {
            return;
        }
        
        $this->response(401, ['error' => 'Não autorizado. Faça login ou use API Key.']);
    }
    
    /**
     * Valida API Key
     */
    private function validateApiKey($apiKey) {
        // Implementar validação de API Key conforme necessário
        // Por enquanto, aceita qualquer key não vazia (substituir por lógica real)
        return !empty($apiKey);
    }
    
    /**
     * Handler para requisições GET
     */
    private function handleGet($action, $param) {
        switch ($action) {
            case 'health':
                $this->response(200, ['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')]);
                break;
                
            case 'stats':
                $stats = $this->connectionManager->getConnectionStats();
                $this->response(200, $stats);
                break;
                
            case 'user':
                if (!$param) {
                    $this->response(400, ['error' => 'ID do usuário necessário']);
                    return;
                }
                $connections = $this->connectionManager->getUserActiveConnections($param);
                $this->response(200, ['connections' => $connections, 'count' => count($connections)]);
                break;
                
            case 'check':
                // Verifica se um usuário pode conectar
                $userId = $_GET['user_id'] ?? null;
                $username = $_GET['username'] ?? null;
                if (!$userId || !$username) {
                    $this->response(400, ['error' => 'user_id e username necessários']);
                    return;
                }
                $sessionId = $_GET['session_id'] ?? uniqid('sess_', true);
                $ipAddress = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $userAgent = $_GET['user_agent'] ?? '';
                $connectionType = $_GET['type'] ?? 'live';
                $streamId = $_GET['stream_id'] ?? null;
                
                $result = $this->connectionManager->checkConnection(
                    $userId, $username, $sessionId, $ipAddress, $userAgent, $connectionType, $streamId
                );
                $this->response(200, $result);
                break;
                
            case 'list':
                // Lista todas as conexões ativas com paginação
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                // Implementar listagem completa se necessário
                $stats = $this->connectionManager->getConnectionStats();
                $this->response(200, $stats);
                break;
                
            default:
                $this->response(404, ['error' => 'Endpoint não encontrado']);
        }
    }
    
    /**
     * Handler para requisições POST
     */
    private function handlePost($action, $param) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'heartbeat':
                // Atualiza heartbeat de uma conexão
                $sessionId = $data['session_id'] ?? null;
                if (!$sessionId) {
                    $this->response(400, ['error' => 'session_id necessário']);
                    return;
                }
                $this->connectionManager->updateHeartbeat($sessionId);
                $this->response(200, ['status' => 'heartbeat atualizado']);
                break;
                
            case 'logout':
                // Finaliza uma conexão específica
                $sessionId = $data['session_id'] ?? null;
                if (!$sessionId) {
                    $this->response(400, ['error' => 'session_id necessário']);
                    return;
                }
                $this->connectionManager->closeConnection($sessionId);
                $this->response(200, ['status' => 'conexão finalizada']);
                break;
                
            case 'logout-all':
                // Finaliza todas as conexões de um usuário
                $userId = $data['user_id'] ?? null;
                if (!$userId) {
                    $this->response(400, ['error' => 'user_id necessário']);
                    return;
                }
                $this->connectionManager->closeAllUserConnections($userId);
                $this->response(200, ['status' => 'todas as conexões finalizadas']);
                break;
                
            case 'limit':
                // Define limite de conexões para um usuário
                $userId = $data['user_id'] ?? null;
                $maxConnections = $data['max_connections'] ?? null;
                if (!$userId || $maxConnections === null) {
                    $this->response(400, ['error' => 'user_id e max_connections necessários']);
                    return;
                }
                $success = $this->connectionManager->setUserMaxConnections($userId, $maxConnections);
                if ($success) {
                    $this->response(200, ['status' => 'limite atualizado com sucesso']);
                } else {
                    $this->response(500, ['error' => 'Erro ao atualizar limite']);
                }
                break;
                
            default:
                $this->response(404, ['error' => 'Endpoint não encontrado']);
        }
    }
    
    /**
     * Handler para requisições PUT
     */
    private function handlePut($action, $param) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'limit':
                // Atualiza limite de conexões (alias para POST /limit)
                $userId = $data['user_id'] ?? null;
                $maxConnections = $data['max_connections'] ?? null;
                if (!$userId || $maxConnections === null) {
                    $this->response(400, ['error' => 'user_id e max_connections necessários']);
                    return;
                }
                $success = $this->connectionManager->setUserMaxConnections($userId, $maxConnections);
                if ($success) {
                    $this->response(200, ['status' => 'limite atualizado com sucesso']);
                } else {
                    $this->response(500, ['error' => 'Erro ao atualizar limite']);
                }
                break;
                
            default:
                $this->response(404, ['error' => 'Endpoint não encontrado']);
        }
    }
    
    /**
     * Handler para requisições DELETE
     */
    private function handleDelete($action, $param) {
        switch ($action) {
            case 'session':
                // Finaliza uma conexão específica
                if (!$param) {
                    $this->response(400, ['error' => 'session_id necessário']);
                    return;
                }
                $this->connectionManager->closeConnection($param);
                $this->response(200, ['status' => 'conexão finalizada']);
                break;
                
            case 'user':
                // Finaliza todas as conexões de um usuário
                if (!$param) {
                    $this->response(400, ['error' => 'user_id necessário']);
                    return;
                }
                $this->connectionManager->closeAllUserConnections($param);
                $this->response(200, ['status' => 'todas as conexões finalizadas']);
                break;
                
            default:
                $this->response(404, ['error' => 'Endpoint não encontrado']);
        }
    }
    
    /**
     * Envia resposta JSON
     */
    private function response($statusCode, $data) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Executa a API
$api = new ConnectionAPI();
$api->handleRequest();
