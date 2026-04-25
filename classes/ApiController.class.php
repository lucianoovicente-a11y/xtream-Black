<?php
/**
 * ApiController.class.php
 * 
 * API REST Pública Documentada
 * Fornece endpoints para integração com aplicativos de terceiros,
 * WHMCS, sistemas externos e desenvolvedores.
 * 
 * Documentação Swagger disponível em: /api/docs
 */

class ApiController {
    private $db;
    private $logger;
    private $apiKey;
    private $userId;
    
    // Rate limiting
    private $rateLimit = [
        'requests_per_minute' => 60,
        'requests_per_hour' => 1000
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger('api');
    }

    /**
     * Roteador da API
     * Recebe requisições e dispatch para o controller apropriado
     */
    public function handleRequest($method, $endpoint, $params = [], $headers = []) {
        // 1. Autenticação via API Key ou Bearer Token
        $auth = $this->authenticate($headers);
        if (!$auth['success']) {
            return $this->response(401, ['error' => $auth['message']]);
        }

        // 2. Rate Limiting
        if (!$this->checkRateLimit($auth['user_id'])) {
            return $this->response(429, ['error' => 'Rate limit exceeded']);
        }

        // 3. Roteamento
        try {
            $result = $this->route($method, $endpoint, $params, $auth['user_id']);
            return $result;
        } catch (Exception $e) {
            $this->logger->error("API Error: " . $e->getMessage(), ['endpoint' => $endpoint]);
            return $this->response(500, ['error' => 'Internal server error']);
        }
    }

    /**
     * Autentica requisição
     */
    private function authenticate($headers) {
        // Verificar API Key
        if (isset($headers['X-API-Key'])) {
            $apiKey = $headers['X-API-Key'];
            $keyData = $this->db->select('api_keys', '*', ['api_key' => $apiKey, 'status' => 'active'], 'fetch');
            
            if ($keyData) {
                // Atualizar last_used
                $this->db->update('api_keys', ['last_used' => date('Y-m-d H:i:s')], ['id' => $keyData['id']]);
                
                return [
                    'success' => true,
                    'user_id' => $keyData['user_id'],
                    'permissions' => json_decode($keyData['permissions'], true)
                ];
            }
        }

        // Verificar Bearer Token (OAuth2 style)
        if (isset($headers['Authorization']) && strpos($headers['Authorization'], 'Bearer ') === 0) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $tokenData = $this->db->select('api_tokens', '*', ['token' => $token, 'expires_at[>]' => date('Y-m-d H:i:s')], 'fetch');
            
            if ($tokenData) {
                return [
                    'success' => true,
                    'user_id' => $tokenData['user_id'],
                    'permissions' => json_decode($tokenData['permissions'], true)
                ];
            }
        }

        return ['success' => false, 'message' => 'Invalid or missing API key'];
    }

    /**
     * Verifica rate limiting
     */
    private function checkRateLimit($userId) {
        $now = time();
        $minuteAgo = date('Y-m-d H:i:s', $now - 60);
        $hourAgo = date('Y-m-d H:i:s', $now - 3600);

        $requestsLastMinute = $this->db->count('api_logs', [
            'user_id' => $userId,
            'created_at[>]' => $minuteAgo
        ]);

        $requestsLastHour = $this->db->count('api_logs', [
            'user_id' => $userId,
            'created_at[>]' => $hourAgo
        ]);

        if ($requestsLastMinute >= $this->rateLimit['requests_per_minute'] ||
            $requestsLastHour >= $this->rateLimit['requests_per_hour']) {
            return false;
        }

        // Log request
        $this->db->insert('api_logs', [
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Roteador de endpoints
     */
    private function route($method, $endpoint, $params, $userId) {
        // Rotas da API v1
        $routes = [
            'GET /users' => 'getUsers',
            'GET /users/:id' => 'getUser',
            'POST /users' => 'createUser',
            'PUT /users/:id' => 'updateUser',
            'DELETE /users/:id' => 'deleteUser',
            
            'GET /connections' => 'getConnections',
            'GET /connections/stats' => 'getConnectionStats',
            
            'GET /streams/live' => 'getLiveStreams',
            'GET /streams/movies' => 'getMovies',
            'GET /streams/series' => 'getSeries',
            
            'GET /packages' => 'getPackages',
            'POST /payments' => 'createPayment',
            
            'GET /stats/dashboard' => 'getDashboardStats',
            'GET /logs' => 'getLogs'
        ];

        $routeKey = strtoupper($method) . ' ' . $endpoint;
        
        if (!isset($routes[$routeKey])) {
            return $this->response(404, ['error' => 'Endpoint not found']);
        }

        $controllerMethod = $routes[$routeKey];
        return $this->$controllerMethod($params, $userId);
    }

    // --- ENDPOINTS DE USUÁRIOS ---

    /**
     * GET /users - Lista usuários
     */
    private function getUsers($params, $userId) {
        $limit = min($params['limit'] ?? 50, 100);
        $offset = $params['offset'] ?? 0;
        $status = $params['status'] ?? null;

        $where = [];
        if ($status) $where['status'] = $status;

        $users = $this->db->select('users', '*', $where, 'all', "LIMIT $offset, $limit");
        $total = $this->db->count('users', $where);

        return $this->response(200, [
            'data' => $users,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }

    /**
     * GET /users/:id - Detalhes de um usuário
     */
    private function getUser($params, $userId) {
        $user = $this->db->select('users', '*', ['id' => $params['id']], 'fetch');
        
        if (!$user) {
            return $this->response(404, ['error' => 'User not found']);
        }

        // Adicionar informações extras
        $user['connections'] = $this->db->count('active_connections', ['user_id' => $params['id']]);
        $user['payments'] = $this->db->select('payments', 'id, amount, status, created_at', ['user_id' => $params['id']], 'all');

        return $this->response(200, ['data' => $user]);
    }

    /**
     * POST /users - Criar usuário
     */
    private function createUser($params, $userId) {
        // Validar permissões
        if (!$this->hasPermission($userId, 'users.create')) {
            return $this->response(403, ['error' => 'Permission denied']);
        }

        $required = ['username', 'password', 'email'];
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return $this->response(400, ['error' => "Field {$field} is required"]);
            }
        }

        $data = [
            'username' => $params['username'],
            'password' => password_hash($params['password'], PASSWORD_BCRYPT),
            'email' => $params['email'],
            'status' => $params['status'] ?? 'active',
            'expiration_date' => $params['expiration_date'] ?? date('Y-m-d H:i:s', strtotime('+30 days')),
            'max_connections' => $params['max_connections'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $newId = $this->db->insert('users', $data);
        
        // Provisionar automaticamente
        $sm = new ServiceManager();
        $sm->provisionUser($newId, $params['username'], $params['password'], [
            'max_connections' => $data['max_connections']
        ]);

        $this->logger->info("Usuário criado via API", ['user_id' => $newId, 'by' => $userId]);

        return $this->response(201, ['data' => ['id' => $newId] + $data]);
    }

    /**
     * PUT /users/:id - Atualizar usuário
     */
    private function updateUser($params, $userId) {
        $allowedFields = ['status', 'expiration_date', 'max_connections', 'notes'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($params[$field])) {
                $updateData[$field] = $params[$field];
            }
        }

        if (empty($updateData)) {
            return $this->response(400, ['error' => 'No fields to update']);
        }

        $this->db->update('users', $updateData, ['id' => $params['id']]);
        
        // Hot reload se mudou limites
        if (isset($params['max_connections'])) {
            $sm = new ServiceManager();
            $sm->hotReloadLimits($params['id'], ['max_connections' => $params['max_connections']]);
        }

        return $this->response(200, ['success' => true]);
    }

    /**
     * DELETE /users/:id - Remover usuário
     */
    private function deleteUser($params, $userId) {
        $sm = new ServiceManager();
        $result = $sm->deprovisionUser($params['id']);

        if ($result['success']) {
            $this->db->update('users', ['status' => 'deleted'], ['id' => $params['id']]);
            return $this->response(200, ['success' => true]);
        }

        return $this->response(500, ['error' => $result['message']]);
    }

    // --- ENDPOINTS DE CONEXÕES ---

    /**
     * GET /connections - Lista conexões ativas
     */
    private function getConnections($params, $userId) {
        $connections = $this->db->query("
            SELECT c.*, u.username 
            FROM active_connections c
            JOIN users u ON c.user_id = u.id
            WHERE c.last_heartbeat > ?
            ORDER BY c.created_at DESC
            LIMIT 100
        ", [date('Y-m-d H:i:s', strtotime('-5 minutes'))])->fetchAll(PDO::FETCH_ASSOC);

        return $this->response(200, ['data' => $connections]);
    }

    /**
     * GET /connections/stats - Estatísticas de conexões
     */
    private function getConnectionStats($params, $userId) {
        $cm = new ConnectionManager();
        return $this->response(200, ['data' => $cm->getStats()]);
    }

    // --- ENDPOINTS DE STREAMS ---

    /**
     * GET /streams/live - Canais ao vivo
     */
    private function getLiveStreams($params, $userId) {
        $category = $params['category_id'] ?? null;
        $where = $category ? ['category_id' => $category, 'stream_type' => 'live'] : ['stream_type' => 'live'];
        
        $streams = $this->db->select('streams', '*', $where, 'all', 'LIMIT 100');
        return $this->response(200, ['data' => $streams]);
    }

    /**
     * GET /streams/movies - Filmes
     */
    private function getMovies($params, $userId) {
        $where = ['stream_type' => 'movie'];
        if (isset($params['genre'])) $where['genre'] = $params['genre'];
        
        $streams = $this->db->select('streams', '*', $where, 'all', 'LIMIT 100');
        return $this->response(200, ['data' => $streams]);
    }

    /**
     * GET /streams/series - Séries
     */
    private function getSeries($params, $userId) {
        $where = ['stream_type' => 'series'];
        $streams = $this->db->select('streams', '*', $where, 'all', 'LIMIT 100');
        return $this->response(200, ['data' => $streams]);
    }

    // --- ENDPOINTS FINANCEIROS ---

    /**
     * GET /packages - Planos disponíveis
     */
    private function getPackages($params, $userId) {
        $packages = $this->db->select('packages', '*', ['status' => 'active'], 'all');
        return $this->response(200, ['data' => $packages]);
    }

    /**
     * POST /payments - Criar pagamento
     */
    private function createPayment($params, $userId) {
        $pg = new PaymentGateway();
        $result = $pg->createPayment(
            $userId,
            $params['amount'],
            $params['method'],
            $params['description'] ?? 'Assinatura'
        );

        if ($result['success']) {
            return $this->response(201, ['data' => $result]);
        }

        return $this->response(400, ['error' => $result['message']]);
    }

    // --- ENDPOINTS DE ESTATÍSTICAS ---

    /**
     * GET /stats/dashboard - Stats do dashboard
     */
    private function getDashboardStats($params, $userId) {
        return $this->response(200, ['data' => [
            'total_users' => $this->db->count('users'),
            'active_users' => $this->db->count('users', ['status' => 'active']),
            'total_connections' => $this->db->count('active_connections'),
            'revenue_today' => $this->db->query("SELECT SUM(amount) FROM payments WHERE status='approved' AND DATE(created_at)=CURDATE()")->fetchColumn(),
            'violations_today' => (new AntiSharing())->getSecurityStats()['total_violations_today']
        ]]);
    }

    /**
     * GET /logs - Logs do sistema
     */
    private function getLogs($params, $userId) {
        $type = $params['type'] ?? 'general';
        $limit = min($params['limit'] ?? 50, 200);
        
        $logs = $this->db->select('system_logs', '*', ['log_type' => $type], 'all', "ORDER BY created_at DESC LIMIT $limit");
        return $this->response(200, ['data' => $logs]);
    }

    // --- UTILITÁRIOS ---

    /**
     * Verifica permissão do usuário
     */
    private function hasPermission($userId, $permission) {
        $user = $this->db->select('users', 'role', ['id' => $userId], 'fetch');
        
        // Admin tem todas as permissões
        if ($user['role'] === 'admin') return true;
        
        // Verificar permissões específicas
        $apiKeys = $this->db->select('api_keys', 'permissions', ['user_id' => $userId, 'status' => 'active'], 'all');
        
        foreach ($apiKeys as $key) {
            $perms = json_decode($key['permissions'], true);
            if (in_array($permission, $perms) || in_array('*', $perms)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Formata resposta JSON padrão
     */
    private function response($statusCode, $data) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
        
        echo json_encode(array_merge([
            'status' => $statusCode,
            'timestamp' => date('c')
        ], $data));
        exit;
    }

    /**
     * Gera documentação Swagger/OpenAPI
     */
    public function getSwaggerDocs() {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'XTream Server API',
                'version' => '1.0.0',
                'description' => 'API REST para gerenciamento de servidor de streaming'
            ],
            'servers' => [
                ['url' => SITE_URL . '/api/v1']
            ],
            'paths' => [
                '/users' => [
                    'get' => ['summary' => 'List users', 'responses' => ['200' => ['description' => 'Success']]],
                    'post' => ['summary' => 'Create user', 'responses' => ['201' => ['description' => 'Created']]]
                ],
                '/connections/stats' => [
                    'get' => ['summary' => 'Get connection statistics', 'responses' => ['200' => ['description' => 'Success']]]
                ]
                // ... mais endpoints
            ],
            'components' => [
                'securitySchemes' => [
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ]
                ]
            ]
        ];
    }
}
