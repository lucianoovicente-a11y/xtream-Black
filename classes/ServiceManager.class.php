<?php
/**
 * ServiceManager.class.php
 * 
 * Gerenciador de Provisionamento Automático
 * Responsável por configurar streams, regras de firewall e alocação de recursos
 * sem necessidade de reinício do servidor.
 */

class ServiceManager {
    private $db;
    private $logger;
    private $configPath;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger('service_manager');
        $this->configPath = dirname(__DIR__) . '/configs/stream_configs/';
        
        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0755, true);
        }
    }

    /**
     * Provisiona um novo usuário no sistema de streaming
     * Cria configurações individuais, regras de firewall e limites
     */
    public function provisionUser($userId, $username, $password, $limits = []) {
        try {
            $this->db->beginTransaction();

            // 1. Criar configuração individual do Nginx/Stream
            $configFile = $this->createStreamConfig($userId, $username, $limits);
            
            // 2. Aplicar regras de Firewall (iptables/nftables simulation)
            $firewallRuleId = $this->applyFirewallRules($userId, $limits);
            
            // 3. Alocar recursos de banda
            $resourceId = $this->allocateBandwidth($userId, $limits);

            // 4. Registrar no banco
            $data = [
                'user_id' => $userId,
                'config_file' => $configFile,
                'firewall_rule_id' => $firewallRuleId,
                'resource_id' => $resourceId,
                'status' => 'active',
                'provisioned_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('service_provisions', $data);
            
            $this->db->commit();
            $this->logger->info("Usuário {$username} provisionado com sucesso", ['user_id' => $userId]);
            
            return ['success' => true, 'message' => 'Provisionamento concluído', 'data' => $data];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error("Falha no provisionamento: " . $e->getMessage(), ['user_id' => $userId]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Desprovisiona um usuário (cancelamento)
     * Remove configs, regras de firewall e libera recursos
     */
    public function deprovisionUser($userId) {
        try {
            $this->db->beginTransaction();

            // Buscar dados de provisionamento
            $provision = $this->db->select('service_provisions', '*', ['user_id' => $userId], 'fetch');
            
            if ($provision) {
                // 1. Remover config de stream
                $this->removeStreamConfig($provision['config_file']);
                
                // 2. Remover regra de firewall
                $this->removeFirewallRules($provision['firewall_rule_id']);
                
                // 3. Liberar banda
                $this->releaseBandwidth($provision['resource_id']);
                
                // 4. Atualizar status
                $this->db->update('service_provisions', ['status' => 'terminated', 'terminated_at' => date('Y-m-d H:i:s')], ['user_id' => $userId]);
                
                // 5. Derrubar conexões ativas
                $cm = new ConnectionManager();
                $cm->forceLogoutAll($userId);
            }

            $this->db->commit();
            $this->logger->info("Usuário {$userId} desprovisionado com sucesso");
            return ['success' => true, 'message' => 'Desprovisionamento concluído'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reconfigura limites de um usuário em tempo real (Hot Reload)
     */
    public function hotReloadLimits($userId, $newLimits) {
        $provision = $this->db->select('service_provisions', '*', ['user_id' => $userId], 'fetch');
        if (!$provision) return ['success' => false, 'message' => 'Provisionamento não encontrado'];

        // Atualiza arquivo de config sem reiniciar
        $this->updateStreamConfig($provision['config_file'], $newLimits);
        
        // Atualiza regras de firewall dinamicamente
        $this->updateFirewallRules($provision['firewall_rule_id'], $newLimits);
        
        $this->db->update('service_provisions', ['last_reload' => date('Y-m-d H:i:s')], ['user_id' => $userId]);
        
        $this->logger->info("Hot reload realizado para usuário {$userId}");
        return ['success' => true, 'message' => 'Limites atualizados sem interrupção'];
    }

    // --- Métodos Internos de Infraestrutura (Simulados para PHP) ---
    
    private function createStreamConfig($userId, $username, $limits) {
        $filename = $this->configPath . "user_{$userId}.conf";
        $content = "# Config for user {$username}\n";
        $content .= "limit_connections {$limits['max_connections'] ?? 1};\n";
        $content .= "limit_bandwidth {$limits['max_bandwidth'] ?? 1000}kb;\n";
        $content .= "timeout_idle 120;\n";
        
        file_put_contents($filename, $content);
        return $filename;
    }

    private function removeStreamConfig($filename) {
        if (file_exists($filename)) unlink($filename);
    }

    private function updateStreamConfig($filename, $limits) {
        $this->createStreamConfig(explode('_', basename($filename))[1], 'user', $limits);
    }

    private function applyFirewallRules($userId, $limits) {
        // Simulação: Em produção, executaria comandos iptables/nftables
        $ruleId = "fw_" . md5($userId . time());
        // shell_exec("iptables -A INPUT -p tcp --dport 8080 -m limit --limit {$limits['max_connections']}/min -j ACCEPT");
        return $ruleId;
    }

    private function removeFirewallRules($ruleId) {
        // shell_exec("iptables -D ...");
        return true;
    }

    private function updateFirewallRules($ruleId, $limits) {
        // Lógica de atualização dinâmica de firewall
        return true;
    }

    private function allocateBandwidth($userId, $limits) {
        $resId = "bw_" . $userId;
        // Lógica de QoS (Quality of Service)
        return $resId;
    }

    private function releaseBandwidth($resId) {
        return true;
    }

    /**
     * Health Check do Serviço
     */
    public function getSystemHealth() {
        return [
            'status' => 'healthy',
            'active_provisions' => $this->db->count('service_provisions', ['status' => 'active']),
            'config_files' => count(glob($this->configPath . "*.conf")),
            'uptime' => shell_exec('uptime') ?: 'N/A'
        ];
    }
}
