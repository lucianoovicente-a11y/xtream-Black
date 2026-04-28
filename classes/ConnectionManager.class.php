<?php
/**
 * ConnectionManager - Gerenciador de conexões em tempo real
 * Controla limite de conexões simultâneas por usuário e faz check-in/check-out
 * Ajustado para trabalhar com a tabela 'clientes' do sistema Xtream
 */

require_once __DIR__ . '/Database.class.php';
require_once __DIR__ . '/Logger.class.php';

class ConnectionManager {
    private $db;
    private $logger;
    private $table = 'active_connections';
    
    // Tempo máximo sem heartbeat antes de considerar conexão morta (em segundos)
    private $heartbeat_timeout = 120; // 2 minutos
    
    // Intervalo de limpeza de conexões mortas (em segundos)
    private $cleanup_interval = 300; // 5 minutos
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
        $this->initializeTables();
    }
    
    /**
     * Inicializa as tabelas de conexões se não existirem
     */
    private function initializeTables() {
        try {
            // Tabela active_connections
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                username VARCHAR(100) NOT NULL,
                session_id VARCHAR(64) UNIQUE NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                device_info VARCHAR(255),
                connection_type ENUM('live', 'vod', 'series') DEFAULT 'live',
                stream_id INT,
                stream_name VARCHAR(255),
                stream_icon VARCHAR(500),
                started_at DATETIME NOT NULL,
                last_heartbeat DATETIME NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_is_active (is_active),
                INDEX idx_last_heartbeat (last_heartbeat)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
            
            // Tabela clientes_online para compatibilidade com dashboard existente
            $sql2 = "CREATE TABLE IF NOT EXISTS clientes_online (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario VARCHAR(100) NOT NULL,
                ip VARCHAR(45),
                canal_atual VARCHAR(255),
                tipo_conteudo VARCHAR(50),
                canal_icon VARCHAR(500),
                ultima_atividade DATETIME,
                inicio_sessao DATETIME,
                is_watching TINYINT(1) DEFAULT 0,
                is_online TINYINT(1) DEFAULT 1,
                session_id VARCHAR(64),
                INDEX idx_usuario (usuario),
                INDEX idx_is_online (is_online)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $stmt2 = $this->db->getConnection()->prepare($sql2);
            $stmt2->execute();
            
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao inicializar tabelas: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o usuário pode estabelecer uma nova conexão
     * Usa a tabela 'clientes' em vez de 'users'
     * 
     * @param int $userId ID do usuário (ou NULL se não tiver)
     * @param string $username Username do usuário
     * @param string $sessionId ID único da sessão
     * @param string $ipAddress IP do cliente
     * @param string $userAgent User agent do dispositivo
     * @param string $connectionType Tipo de conexão (live, vod, series)
     * @param int|null $streamId ID do stream sendo assistido
     * @return array ['allowed' => bool, 'current_connections' => int, 'max_connections' => int, 'message' => string]
     */
    public function checkConnection($userId, $username, $sessionId, $ipAddress, $userAgent = '', $connectionType = 'live', $streamId = null) {
        try {
            // Primeiro, limpa conexões mortas
            $this->cleanupDeadConnections();
            
            // Obtém o limite de conexões do usuário na tabela clientes
            $maxConnections = $this->getUserMaxConnections($username);
            
            // Conta conexões ativas atuais
            $currentConnections = $this->getActiveConnectionCount($username);
            
            // Verifica se já existe esta sessão ativa (renovação de heartbeat)
            $existingSession = $this->getActiveSessionByUser($username, $sessionId);
            
            if ($existingSession) {
                // Atualiza heartbeat da sessão existente
                $this->updateHeartbeat($existingSession['id']);
                $this->updateClientesOnline($username, $sessionId, $connectionType, $streamId, $ipAddress, true);
                return [
                    'allowed' => true,
                    'current_connections' => $currentConnections,
                    'max_connections' => $maxConnections,
                    'message' => 'Sessão renovada com sucesso',
                    'session_renewed' => true
                ];
            }
            
            // Verifica se excedeu o limite
            if ($currentConnections >= $maxConnections) {
                $this->logger->warning('ConnectionManager', 
                    "Usuário {$username} tentou conectar além do limite. Atuais: {$currentConnections}, Máximo: {$maxConnections}",
                    ['username' => $username, 'ip' => $ipAddress]
                );
                
                return [
                    'allowed' => false,
                    'current_connections' => $currentConnections,
                    'max_connections' => $maxConnections,
                    'message' => "Limite de {$maxConnections} conexão(ões) simultânea(s) atingido. Por favor, desconecte outro dispositivo.",
                    'session_renewed' => false
                ];
            }
            
            // Permite a conexão e registra
            $this->registerConnection($userId, $username, $sessionId, $ipAddress, $userAgent, $connectionType, $streamId);
            $this->updateClientesOnline($username, $sessionId, $connectionType, $streamId, $ipAddress, true);
            
            $this->logger->info('ConnectionManager', 
                "Nova conexão registrada para {$username}. Total: " . ($currentConnections + 1) . "/{$maxConnections}",
                ['username' => $username, 'ip' => $ipAddress, 'type' => $connectionType]
            );
            
            return [
                'allowed' => true,
                'current_connections' => $currentConnections + 1,
                'max_connections' => $maxConnections,
                'message' => 'Conexão autorizada com sucesso',
                'session_renewed' => false
            ];
            
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao verificar conexão: ' . $e->getMessage());
            return [
                'allowed' => false,
                'current_connections' => 0,
                'max_connections' => 1,
                'message' => 'Erro interno ao verificar conexão',
                'session_renewed' => false
            ];
        }
    }
    
    /**
     * Registra uma nova conexão na tabela active_connections
     */
    private function registerConnection($userId, $username, $sessionId, $ipAddress, $userAgent, $connectionType, $streamId) {
        // Busca informações do stream se houver streamId
        $streamName = '';
        $streamIcon = '';
        if ($streamId) {
            $streamTable = ($connectionType == 'live') ? 'streams' : (($connectionType == 'vod') ? 'streams' : 'series');
            $nameField = ($connectionType == 'series') ? 'name' : 'name';
            $iconField = ($connectionType == 'series') ? 'cover' : 'stream_icon';
            
            try {
                $sqlStream = "SELECT {$nameField} as name, {$iconField} as icon FROM {$streamTable} WHERE id = :stream_id LIMIT 1";
                $stmtStream = $this->db->getConnection()->prepare($sqlStream);
                $stmtStream->bindValue(':stream_id', $streamId, PDO::PARAM_INT);
                $stmtStream->execute();
                $streamData = $stmtStream->fetch(PDO::FETCH_ASSOC);
                if ($streamData) {
                    $streamName = $streamData['name'] ?? '';
                    $streamIcon = $streamData['icon'] ?? '';
                }
            } catch (Exception $e) {
                // Ignora erro de busca de stream
            }
        }
        
        $sql = "INSERT INTO {$this->table} 
                (user_id, username, session_id, ip_address, user_agent, connection_type, stream_id, stream_name, stream_icon, started_at, last_heartbeat, is_active) 
                VALUES (:user_id, :username, :session_id, :ip_address, :user_agent, :connection_type, :stream_id, :stream_name, :stream_icon, NOW(), NOW(), 1)";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->bindValue(':connection_type', $connectionType, PDO::PARAM_STR);
        $stmt->bindValue(':stream_id', $streamId, $streamId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':stream_name', $streamName, PDO::PARAM_STR);
        $stmt->bindValue(':stream_icon', $streamIcon, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    /**
     * Atualiza a tabela clientes_online para compatibilidade com dashboard
     */
    private function updateClientesOnline($username, $sessionId, $connectionType, $streamId, $ipAddress, $isWatching) {
        try {
            // Busca informações do stream
            $streamName = 'Menu Principal';
            $streamIcon = '';
            $tipoConteudo = 'N/A';
            
            if ($streamId && $isWatching) {
                $streamTable = ($connectionType == 'live') ? 'streams' : (($connectionType == 'vod') ? 'streams' : 'series');
                $nameField = 'name';
                $iconField = ($connectionType == 'series') ? 'cover' : 'stream_icon';
                
                $sqlStream = "SELECT {$nameField} as name, {$iconField} as icon FROM {$streamTable} WHERE id = :stream_id LIMIT 1";
                $stmtStream = $this->db->getConnection()->prepare($sqlStream);
                $stmtStream->bindValue(':stream_id', $streamId, PDO::PARAM_INT);
                $stmtStream->execute();
                $streamData = $stmtStream->fetch(PDO::FETCH_ASSOC);
                if ($streamData) {
                    $streamName = $streamData['name'] ?? 'N/A';
                    $streamIcon = $streamData['icon'] ?? '';
                    $tipoConteudo = ($connectionType == 'live') ? 'Canal ao Vivo' : (($connectionType == 'vod') ? 'Filme' : 'Série');
                }
            }
            
            // Verifica se já existe registro
            $checkSql = "SELECT id FROM clientes_online WHERE usuario = :username AND session_id = :session_id LIMIT 1";
            $checkStmt = $this->db->getConnection()->prepare($checkSql);
            $checkStmt->bindValue(':username', $username, PDO::PARAM_STR);
            $checkStmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $checkStmt->execute();
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualiza
                $updateSql = "UPDATE clientes_online SET 
                    ip = :ip, canal_atual = :canal, tipo_conteudo = :tipo, canal_icon = :icon,
                    ultima_atividade = NOW(), is_watching = :watching, is_online = 1
                    WHERE id = :id";
                $updateStmt = $this->db->getConnection()->prepare($updateSql);
                $updateStmt->bindValue(':ip', $ipAddress, PDO::PARAM_STR);
                $updateStmt->bindValue(':canal', $streamName, PDO::PARAM_STR);
                $updateStmt->bindValue(':tipo', $tipoConteudo, PDO::PARAM_STR);
                $updateStmt->bindValue(':icon', $streamIcon, PDO::PARAM_STR);
                $updateStmt->bindValue(':watching', $isWatching ? 1 : 0, PDO::PARAM_INT);
                $updateStmt->bindValue(':id', $existing['id'], PDO::PARAM_INT);
                $updateStmt->execute();
            } else {
                // Insere novo
                $insertSql = "INSERT INTO clientes_online 
                    (usuario, ip, canal_atual, tipo_conteudo, canal_icon, ultima_atividade, inicio_sessao, is_watching, is_online, session_id)
                    VALUES (:username, :ip, :canal, :tipo, :icon, NOW(), NOW(), :watching, 1, :session_id)";
                $insertStmt = $this->db->getConnection()->prepare($insertSql);
                $insertStmt->bindValue(':username', $username, PDO::PARAM_STR);
                $insertStmt->bindValue(':ip', $ipAddress, PDO::PARAM_STR);
                $insertStmt->bindValue(':canal', $streamName, PDO::PARAM_STR);
                $insertStmt->bindValue(':tipo', $tipoConteudo, PDO::PARAM_STR);
                $insertStmt->bindValue(':icon', $streamIcon, PDO::PARAM_STR);
                $insertStmt->bindValue(':watching', $isWatching ? 1 : 0, PDO::PARAM_INT);
                $insertStmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
                $insertStmt->execute();
            }
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao atualizar clientes_online: ' . $e->getMessage());
        }
    }
    
    /**
     * Atualiza o heartbeat de uma conexão existente
     */
    public function updateHeartbeat($sessionId) {
        try {
            $sql = "UPDATE {$this->table} SET last_heartbeat = NOW() WHERE session_id = :session_id AND is_active = 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();
            
            // Também atualiza clientes_online
            $sql2 = "UPDATE clientes_online SET ultima_atividade = NOW() WHERE session_id = :session_id AND is_online = 1";
            $stmt2 = $this->db->getConnection()->prepare($sql2);
            $stmt2->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt2->execute();
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao atualizar heartbeat: ' . $e->getMessage());
        }
    }
    
    /**
     * Finaliza uma conexão (logout ou fechamento)
     */
    public function closeConnection($sessionId) {
        try {
            // Marca como inativo na tabela active_connections
            $sql = "UPDATE {$this->table} SET is_active = 0 WHERE session_id = :session_id";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();
            
            // Marca como offline na tabela clientes_online
            $sql2 = "UPDATE clientes_online SET is_online = 0, ultima_atividade = NOW() WHERE session_id = :session_id";
            $stmt2 = $this->db->getConnection()->prepare($sql2);
            $stmt2->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt2->execute();
            
            $this->logger->info('ConnectionManager', "Conexão {$sessionId} finalizada");
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao fechar conexão: ' . $e->getMessage());
        }
    }
    
    /**
     * Finaliza todas as conexões de um usuário
     */
    public function closeAllUserConnections($userId) {
        try {
            // Busca username se veio por userId
            $username = '';
            if (is_numeric($userId)) {
                $sqlUser = "SELECT usuario FROM clientes WHERE id = :user_id LIMIT 1";
                $stmtUser = $this->db->getConnection()->prepare($sqlUser);
                $stmtUser->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmtUser->execute();
                $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
                $username = $userData['usuario'] ?? '';
            } else {
                $username = $userId;
            }
            
            // Marca como inativo na tabela active_connections
            if ($username) {
                $sql = "UPDATE {$this->table} SET is_active = 0 WHERE username = :username AND is_active = 1";
                $stmt = $this->db->getConnection()->prepare($sql);
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
            }
            
            // Marca como offline na tabela clientes_online
            if ($username) {
                $sql2 = "UPDATE clientes_online SET is_online = 0, ultima_atividade = NOW() WHERE usuario = :username AND is_online = 1";
                $stmt2 = $this->db->getConnection()->prepare($sql2);
                $stmt2->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt2->execute();
            }
            
            $this->logger->info('ConnectionManager', "Todas as conexões do usuário {$username} finalizadas");
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao fechar todas as conexões: ' . $e->getMessage());
        }
    }
    
    /**
     * Limpa conexões mortas (sem heartbeat há muito tempo)
     */
    public function cleanupDeadConnections() {
        try {
            $sql = "UPDATE {$this->table} 
                    SET is_active = 0 
                    WHERE is_active = 1 
                    AND last_heartbeat < DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':timeout', $this->heartbeat_timeout, PDO::PARAM_INT);
            $stmt->execute();
            
            $deadCount = $stmt->rowCount();
            
            if ($deadCount > 0) {
                // Também limpa clientes_online
                $sql2 = "UPDATE clientes_online SET is_online = 0 WHERE ultima_atividade < DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
                $stmt2 = $this->db->getConnection()->prepare($sql2);
                $stmt2->bindValue(':timeout', $this->heartbeat_timeout, PDO::PARAM_INT);
                $stmt2->execute();
                
                $this->logger->info('ConnectionManager', "{$deadCount} conexões mortas limpas");
            }
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao limpar conexões mortas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém o limite de conexões do usuário na tabela clientes
     */
    private function getUserMaxConnections($username) {
        try {
            $sql = "SELECT COALESCE(conexoes, 1) as max_connections FROM clientes WHERE usuario = :username LIMIT 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['max_connections'] : 1;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter limite de conexões: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Conta conexões ativas do usuário
     */
    private function getActiveConnectionCount($username) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username AND is_active = 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao contar conexões: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém sessão ativa específica por username e session_id
     */
    private function getActiveSessionByUser($username, $sessionId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE username = :username AND session_id = :session_id AND is_active = 1 
                    LIMIT 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter sessão: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém todas as conexões ativas de um usuário
     */
    public function getUserActiveConnections($username) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE username = :username AND is_active = 1 
                    ORDER BY started_at DESC";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter conexões do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas gerais de conexões
     * Ajustado para incluir dados da tabela clientes_online
     */
    public function getConnectionStats() {
        try {
            $stats = [];
            
            // Total de conexões ativas na tabela active_connections
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = 1";
            $stmt = $this->db->getConnection()->query($sql);
            $stats['total_active'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Conexões por tipo
            $sql = "SELECT connection_type, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE is_active = 1 
                    GROUP BY connection_type";
            $stmt = $this->db->getConnection()->query($sql);
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Conta também pela tabela clientes_online para compatibilidade
            $sql2 = "SELECT COUNT(*) as total_online FROM clientes_online WHERE is_online = 1";
            $stmt2 = $this->db->getConnection()->query($sql2);
            $stats['total_online'] = $stmt2->fetch(PDO::FETCH_ASSOC)['total_online'];
            
            // Top 10 usuários com mais conexões (pela tabela clientes)
            $sql3 = "SELECT c.usuario, cl.conexoes as max_connections, COUNT(a.id) as active_connections
                    FROM {$this->table} a
                    JOIN clientes c ON a.username = c.usuario
                    LEFT JOIN clientes cl ON c.usuario = cl.usuario
                    WHERE a.is_active = 1
                    GROUP BY a.username
                    ORDER BY active_connections DESC
                    LIMIT 10";
            
            try {
                $stmt3 = $this->db->getConnection()->query($sql3);
                $stats['top_users'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $stats['top_users'] = [];
            }
            
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter estatísticas: ' . $e->getMessage());
            return ['total_active' => 0, 'by_type' => [], 'top_users' => [], 'total_online' => 0];
        }
    }
    
    /**
     * Define o limite de conexões para um usuário na tabela clientes
     */
    public function setUserMaxConnections($username, $maxConnections) {
        try {
            $sql = "UPDATE clientes SET conexoes = :max_connections WHERE usuario = :username";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':max_connections', $maxConnections, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->logger->info('ConnectionManager', 
                "Limite de conexões do usuário {$username} alterado para {$maxConnections}");
            
            // Se o novo limite for menor que o atual, fecha conexões excedentes
            $currentConnections = $this->getActiveConnectionCount($username);
            if ($currentConnections > $maxConnections) {
                $this->closeExcessConnections($username, $maxConnections);
            }
            
            return true;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao definir limite de conexões: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fecha conexões excedentes quando o limite é reduzido
     */
    private function closeExcessConnections($username, $maxConnections) {
        try {
            $sql = "SELECT id FROM {$this->table} 
                    WHERE username = :username AND is_active = 1 
                    ORDER BY started_at ASC 
                    LIMIT :offset, 9999";
            
            $offset = $maxConnections;
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $excessConnections = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($excessConnections as $connId) {
                $closeSql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
                $closeStmt = $this->db->getConnection()->prepare($closeSql);
                $closeStmt->bindValue(':id', $connId, PDO::PARAM_INT);
                $closeStmt->execute();
            }
            
            // Também atualiza clientes_online
            $sql2 = "UPDATE clientes_online SET is_online = 0 WHERE usuario = :username AND is_online = 1 LIMIT :limit";
            $stmt2 = $this->db->getConnection()->prepare($sql2);
            $stmt2->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt2->bindValue(':limit', count($excessConnections), PDO::PARAM_INT);
            $stmt2->execute();
            
            $this->logger->info('ConnectionManager', 
                count($excessConnections) . " conexões excedentes fechadas para usuário {$username}");
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao fechar conexões excedentes: ' . $e->getMessage());
        }
    }
}
