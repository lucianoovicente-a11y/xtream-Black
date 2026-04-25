<?php
// ======================================================================
//  API M3U - Lista de canais com URLs de streaming
// ======================================================================

error_reporting(0);
ini_set('display_errors', 0);

$username = $_GET['username'] ?? $_GET['user'] ?? null;
$password = $_GET['password'] ?? $_GET['pass'] ?? null;
$output = $_GET['output'] ?? $_GET['type'] ?? 'm3u8';

if (!$username || !$password) {
    header('Content-Type: application/vnd.apple.mpegurl');
    echo "#EXTM3U\n#EXTINF:-1,Tela de Login\nhttp://localhost/error?msg=Credenciais+invalidas\n";
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
date_default_timezone_set('America/Sao_Paulo');

$base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

try {
    $conexao = conectar_bd();
    
    // Autenticar
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE usuario = :username AND senha = :password");
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':password', $password);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        header('Content-Type: application/vnd.apple.mpegurl');
        echo "#EXTM3U\n#EXTINF:-1,Credenciais Invalidas\nhttp://localhost/error?msg=Credenciais+invalidas\n";
        exit;
    }
    
    // Verificar vencimento
    if (strtotime($cliente['Vencimento']) < time()) {
        header('Content-Type: application/vnd.apple.mpegurl');
        echo "#EXTM3U\n#EXTINF:-1,Assinatura Vencdida\nhttp://localhost/error?msg=Assinatura+vencida\n";
        exit;
    }
    
    // Buscar streams (apenas live)
    $query = "SELECT s.*, c.nome as categoria_nome FROM streams s LEFT JOIN categoria c ON s.category_id = c.id WHERE s.stream_type = 'live'";
    $params = [];
    
    // Filtrar adulto
    if ($cliente['adulto'] == 0) {
        $query .= " AND c.is_adult = 0";
    }
    
    // Filtrar por bouquet se existir
    if (!empty($cliente['bouquet_id'])) {
        $query .= " AND s.category_id IN (SELECT category_id FROM bouquet_categories WHERE bouquet_id = ?)";
        $params[] = $cliente['bouquet_id'];
    }
    
    $query .= " ORDER BY c.nome, s.name";
    
    $stmt_streams = $conexao->prepare($query);
    $stmt_streams->execute($params);
    $streams = $stmt_streams->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar M3U
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Content-Disposition: attachment; filename="playlist.m3u"');
    
    echo "#EXTM3U\n";
    
    foreach ($streams as $stream) {
        $nome = $stream['name'];
        $categoria = $stream['categoria_nome'] ?? 'Sem Categoria';
        
        // URL de streaming usando o proxy local
        $stream_url = $stream['stream_url'] ?? '';
        
        if (!empty($stream_url)) {
            // Se for URL externa, usar proxy
            if (strpos($stream_url, $_SERVER['HTTP_HOST']) === false && strpos($stream_url, 'localhost') === false) {
                // URL externa - criar URL de streaming com proxy
                $encoded_url = base64_encode($stream_url);
                $url_stream = $base_url . '/api/stream_proxy.php?url=' . $encoded_url . '&user=' . $username . '&pass=' . $password . '&id=' . $stream['id'];
            } else {
                // URL local
                $url_stream = $stream_url;
            }
            
            echo "#EXTINF:-1 tvg-name=\"{$nome}\" tvg-logo=\"{$stream['stream_icon']}\" group-title=\"{$categoria}\",{$nome}\n";
            echo $url_stream . "\n";
        }
    }
    
} catch (Exception $e) {
    header('Content-Type: application/vnd.apple.mpegurl');
    echo "#EXTM3U\n#EXTINF:-1,Erro no Servidor\nhttp://localhost/error?msg=" . urlencode($e->getMessage()) . "\n";
}
?>