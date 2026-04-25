<?php
/**
 * WebSocketServer.php
 * 
 * Servidor WebSocket para Dashboard em Tempo Real
 * Envia atualizações instantâneas de conexões, uso de banda e alertas
 * sem necessidade de refresh na página.
 */

// Nota: Requer extensão php-pcntl e php-sockets ou biblioteca Ratchet
// Para produção, recomenda-se usar Ratchet: composer require cboden/ratchet

class WebSocketServer {
    private $port = 8080;
    private $master;
    private $sockets = [];
    private $connectionManager;
    private $db;

    public function __construct($port = 8080) {
        $this->port = $port;
        $this->connectionManager = new ConnectionManager();
        $this->db = Database::getInstance();
        
        echo "Iniciando servidor WebSocket na porta {$port}...\n";
    }

    /**
     * Inicia o servidor WebSocket
     */
    public function start() {
        // Cria socket master
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->master < 0) {
            throw new Exception("Falha ao criar socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        
        if (socket_bind($this->master, '0.0.0.0', $this->port) < 0) {
            throw new Exception("Falha ao bind: " . socket_strerror(socket_last_error($this->master)));
        }

        if (socket_listen($this->master, 5) < 0) {
            throw new Exception("Falha ao listen: " . socket_strerror(socket_last_error($this->master)));
        }

        $this->sockets[$this->master] = $this->master;
        echo "Servidor rodando em ws://0.0.0.0:{$this->port}\n";

        // Loop principal
        while (true) {
            $changed = $this->sockets;
            $write = null;
            $except = null;

            $select = socket_select($changed, $write, $except, 0, 10000); // 10ms timeout

            if ($select === false) {
                echo "Erro no select\n";
                break;
            } elseif ($select > 0) {
                foreach ($changed as $socket) {
                    if ($socket == $this->master) {
                        // Nova conexão
                        $client = socket_accept($this->master);
                        if ($client < 0) continue;
                        
                        $this->sockets[$client] = $client;
                        echo "Novo cliente conectado\n";
                    } else {
                        // Dados recebidos
                        $data = socket_read($socket, 1024);
                        if ($data === false || $data === '') {
                            // Cliente desconectou
                            $this->closeClient($socket);
                        } else {
                            $this->handleMessage($socket, $data);
                        }
                    }
                }
            }

            // Broadcast de atualizações periódicas (a cada 2s)
            static $lastUpdate = 0;
            if (time() - $lastUpdate >= 2) {
                $this->broadcastUpdates();
                $lastUpdate = time();
            }
        }
    }

    /**
     * Processa handshake inicial do WebSocket
     */
    private function handleHandshake($socket, $data) {
        if (strpos($data, 'Upgrade: websocket') !== false) {
            preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $data, $matches);
            $key = $matches[1];
            $accept = base64_encode(pack('H*', sha1(trim($key) . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

            $response = "HTTP/1.1 101 Switching Protocols\r\n";
            $response .= "Upgrade: websocket\r\n";
            $response .= "Connection: Upgrade\r\n";
            $response .= "Sec-WebSocket-Accept: $accept\r\n\r\n";

            socket_write($socket, $response, strlen($response));
            echo "Handshake completado\n";
        }
    }

    /**
     * Envia dados codificados para o cliente
     */
    private function sendToClient($socket, $message) {
        $encoded = $this->encodeMessage($message);
        socket_write($socket, $encoded, strlen($encoded));
    }

    /**
     * Broadcast de atualizações em tempo real
     */
    private function broadcastUpdates() {
        $stats = [
            'type' => 'stats_update',
            'timestamp' => time(),
            'data' => [
                'total_connections' => $this->db->count('active_connections'),
                'total_users_online' => $this->db->count('active_connections', [], 'COUNT(DISTINCT user_id)'),
                'server_load' => sys_getloadavg()[0],
                'memory_usage' => memory_get_usage(true),
                'top_users' => $this->getTopUsers(5)
            ]
        ];

        $message = json_encode($stats);
        foreach ($this->sockets as $socket) {
            if ($socket != $this->master) {
                $this->sendToClient($socket, $message);
            }
        }
    }

    /**
     * Obtém top usuários por conexões
     */
    private function getTopUsers($limit = 5) {
        return $this->db->query("
            SELECT u.username, COUNT(c.id) as connections 
            FROM active_connections c 
            JOIN users u ON c.user_id = u.id 
            GROUP BY c.user_id 
            ORDER BY connections DESC 
            LIMIT $limit
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function handleMessage($socket, $data) {
        // Se for handshake
        if (strpos($data, 'GET /') === 0) {
            $this->handleHandshake($socket, $data);
            return;
        }

        // Processar mensagens normais (JSON)
        $decoded = $this->decodeMessage($data);
        $msg = json_decode($decoded, true);

        if ($msg && isset($msg['action'])) {
            switch ($msg['action']) {
                case 'subscribe_user':
                    $this->subscribeToUser($socket, $msg['user_id']);
                    break;
                case 'get_stats':
                    $this->sendStats($socket);
                    break;
            }
        }
    }

    private function closeClient($socket) {
        socket_close($socket);
        unset($this->sockets[array_search($socket, $this->sockets)]);
        echo "Cliente desconectado\n";
    }

    // --- Codificação/Decodificação WebSocket (RFC 6455 simplificada) ---
    
    private function encodeMessage($text) {
        $b1 = 0x80 | (0x1 & 0x0f); // Text frame
        $length = strlen($text);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length <= 65535) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, 0, $length);
        }

        return $header . $text;
    }

    private function decodeMessage($data) {
        // Implementação simplificada - em produção usar biblioteca completa
        return substr($data, 2); 
    }

    private function subscribeToUser($socket, $userId) {
        echo "Cliente subscrito ao usuário {$userId}\n";
    }

    private function sendStats($socket) {
        $this->broadcastUpdates();
    }
}

// Execução via CLI: php WebSocketServer.php
if (php_sapi_name() === 'cli') {
    $server = new WebSocketServer(8080);
    try {
        $server->start();
    } catch (Exception $e) {
        echo "Erro: " . $e->getMessage() . "\n";
    }
}
