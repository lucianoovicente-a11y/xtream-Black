<?php
// === LIGA O OUTPUT BUFFER NO INÍCIO E GARANTE A LIMPEZA ===
ob_start();

header('Content-Type: application/json');
header('Connection: close');

// Desativa a exibição de erros no navegador para segurança
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ==========================================================
// 1. DEFINE O FUSO HORÁRIO PARA BRASÍLIA (UTC-3) - APENAS PARA FORMATAR
// ==========================================================
date_default_timezone_set('America/Sao_Paulo'); 

// ==========================================================
// FUNÇÃO PARA ENVIAR RESPOSTA E PARAR O SCRIPT
// Adaptada para aceitar o objeto PDO, mantendo a estrutura.
// ==========================================================
function sendFinalResponse($conn, $response) {
    // Em PDO, não é necessário chamar ->close(), mas mantemos a estrutura para evitar erros
    // caso $conn seja um objeto.
    if ($conn) {
        // Nada a fazer aqui para PDO.
    }
    ob_end_clean();
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// ==========================================================
// MUDANÇA PRINCIPAL: INCLUI O ARQUIVO db.php CENTRALIZADO
// ==========================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');

// ==========================================================
// 2. CONFIGURAÇÕES E CONEXÃO: USA A FUNÇÃO CENTRAL conectar_bd() (PDO)
// ==========================================================

$response = [
    'online_count' => 0,
    'multi_connection_count' => 0,
    'activity' => []
];
$conn = null;

// Tenta obter a conexão PDO
try {
    $conn_pdo = conectar_bd();
    if (!$conn_pdo) {
        // Se a função retornar NULL/false
        throw new Exception("Falha ao obter conexão com o banco de dados.");
    }
    // $conn agora é o objeto PDO.
    $conn = $conn_pdo; 
} catch (Exception $e) {
    // Captura erros de conexão ou da função
    $response['error'] = 'Falha na conexão com o DB: ' . $e->getMessage();
    sendFinalResponse(null, $response);
}

// ==========================================================
// 3. CONSULTA SQL ÚNICA - ADAPTAÇÃO PARA PDO
// ==========================================================
$sql = "SELECT 
            c.usuario, 
            c.ip, 
            c.ultima_atividade, 
            c.user_agent, 
            c.id,
            c.canal_atual AS id_conteudo_ativo,
            COALESCE(
                c.serie_nome,      
                s.name,            
                CONCAT('ID: ', c.canal_atual) 
            ) AS canal_atual_display, 
            (SELECT COUNT(id) FROM conexoes WHERE usuario = c.usuario) AS conexoes_total
        FROM conexoes AS c
        LEFT JOIN streams AS s ON c.canal_atual = s.id
        ORDER BY c.ultima_atividade DESC";

// PDO usa prepare/execute para executar a consulta
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    // Obtém todos os resultados de uma vez como um array associativo
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $response['error'] = 'Erro ao executar a consulta SQL. Mensagem do DB: ' . $e->getMessage();
    sendFinalResponse($conn, $response);
}

// ==========================================================
// 4. PROCESSAMENTO E RESPOSTA DOS DADOS - ADAPTADO PARA ITEAR O ARRAY PDO
// ==========================================================
$activity = [];
$counted_multi = [];
$multi_connection_users = 0;

// Itera sobre o array de resultados (PDO)
foreach($result as $row) {
    
    $ultima_atividade_formatada = 'N/A';
    if (!empty($row['ultima_atividade'])) {
        try {
            // ==========================================================
            // CÓDIGO ORIGINAL DE CORREÇÃO DE DATA/FUSO
            // ==========================================================
            $db_datetime = new DateTime($row['ultima_atividade']); 
            
            // Subtrai 3 horas (P3H = Period 3 Hours)
            $db_datetime->sub(new DateInterval('PT3H')); 
            
            $ultima_atividade_formatada = $db_datetime->format('d/m/Y H:i:s'); 

        } catch (Exception $e) {
            $ultima_atividade_formatada = 'Erro de Data';
        }
    }
    
    $activity[] = [
        'id' => $row['id'],
        'usuario' => $row['usuario'],
        'ip' => $row['ip'],
        'canal_atual' => $row['canal_atual_display'], 
        'ultima_atividade' => $ultima_atividade_formatada, // O valor corrigido
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

// === ENVIA A RESPOSTA FINAL E ENCERRA ===
sendFinalResponse($conn, $response);