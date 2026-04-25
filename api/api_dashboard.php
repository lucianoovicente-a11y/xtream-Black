<?php
ob_start();
header('Content-Type: application/json');
header('Connection: close');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('America/Sao_Paulo');

function sendFinalResponse($conn, $response) {
    if ($conn) {}
    ob_end_clean();
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

$response = [
    'online_count' => 0,
    'multi_connection_count' => 0,
    'activity' => []
];
$conn = null;

try {
    $conn_pdo = conectar_bd();
    if (!$conn_pdo) {
        throw new Exception("Falha ao obter conexão com o banco de dados.");
    }
    $conn = $conn_pdo;
} catch (Exception $e) {
    $response['error'] = 'Falha na conexão com o DB: ' . $e->getMessage();
    sendFinalResponse(null, $response);
}

// Buscar todas as conexões
$sql = "SELECT 
            c.usuario, 
            c.ip, 
            c.ultima_atividade, 
            c.user_agent, 
            c.id,
            c.canal_atual AS canal_id,
            COALESCE(c.serie_nome, '') AS serie_nome,
            c.tipo_stream,
            (SELECT COUNT(id) FROM conexoes WHERE usuario = c.usuario) AS conexoes_total
        FROM conexoes AS c
        ORDER BY c.ultima_atividade DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $response['error'] = 'Erro SQL: ' . $e->getMessage();
    sendFinalResponse($conn, $response);
}

$activity = [];
$counted_multi = [];
$multi_connection_users = 0;
$now = new DateTime();

foreach($result as $row) {
    $ultima_atividade_formatada = 'N/A';
    $tempo_online = '0m';
    
    if (!empty($row['ultima_atividade'])) {
        try {
            $db_datetime = new DateTime($row['ultima_atividade']);
            $ultima_atividade_formatada = $db_datetime->format('d/m H:i:s');
            
            $diff = $now->diff($db_datetime);
            $tempo_parts = [];
            if ($diff->d > 0) $tempo_parts[] = $diff->d . 'd';
            if ($diff->h > 0) $tempo_parts[] = $diff->h . 'h';
            if ($diff->i > 0) $tempo_parts[] = $diff->i . 'm';
            if (empty($tempo_parts)) $tempo_parts[] = '<1m';
            $tempo_online = implode(' ', $tempo_parts);
        } catch (Exception $e) {
            $ultima_atividade_formatada = 'Erro';
        }
    }
    
    $canal_id = $row['canal_id'];
    $tipo_stream = $row['tipo_stream'] ?? 'live';
    $nome_conteudo = 'Menu Principal';
    $tipo_conteudo = 'Desconhecido';
    $stream_icon = '';
    
    // Se é episódio de série
    if (!empty($row['serie_nome'])) {
        $nome_conteudo = $row['serie_nome'];
        $tipo_conteudo = 'Serie';
    }
    // Se tem canal_id, buscar nome
    elseif (!empty($canal_id) && $canal_id > 0) {
        // Primeiro tenta buscar na tabela streams
        $stmt_stream = $conn->prepare("SELECT name, stream_icon, stream_type FROM streams WHERE id = ?");
        $stmt_stream->execute([$canal_id]);
        $stream = $stmt_stream->fetch(PDO::FETCH_ASSOC);
        
        if ($stream) {
            if ($stream['stream_type'] == 'movie') {
                $nome_conteudo = $stream['name'];
                $tipo_conteudo = 'Filme';
                $stream_icon = $stream['stream_icon'];
            } else {
                $nome_conteudo = $stream['name'];
                $tipo_conteudo = 'Ao Vivo';
                $stream_icon = $stream['stream_icon'];
            }
        } else {
            // Se não encontrar, pode ser episódio
            $stmt_ep = $conn->prepare("SELECT title, movie_image FROM series_episodes WHERE id = ?");
            $stmt_ep->execute([$canal_id]);
            $episodio = $stmt_ep->fetch(PDO::FETCH_ASSOC);
            
            if ($episodio) {
                $nome_conteudo = $episodio['title'] ?: 'Episódio #' . $canal_id;
                $tipo_conteudo = 'Serie';
                $stream_icon = $episodio['movie_image'] ?? '';
            } else {
                $nome_conteudo = 'Canal #' . $canal_id;
                $tipo_conteudo = 'Ao Vivo';
            }
        }
    }
    
    $statusInativos = ["Menu Principal", "Nenhum canal ativo", "N/A", "Menu/Offline", "menu", "offline", ""];
    $is_watching = !in_array($nome_conteudo, $statusInativos) && !empty($row['canal_id']);
    
    $activity[] = [
        'id' => $row['id'],
        'usuario' => $row['usuario'],
        'ip' => $row['ip'],
        'canal_atual' => $nome_conteudo,
        'canal_icon' => $stream_icon,
        'tipo_conteudo' => $tipo_conteudo,
        'is_watching' => $is_watching,
        'ultima_atividade' => $ultima_atividade_formatada,
        'tempo_online' => $tempo_online,
        'hora_conexao' => $row['ultima_atividade'],
        'user_agent' => $row['user_agent'],
        'conexoes_total' => $row['conexoes_total']
    ];
    
    if ($row['conexoes_total'] > 1 && !isset($counted_multi[$row['usuario']])) {
        $multi_connection_users++;
        $counted_multi[$row['usuario']] = true;
    }
}

$response['online_count'] = count($activity);
$response['multi_connection_count'] = $multi_connection_users;
$response['activity'] = $activity;

sendFinalResponse($conn, $response);