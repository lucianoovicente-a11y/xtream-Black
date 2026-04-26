<?php
/**
 * Sistema de Gerenciamento de Logs do XTREAM SERVER
 * Centraliza todas as operações de log do sistema
 */

class Logger {
    private static $instance = null;
    private $logDir;
    private $currentDate;
    
    public function __construct($context = 'system') {
        // Define BASE_DIR se não estiver definido
        if (!defined('BASE_DIR')) {
            define('BASE_DIR', dirname(__DIR__));
        }
        
        // Define PATH_LOGS se não estiver definido
        if (!defined('PATH_LOGS')) {
            define('PATH_LOGS', BASE_DIR . '/logs/');
        }
        
        $this->logDir = PATH_LOGS;
        $this->currentDate = date('Y-m-d');
        $this->context = $context;
        
        // Garantir que o diretório de logs existe
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance($context = 'system') {
        if (self::$instance === null) {
            self::$instance = new self($context);
        }
        return self::$instance;
    }
    
    /**
     * Escrever log genérico
     */
    public function write($message, $level = 'INFO', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = $this->logDir . 'system_' . $this->currentDate . '.log';
        
        $logEntry = sprintf(
            "[%s] [%s] %s",
            $timestamp,
            $level,
            $message
        );
        
        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log de informação
     */
    public function info($message, $context = []) {
        $this->write($message, 'INFO', $context);
    }
    
    /**
     * Log de aviso
     */
    public function warning($message, $context = []) {
        $this->write($message, 'WARNING', $context);
    }
    
    /**
     * Log de erro
     */
    public function error($message, $context = []) {
        $this->write($message, 'ERROR', $context);
    }
    
    /**
     * Log de debug
     */
    public function debug($message, $context = []) {
        if (DEBUG_MODE) {
            $this->write($message, 'DEBUG', $context);
        }
    }
    
    /**
     * Log de atividade do usuário
     */
    public function userActivity($userId, $action, $details = []) {
        $this->info("Usuário {$userId}: {$action}", array_merge(['user_id' => $userId], $details));
    }
    
    /**
     * Log de acesso à API
     */
    public function apiAccess($endpoint, $method, $userId = null, $ip = null) {
        $this->info("API Access: {$method} {$endpoint}", [
            'user_id' => $userId,
            'ip' => $ip ?? get_client_ip(),
            'endpoint' => $endpoint,
            'method' => $method
        ]);
    }
    
    /**
     * Log de erro da API
     */
    public function apiError($endpoint, $error, $userId = null) {
        $this->error("API Error em {$endpoint}: {$error}", [
            'user_id' => $userId,
            'endpoint' => $endpoint
        ]);
    }
    
    /**
     * Log de transação financeira
     */
    public function financialTransaction($type, $amount, $userId, $status, $details = []) {
        $this->info("Transação {$type}: R$ {$amount} - Usuário: {$userId} - Status: {$status}", 
            array_merge([
                'type' => $type,
                'amount' => $amount,
                'user_id' => $userId,
                'status' => $status
            ], $details)
        );
    }
    
    /**
     * Log de upload
     */
    public function upload($fileType, $fileName, $fileSize, $userId, $success = true) {
        $level = $success ? 'INFO' : 'ERROR';
        $message = $success 
            ? "Upload bem-sucedido: {$fileName} ({$fileType})"
            : "Falha no upload: {$fileName} ({$fileType})";
        
        $this->write($message, $level, [
            'file_type' => $fileType,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'user_id' => $userId,
            'success' => $success
        ]);
    }
    
    /**
     * Log de cliente (criação, edição, exclusão)
     */
    public function clientAction($action, $clientId, $clientUsername, $adminId) {
        $this->info("Cliente {$action}: {$clientUsername} (ID: {$clientId})", [
            'action' => $action,
            'client_id' => $clientId,
            'client_username' => $clientUsername,
            'admin_id' => $adminId
        ]);
    }
    
    /**
     * Log de revendedor
     */
    public function resellerAction($action, $resellerId, $resellerUsername, $adminId) {
        $this->info("Revendedor {$action}: {$resellerUsername} (ID: {$resellerId})", [
            'action' => $action,
            'reseller_id' => $resellerId,
            'reseller_username' => $resellerUsername,
            'admin_id' => $adminId
        ]);
    }
    
    /**
     * Log de conexão de cliente
     */
    public function clientConnection($clientId, $clientUsername, $ip, $device, $success = true) {
        $level = $success ? 'INFO' : 'WARNING';
        $message = $success
            ? "Cliente conectado: {$clientUsername}"
            : "Falha na conexão: {$clientUsername}";
        
        $this->write($message, $level, [
            'client_id' => $clientId,
            'client_username' => $clientUsername,
            'ip' => $ip,
            'device' => $device,
            'success' => $success
        ]);
    }
    
    /**
     * Log de EPG
     */
    public function epgUpdate($channelsCount, $success = true) {
        $level = $success ? 'INFO' : 'ERROR';
        $message = $success
            ? "EPG atualizado com sucesso: {$channelsCount} canais"
            : "Falha ao atualizar EPG";
        
        $this->write($message, $level, [
            'channels_count' => $channelsCount,
            'success' => $success
        ]);
    }
    
    /**
     * Log de backup
     */
    public function backup($type, $fileName, $size, $success = true) {
        $level = $success ? 'INFO' : 'ERROR';
        $message = $success
            ? "Backup realizado: {$fileName} ({$type})"
            : "Falha no backup: {$fileName}";
        
        $this->write($message, $level, [
            'type' => $type,
            'file_name' => $fileName,
            'size' => $size,
            'success' => $success
        ]);
    }
    
    /**
     * Log de atualização do sistema
     */
    public function systemUpdate($oldVersion, $newVersion, $success = true) {
        $level = $success ? 'INFO' : 'ERROR';
        $message = $success
            ? "Sistema atualizado: {$oldVersion} -> {$newVersion}"
            : "Falha na atualização: {$oldVersion} -> {$newVersion}";
        
        $this->write($message, $level, [
            'old_version' => $oldVersion,
            'new_version' => $newVersion,
            'success' => $success
        ]);
    }
    
    /**
     * Limpar logs antigos
     */
    public function cleanOldLogs($daysToKeep = 30) {
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        $files = glob($this->logDir . 'system_*.log');
        $deleted = 0;
        
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/system_(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
                $logDate = $matches[1];
                if ($logDate < $cutoffDate) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        $this->info("Limpeza de logs: {$deleted} arquivos removidos");
        return $deleted;
    }
    
    /**
     * Obter estatísticas de log
     */
    public function getStats($date = null) {
        $targetDate = $date ?? $this->currentDate;
        $logFile = $this->logDir . 'system_' . $targetDate . '.log';
        
        if (!file_exists($logFile)) {
            return ['total' => 0, 'by_level' => []];
        }
        
        $content = file_get_contents($logFile);
        $lines = explode(PHP_EOL, trim($content));
        
        $stats = [
            'total' => count($lines),
            'by_level' => [
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0,
                'DEBUG' => 0
            ]
        ];
        
        foreach ($lines as $line) {
            foreach (array_keys($stats['by_level']) as $level) {
                if (strpos($line, "[{$level}]") !== false) {
                    $stats['by_level'][$level]++;
                    break;
                }
            }
        }
        
        return $stats;
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Função global para facilitar o uso
if (!function_exists('logger')) {
    function logger() {
        return Logger::getInstance();
    }
}
