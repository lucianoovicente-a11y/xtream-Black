<?php
// ======================================================================
//  API DE STREAMING - Proxy para evitar CORS e adicionar headers
// ======================================================================

error_reporting(0);
ini_set('display_errors', 0);

$stream_id = $_GET['stream_id'] ?? $_GET['id'] ?? null;
$username = $_GET['username'] ?? $_GET['user'] ?? null;
$password = $_GET['password'] ?? $_GET['pass'] ?? null;

if (!$stream_id || !$username || !$password) {
    http_response_code(400);
    die("Parâmetros inválidos");
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
date_default_timezone_set('America/Sao_Paulo');

try {
    $conexao = conectar_bd();
    
    // Autenticar usuário
    $stmt_auth = $conexao->prepare("SELECT * FROM clientes WHERE usuario = :username AND senha = :password");
    $stmt_auth->bindValue(':username', $username);
    $stmt_auth->bindValue(':password', $password);
    $stmt_auth->execute();
    $cliente_info = $stmt_auth->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente_info) {
        http_response_code(401);
        die("Credenciais inválidas");
    }
    
    // Verificar se está ativo
    $exp_date = strtotime($cliente_info['Vencimento']);
    if ($exp_date < time()) {
        http_response_code(403);
        die("Assinatura vencida");
    }
    
    // Buscar o stream
    $stmt_stream = $conexao->prepare("SELECT * FROM streams WHERE id = :stream_id");
    $stmt_stream->bindValue(':stream_id', $stream_id);
    $stmt_stream->execute();
    $stream = $stmt_stream->fetch(PDO::FETCH_ASSOC);
    
    if (!$stream) {
        http_response_code(404);
        die("Stream não encontrado");
    }
    
    // Verificar adulto
    $is_adult = isset($_GET['adulto']) ? (int)$_GET['adulto'] : $cliente_info['adulto'];
    if ($is_adult == 0 && !empty($stream['category_id'])) {
        $stmt_cat = $conexao->prepare("SELECT is_adult FROM categoria WHERE id = ?");
        $stmt_cat->execute([$stream['category_id']]);
        $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
        if ($categoria && $categoria['is_adult'] == 1) {
            http_response_code(403);
            die("Conteúdo adulto - acesso não permitido");
        }
    }
    
    $stream_url = $stream['stream_url'];
    
    if (empty($stream_url)) {
        http_response_code(404);
        die("URL do stream não configurada");
    }
    
    // Atualizar última atividade
    $stmt_update = $conexao->prepare("UPDATE conexoes SET canal_atual = :stream_id, ultima_atividade = NOW() WHERE usuario = :username ORDER BY id DESC LIMIT 1");
    $stmt_update->bindValue(':stream_id', $stream_id);
    $stmt_update->bindValue(':username', $username);
    $stmt_update->execute();
    
    // Detectar tipo de stream e configurar headers
    $extensao = strtolower(pathinfo($stream_url, PATHINFO_EXTENSION));
    
    // Headers para evitar CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    
    // Detectar tipo de conteúdo
    if ($extensao === 'm3u8' || strpos($stream_url, '.m3u8') !== false) {
        header('Content-Type: application/vnd.apple.mpegurl');
    } elseif ($extensao === 'ts') {
        header('Content-Type: video/mp2ts');
    } elseif ($extensao === 'mp4') {
        header('Content-Type: video/mp4');
    } else {
        header('Content-Type: application/octet-stream');
    }
    
    // Se for um proxy local, fazer o proxy
    if (strpos($stream_url, $_SERVER['HTTP_HOST']) !== false || strpos($stream_url, 'localhost') !== false) {
        // É stream local, exibir diretamente
        header('Location: ' . $stream_url);
        exit;
    }
    
    // Headers para simular navegador/app
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36';
    
    // Iniciar proxy
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $stream_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: */*',
        'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'Origin: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
        'Referer: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/'
    ]);
    
    // Proxy de vídeo
    curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error || $http_code >= 400) {
        http_response_code($http_code ?: 502);
        die("Erro ao conectar ao stream: " . ($error ?: "HTTP $http_code"));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    die("Erro interno: " . $e->getMessage());
}
?>