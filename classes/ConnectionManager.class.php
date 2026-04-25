<?php
/**
 * ConnectionManager - Gerenciador de conexões em tempo real
 * Controla limite de conexões simultâneas por usuário e faz check-in/check-out
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
        $this->initializeTable();
    }
    
    /**
     * Inicializa a tabela de conexões se não existir
     */
    private function initializeTable() {
        try {
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
            
            // Adiciona coluna max_connections na tabela de usuários se não existir
            $this->addMaxConnectionsColumn();
            
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao inicializar tabela: ' . $e->getMessage());
        }
    }
    
    /**
     * Adiciona coluna max_connections na tabela de usuários
     */
    private function addMaxConnectionsColumn() {
        try {
            // Verifica se a coluna já existe
            $checkSql = "SHOW COLUMNS FROM users LIKE 'max_connections'";
            $stmt = $this->db->getConnection()->prepare($checkSql);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $alterSql = "ALTER TABLE users ADD COLUMN max_connections INT DEFAULT 1 AFTER password";
                $stmt = $this->db->getConnection()->prepare($alterSql);
                $stmt->execute();
                
                // Define valor padrão para usuários existentes
                $updateSql = "UPDATE users SET max_connections = 1 WHERE max_connections IS NULL";
                $stmt = $this->db->getConnection()->prepare($updateSql);
                $stmt->execute();
                
                $this->logger->info('ConnectionManager', 'Coluna max_connections adicionada à tabela users');
            }
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao adicionar coluna max_connections: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o usuário pode estabelecer uma nova conexão
     * 
     * @param int $userId ID do usuário
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
            
            // Obtém o limite de conexões do usuário
            $maxConnections = $this->getUserMaxConnections($userId);
            
            // Conta conexões ativas atuais
            $currentConnections = $this->getActiveConnectionCount($userId);
            
            // Verifica se já existe esta sessão ativa (renovação de heartbeat)
            $existingSession = $this->getActiveSession($userId, $sessionId);
            
            if ($existingSession) {
                // Atualiza heartbeat da sessão existente
                $this->updateHeartbeat($existingSession['id']);
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
                    "Usuário {$username} (ID: {$userId}) tentou conectar além do limite. Atuais: {$currentConnections}, Máximo: {$maxConnections}",
                    ['user_id' => $userId, 'ip' => $ipAddress]
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
            
            $this->logger->info('ConnectionManager', 
                "Nova conexão registrada para {$username}. Total: " . ($currentConnections + 1) . "/{$maxConnections}",
                ['user_id' => $userId, 'ip' => $ipAddress, 'type' => $connectionType]
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
                'max_connections' => 0,
                'message' => 'Erro interno ao verificar conexão',
                'session_renewed' => false
            ];
        }
    }
    
    /**
     * Registra uma nova conexão
     */
    private function registerConnection($userId, $username, $sessionId, $ipAddress, $userAgent, $connectionType, $streamId) {
        $sql = "INSERT INTO {$this->table} 
                (user_id, username, session_id, ip_address, user_agent, connection_type, stream_id, started_at, last_heartbeat, is_active) 
                VALUES (:user_id, :username, :session_id, :ip_address, :user_agent, :connection_type, :stream_id, NOW(), NOW(), 1)";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->bindValue(':connection_type', $connectionType, PDO::PARAM_STR);
        $stmt->bindValue(':stream_id', $streamId, $streamId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
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
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao atualizar heartbeat: ' . $e->getMessage());
        }
    }
    
    /**
     * Finaliza uma conexão (logout ou fechamento)
     */
    public function closeConnection($sessionId) {
        try {
            $sql = "UPDATE {$this->table} SET is_active = 0 WHERE session_id = :session_id";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();
            
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
            $sql = "UPDATE {$this->table} SET is_active = 0 WHERE user_id = :user_id AND is_active = 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->logger->info('ConnectionManager', "Todas as conexões do usuário {$userId} finalizadas");
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
                $this->logger->info('ConnectionManager', "{$deadCount} conexões mortas limpas");
            }
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao limpar conexões mortas: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém o limite de conexões do usuário
     */
    private function getUserMaxConnections($userId) {
        try {
            $sql = "SELECT COALESCE(max_connections, 1) as max_connections FROM users WHERE id = :user_id";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
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
    private function getActiveConnectionCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = :user_id AND is_active = 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao contar conexões: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém sessão ativa específica
     */
    private function getActiveSession($userId, $sessionId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id AND session_id = :session_id AND is_active = 1 
                    LIMIT 1";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
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
    public function getUserActiveConnections($userId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id AND is_active = 1 
                    ORDER BY started_at DESC";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter conexões do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas gerais de conexões
     */
    public function getConnectionStats() {
        try {
            $stats = [];
            
            // Total de conexões ativas
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
            
            // Top 10 usuários com mais conexões
            $sql = "SELECT u.username, u.max_connections, COUNT(c.id) as active_connections
                    FROM {$this->table} c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.is_active = 1
                    GROUP BY c.user_id
                    ORDER BY active_connections DESC
                    LIMIT 10";
            $stmt = $this->db->getConnection()->query($sql);
            $stats['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao obter estatísticas: ' . $e->getMessage());
            return ['total_active' => 0, 'by_type' => [], 'top_users' => []];
        }
    }
    
    /**
     * Define o limite de conexões para um usuário
     */
    public function setUserMaxConnections($userId, $maxConnections) {
        try {
            $sql = "UPDATE users SET max_connections = :max_connections WHERE id = :user_id";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':max_connections', $maxConnections, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->logger->info('ConnectionManager', 
                "Limite de conexões do usuário {$userId} alterado para {$maxConnections}");
            
            // Se o novo limite for menor que o atual, fecha conexões excedentes
            $currentConnections = $this->getActiveConnectionCount($userId);
            if ($currentConnections > $maxConnections) {
                $this->closeExcessConnections($userId, $maxConnections);
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
    private function closeExcessConnections($userId, $maxConnections) {
        try {
            $sql = "SELECT id FROM {$this->table} 
                    WHERE user_id = :user_id AND is_active = 1 
                    ORDER BY started_at ASC 
                    LIMIT :offset, 9999";
            
            $offset = $maxConnections;
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $excessConnections = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($excessConnections as $connId) {
                $closeSql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
                $closeStmt = $this->db->getConnection()->prepare($closeSql);
                $closeStmt->bindValue(':id', $connId, PDO::PARAM_INT);
                $closeStmt->execute();
            }
            
            $this->logger->info('ConnectionManager', 
                count($excessConnections) . " conexões excedentes fechadas para usuário {$userId}");
        } catch (Exception $e) {
            $this->logger->error('ConnectionManager', 'Erro ao fechar conexões excedentes: ' . $e->getMessage());
        }
    }
}
