<?php
/**
 * Arquivo de inclusão principal
 * Centraliza todos os includes necessários para o sistema
 */

// Carregar configuração principal
require_once __DIR__ . '/config.php';

// Carregar funções utilitárias adicionais
if (file_exists(BASE_PATH . '/functions.php')) {
    require_once BASE_PATH . '/functions.php';
}

// Verificar autenticação para páginas administrativas
function checkAuth() {
    if (!is_logged_in()) {
        redirect(BASE_URL . 'index.php');
    }
}

// Verificar se é administrador
function checkAdmin() {
    checkAuth();
    if (!is_admin()) {
        redirect(BASE_URL . 'home.php');
    }
}

// Verificar permissões específicas
function checkPermission($permission) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $user_permissions = $_SESSION['permissions'] ?? [];
    
    if (!in_array($permission, $user_permissions) && !is_admin()) {
        json_response(['error' => 'Permissão negada'], 403);
    }
}

// Middleware para APIs
function apiMiddleware() {
    header('Content-Type: application/json; charset=utf-8');
    
    // Permitir CORS para desenvolvimento
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    
    // Verificar autenticação para endpoints protegidos
    $public_endpoints = ['login.php', 'test.php'];
    $current_endpoint = basename($_SERVER['PHP_SELF']);
    
    if (!in_array($current_endpoint, $public_endpoints)) {
        if (!is_logged_in()) {
            json_response(['error' => 'Não autorizado'], 401);
        }
    }
}

// Template helper functions
function render_template($template, $data = []) {
    extract($data);
    $template_path = PATH_INCLUDES . 'templates/' . $template . '.php';
    
    if (file_exists($template_path)) {
        require_once $template_path;
    } else {
        throw new Exception("Template não encontrado: {$template}");
    }
}

function render_partial($partial, $data = []) {
    extract($data);
    $partial_path = PATH_INCLUDES . 'partials/' . $partial . '.php';
    
    if (file_exists($partial_path)) {
        require_once $partial_path;
    } else {
        system_log("Partial não encontrado: {$partial}", LOG_LEVEL_WARNING);
    }
}

// Funções de dashboard
function get_dashboard_stats() {
    $db = conectar_bd();
    
    if (!$db) {
        return null;
    }
    
    try {
        $stats = [];
        
        // Total de clientes
        $stmt = $db->query("SELECT COUNT(*) as total FROM clientes");
        $stats['total_clientes'] = $stmt->fetchColumn();
        
        // Clientes ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM clientes WHERE status = 1");
        $stats['clientes_ativos'] = $stmt->fetchColumn();
        
        // Total de revendedores
        $stmt = $db->query("SELECT COUNT(*) as total FROM admin WHERE admin = '1' OR admin = '2'");
        $stats['total_revendedores'] = $stmt->fetchColumn();
        
        // Canais totais
        $stmt = $db->query("SELECT COUNT(*) as total FROM streams WHERE type = 1");
        $stats['total_canais'] = $stmt->fetchColumn();
        
        // Filmes totais
        $stmt = $db->query("SELECT COUNT(*) as total FROM filmes");
        $stats['total_filmes'] = $stmt->fetchColumn();
        
        // Séries totais
        $stmt = $db->query("SELECT COUNT(*) as total FROM series");
        $stats['total_series'] = $stmt->fetchColumn();
        
        // Clientes online agora
        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as total FROM conexoes WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $stats['clientes_online'] = $stmt->fetchColumn();
        
        // Créditos totais
        $stmt = $db->query("SELECT SUM(creditos) as total FROM admin");
        $stats['total_creditos'] = $stmt->fetchColumn() ?? 0;
        
        return $stats;
        
    } catch (PDOException $e) {
        system_log('Erro ao buscar estatísticas do dashboard: ' . $e->getMessage(), LOG_LEVEL_ERROR);
        return null;
    }
}

// Notificações do sistema
function get_system_notifications() {
    $notifications = [];
    
    // Verificar atualizações
    $update = check_system_update();
    if ($update['available']) {
        $notifications[] = [
            'type' => 'info',
            'title' => 'Atualização Disponível',
            'message' => 'Nova versão disponível: ' . $update['version'],
            'icon' => 'fas fa-download'
        ];
    }
    
    // Verificar espaço em disco
    $free_space = disk_free_space(PATH_UPLOADS);
    $total_space = disk_total_space(PATH_UPLOADS);
    $used_percent = (($total_space - $free_space) / $total_space) * 100;
    
    if ($used_percent > 90) {
        $notifications[] = [
            'type' => 'danger',
            'title' => 'Espaço em Disco Crítico',
            'message' => 'Uso de disco: ' . number_format($used_percent, 2) . '%',
            'icon' => 'fas fa-hdd'
        ];
    } elseif ($used_percent > 75) {
        $notifications[] = [
            'type' => 'warning',
            'title' => 'Espaço em Disco Baixo',
            'message' => 'Uso de disco: ' . number_format($used_percent, 2) . '%',
            'icon' => 'fas fa-hdd'
        ];
    }
    
    // Verificar clientes vencendo hoje
    $db = conectar_bd();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM clientes WHERE data_vencimento = CURDATE() AND status = 1");
            $stmt->execute();
            $expirando_hoje = $stmt->fetchColumn();
            
            if ($expirando_hoje > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'title' => 'Clientes Vencendo Hoje',
                    'message' => "{$expirando_hoje} cliente(s) vencem hoje",
                    'icon' => 'fas fa-clock'
                ];
            }
        } catch (PDOException $e) {
            // Ignorar erro
        }
    }
    
    return $notifications;
}

// Breadcrumbs helper
function breadcrumbs($items) {
    echo '<nav aria-label="breadcrumb">';
    echo '<ol class="breadcrumb">';
    echo '<li class="breadcrumb-item"><a href="' . BASE_URL . 'home.php">Início</a></li>';
    
    foreach ($items as $item) {
        if (isset($item['url'])) {
            echo '<li class="breadcrumb-item"><a href="' . $item['url'] . '">' . $item['label'] . '</a></li>';
        } else {
            echo '<li class="breadcrumb-item active" aria-current="page">' . $item['label'] . '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

// Pagination helper
function render_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Anterior</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
    }
    
    // Pages
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // Next
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Próximo</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Próximo</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

// Export helpers
function export_to_csv($data, $filename = 'export.csv', $headers = null) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($headers) {
        fputcsv($output, $headers);
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function export_to_json($data, $filename = 'export.json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Security helpers
function sanitize_filename($filename) {
    // Remove qualquer caractere inseguro
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    // Previne directory traversal
    $filename = str_replace(['..', '/'], '', $filename);
    return $filename;
}

function validate_file_upload($file, $allowed_types = [], $max_size = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Erro no upload'];
    }
    
    // Validar tamanho
    if ($max_size && $file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Arquivo muito grande'];
    }
    
    // Validar tipo
    if (!empty($allowed_types)) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            return ['success' => false, 'error' => 'Tipo de arquivo não permitido'];
        }
    }
    
    // Validar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/x-matroska',
        'video/x-msvideo',
        'audio/mpeg',
        'application/octet-stream'
    ];
    
    if (!in_array($mime, $allowed_mimes) && strpos($mime, 'video/') === false) {
        return ['success' => false, 'error' => 'Tipo de conteúdo inválido'];
    }
    
    return ['success' => true];
}

// Rate limiting
function rate_limit($identifier, $limit = 100, $timeframe = 3600) {
    $db = conectar_bd();
    if (!$db) {
        return true;
    }
    
    try {
        $now = time();
        $window_start = $now - $timeframe;
        
        // Limpar registros antigos
        $db->prepare("DELETE FROM rate_limits WHERE timestamp < ?")->execute([$window_start]);
        
        // Contar requisições atuais
        $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND timestamp > ?");
        $stmt->execute([$identifier, $window_start]);
        $count = $stmt->fetchColumn();
        
        if ($count >= $limit) {
            return false;
        }
        
        // Registrar nova requisição
        $db->prepare("INSERT INTO rate_limits (identifier, timestamp) VALUES (?, ?)")
           ->execute([$identifier, $now]);
        
        return true;
        
    } catch (PDOException $e) {
        system_log('Erro no rate limiting: ' . $e->getMessage(), LOG_LEVEL_ERROR);
        return true; // Fail open
    }
}

// Cache helpers
function cache_get($key, $ttl = 3600) {
    $cache_file = PATH_UPLOADS . 'cache/' . md5($key) . '.cache';
    
    if (file_exists($cache_file)) {
        $data = unserialize(file_get_contents($cache_file));
        
        if ($data && (time() - $data['timestamp']) < $ttl) {
            return $data['value'];
        }
        
        // Cache expirado
        @unlink($cache_file);
    }
    
    return false;
}

function cache_set($key, $value, $ttl = 3600) {
    $cache_dir = PATH_UPLOADS . 'cache/';
    
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . md5($key) . '.cache';
    $data = [
        'timestamp' => time(),
        'value' => $value
    ];
    
    file_put_contents($cache_file, serialize($data));
}

function cache_delete($key) {
    $cache_file = PATH_UPLOADS . 'cache/' . md5($key) . '.cache';
    
    if (file_exists($cache_file)) {
        @unlink($cache_file);
    }
}

function cache_clear() {
    $cache_dir = PATH_UPLOADS . 'cache/';
    
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}

system_log('Includes carregados com sucesso', LOG_LEVEL_DEBUG);
