<?php
/**
 * Configuração principal do sistema XTREAM SERVER OPENSOURCE
 * Este arquivo centraliza todas as configurações do sistema
 */

// Define o caminho base do projeto
define('BASE_PATH', dirname(__DIR__));

// Define a URL base do sistema
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
             (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/');

// Configurações de exibição de erros (ajustar conforme ambiente)
if (file_exists(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env');
    define('DEBUG_MODE', isset($env['DEBUG']) ? filter_var($env['DEBUG'], FILTER_VALIDATE_BOOLEAN) : false);
} else {
    define('DEBUG_MODE', true); // Modo debug ativo por padrão
}

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/php_errors.log');
}

// Fuso horário padrão
date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 3600);

// Limites de upload
ini_set('upload_max_filesize', '2G');
ini_set('post_max_size', '2G');
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');
ini_set('memory_limit', '512M');

// Constantes do sistema
define('SYSTEM_NAME', 'XTREAM SERVER OPENSOURCE');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_AUTHOR', '@FURIA401');
define('DEFAULT_CREDITS', 0);
define('DEFAULT_PLAN', 4);

// Caminhos importantes
define('PATH_UPLOADS', BASE_PATH . '/uploads/');
define('PATH_BACKUP', BASE_PATH . '/backup/');
define('PATH_LOGS', BASE_PATH . '/logs/');
define('PATH_CLASSES', BASE_PATH . '/classes/');
define('PATH_INCLUDES', BASE_PATH . '/includes/');
define('PATH_API', BASE_PATH . '/api/');
define('PATH_CONTROLES', BASE_PATH . '/api/controles/');

// Tipos de conteúdo permitidos para upload
define('ALLOWED_EXTENSIONS', [
    'video' => ['mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm'],
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'audio' => ['mp3', 'aac', 'ogg', 'wav', 'flac'],
    'playlist' => ['m3u', 'm3u8', 'txt'],
    'archive' => ['zip', 'rar', 'tar', 'gz']
]);

// Níveis de revendedor
define('RESELLER_LEVELS', [
    'master' => 1,
    'revenda' => 2,
    'sub_revenda' => 3
]);

// Status dos clientes
define('CLIENT_STATUS', [
    'active' => 1,
    'inactive' => 0,
    'trial' => 2,
    'blocked' => 3,
    'expired' => 4
]);

// Tipos de stream
define('STREAM_TYPES', [
    'live' => 1,
    'vod' => 2,
    'series' => 3
]);

// Configurações de segurança
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos
define('SESSION_TIMEOUT', 3600); // 1 hora
define('PASSWORD_MIN_LENGTH', 6);

// APIs externas (configurar quando necessário)
define('TMDB_API_KEY', ''); // Chave da API TMDB
define('TMDB_API_URL', 'https://api.themoviedb.org/3/');

// Mercado Pago (configurar quando necessário)
define('MP_SANDBOX_MODE', true);

// P2P Configuration
define('P2P_ENABLED', true);
define('P2P_MAX_CONNECTIONS', 10);
define('P2P_TIMEOUT', 30);

// EPG Settings
define('EPG_CACHE_TIME', 3600); // 1 hora
define('EPG_MAX_DAYS', 7);

// Log levels
define('LOG_LEVEL_ERROR', 1);
define('LOG_LEVEL_WARNING', 2);
define('LOG_LEVEL_INFO', 3);
define('LOG_LEVEL_DEBUG', 4);

/**
 * Função para carregar classes automaticamente
 */
spl_autoload_register(function($class) {
    $paths = [
        PATH_CLASSES,
        PATH_INCLUDES . 'classes/',
        BASE_PATH . '/P2P6.2/classes/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.class.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

/**
 * Função helper para debug (dump e die)
 */
if (!function_exists('dd')) {
    function dd(...$vars) {
        echo '<pre style="background: #222; color: #0f0; padding: 10px; border-radius: 5px; font-family: monospace;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}

/**
 * Função para logging
 */
if (!function_exists('system_log')) {
    function system_log($message, $level = LOG_LEVEL_INFO, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $level_names = [
            LOG_LEVEL_ERROR => 'ERROR',
            LOG_LEVEL_WARNING => 'WARNING',
            LOG_LEVEL_INFO => 'INFO',
            LOG_LEVEL_DEBUG => 'DEBUG'
        ];
        
        $level_name = $level_names[$level] ?? 'INFO';
        $log_message = sprintf("[%s] [%s] %s", $timestamp, $level_name, $message);
        
        if (!empty($context)) {
            $log_message .= ' ' . json_encode($context);
        }
        
        $log_file = PATH_LOGS . 'system_' . date('Y-m-d') . '.log';
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
    }
}

/**
 * Função para sanitizar inputs
 */
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Função para verificar se o usuário está logado
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

/**
 * Função para redirecionar
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit;
    }
}

/**
 * Função para resposta JSON
 */
if (!function_exists('json_response')) {
    function json_response($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

/**
 * Função para validar token CSRF
 */
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Função para gerar token CSRF
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

/**
 * Função para formatar bytes
 */
if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * Função para formatar data
 */
if (!function_exists('format_date')) {
    function format_date($date, $format = 'd/m/Y H:i:s') {
        if (empty($date)) {
            return '-';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}

/**
 * Função para calcular diferença de datas
 */
if (!function_exists('date_diff_days')) {
    function date_diff_days($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return $interval->days;
    }
}

/**
 * Função para verificar permissão de administrador
 */
if (!function_exists('is_admin')) {
    function is_admin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin']) && $_SESSION['admin'] == '1';
    }
}

/**
 * Função para verificar permissão de revendedor
 */
if (!function_exists('is_reseller')) {
    function is_reseller() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['revendedor']) && $_SESSION['revendedor'] == '1';
    }
}

/**
 * Função para obter IP do cliente
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

/**
 * Função para limpar cache
 */
if (!function_exists('clear_cache')) {
    function clear_cache($type = 'all') {
        $cache_dir = PATH_UPLOADS . 'cache/';
        
        if (!is_dir($cache_dir)) {
            return false;
        }
        
        $files = glob($cache_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($type === 'all' || strpos($file, $type) !== false) {
                    unlink($file);
                }
            }
        }
        
        return true;
    }
}

/**
 * Função para criptografar senha
 */
if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

/**
 * Função para verificar senha
 */
if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

/**
 * Função para gerar senha aleatória
 */
if (!function_exists('generate_random_password')) {
    function generate_random_password($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        $password = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
}

/**
 * Função para encurtar texto
 */
if (!function_exists('truncate')) {
    function truncate($text, $length = 50, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
}

/**
 * Função para slugify
 */
if (!function_exists('slugify')) {
    function slugify($text) {
        $text = preg_replace('~[^\p{Latin}\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
}

/**
 * Função para validar email
 */
if (!function_exists('valid_email')) {
    function valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Função para validar URL
 */
if (!function_exists('valid_url')) {
    function valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

/**
 * Função para download de arquivo remoto
 */
if (!function_exists('download_remote_file')) {
    function download_remote_file($url, $destination) {
        $ch = curl_init($url);
        $fp = fopen($destination, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        return $success;
    }
}

/**
 * Função para obter informações de arquivo M3U
 */
if (!function_exists('parse_m3u_info')) {
    function parse_m3u_info($content) {
        $info = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, '#EXTINF:') === 0) {
                $current = [];
                
                // Extrair nome
                if (preg_match('/,(.+)$/', $line, $matches)) {
                    $current['name'] = trim($matches[1]);
                }
                
                // Extrair tvg-id
                if (preg_match('/tvg-id="([^"]+)"/', $line, $matches)) {
                    $current['tvg_id'] = $matches[1];
                }
                
                // Extrair tvg-name
                if (preg_match('/tvg-name="([^"]+)"/', $line, $matches)) {
                    $current['tvg_name'] = $matches[1];
                }
                
                // Extrair tvg-logo
                if (preg_match('/tvg-logo="([^"]+)"/', $line, $matches)) {
                    $current['tvg_logo'] = $matches[1];
                }
                
                // Extrair group-title
                if (preg_match('/group-title="([^"]+)"/', $line, $matches)) {
                    $current['group_title'] = $matches[1];
                }
                
                $info[] = $current;
            }
        }
        
        return $info;
    }
}

/**
 * Função para verificar atualização do sistema
 */
if (!function_exists('check_system_update')) {
    function check_system_update() {
        $current_version = SYSTEM_VERSION;
        $update_url = BASE_URL . 'api/check_update.php';
        
        try {
            $response = @file_get_contents($update_url);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['version']) && version_compare($data['version'], $current_version, '>')) {
                    return [
                        'available' => true,
                        'version' => $data['version'],
                        'changelog' => $data['changelog'] ?? '',
                        'download_url' => $data['download_url'] ?? ''
                    ];
                }
            }
        } catch (Exception $e) {
            system_log('Erro ao verificar atualização: ' . $e->getMessage(), LOG_LEVEL_ERROR);
        }
        
        return ['available' => false];
    }
}

// Iniciar sessão automaticamente se não estiver iniciada
if (session_status() === PHP_SESSION_NONE && !defined('SKIP_AUTO_SESSION')) {
    session_start();
}

// Carregar configuração do banco de dados
require_once PATH_CONTROLES . 'db.php';

system_log('Sistema inicializado com sucesso', LOG_LEVEL_INFO);
