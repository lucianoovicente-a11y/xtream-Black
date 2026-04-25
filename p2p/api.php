<?php
// ======================================================================
//  STREAMFLOW P2P API - utrafix.qualidade.cloud
//  Endpoints para autenticação e gerenciamento de usuários P2P
// ======================================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../api/controles/db.php';

define('STREAMFLOW_HOST', 'https://utrafix.qualidade.cloud');
define('STREAMFLOW_P2P_HOST', 'https://utrafix.qualidade.cloud/p2p');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

$conexao = conectar_bd();

// ======================================================================
// ROTAS DA API
// ======================================================================

switch ($path) {
    // ==================================================================
    // AUTH - Autenticar usuário P2P
    // ==================================================================
    case 'auth':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(['status' => false, 'message' => 'Usuário ou senha vazios']);
                exit;
            }
            
            // Buscar usuário P2P
            $stmt = $conexao->prepare("
                SELECT id, usuario, senha, name, Vencimento, plano, conexoes 
                FROM clientes 
                WHERE usuario = :usuario AND is_p2p = 1
            ");
            $stmt->execute([':usuario' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['senha'] === $password) {
                // Verificar se venceu
                $hoje = date('Y-m-d H:i:s');
                if (strtotime($user['Vencimento']) < strtotime($hoje)) {
                    echo json_encode(['status' => false, 'message' => 'Acesso expirado']);
                    exit;
                }
                
                // Buscar categorias/bouquets do plano
                $stmt_cat = $conexao->prepare("SELECT category_id FROM stream_categories ORDER BY id");
                $stmt_cat->execute();
                $categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
                
                $stmt_bouquet = $conexao->prepare("SELECT id, bouquet_name FROM bouquets");
                $stmt_bouquet->execute();
                $bouquets = $stmt_bouquet->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => true,
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['usuario'],
                        'name' => $user['name'],
                        'exp_date' => strtotime($user['Vencimento']),
                        'max_connections' => (int)$user['conexoes'],
                        'is_trial' => 0
                    ],
                    'categories' => $categories,
                    'bouquets' => array_column($bouquets, 'bouquet_name'),
                    'message' => 'OK'
                ]);
            } else {
                echo json_encode(['status' => false, 'message' => 'Credenciais inválidas']);
            }
        }
        break;
    
    // ==================================================================
    // REGISTER - Registrar novo usuário P2P
    // ==================================================================
    case 'register':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $name = $input['name'] ?? '';
            
            if (empty($username) || empty($password)) {
                echo json_encode(['status' => false, 'message' => 'Dados obrigatórios']);
                exit;
            }
            
            // Verificar se usuário já existe
            $stmt_check = $conexao->prepare("SELECT id FROM clientes WHERE usuario = :usuario");
            $stmt_check->execute([':usuario' => $username]);
            
            if ($stmt_check->fetch()) {
                echo json_encode(['status' => false, 'message' => 'Usuário já existe']);
                exit;
            }
            
            // Buscar senha padrão P2P
            $stmt_pass = $conexao->prepare("SELECT setting_value FROM settings WHERE setting_name = 'p2p_default_password'");
            $stmt_pass->execute();
            $default_pass = $stmt_pass->fetchColumn() ?: '1122334455';
            
            // Inserir novo usuário
            $vencimento = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt_insert = $conexao->prepare("
                INSERT INTO clientes (usuario, senha, name, plano, is_p2p, Criado_em, Vencimento, conexoes, admin_id)
                VALUES (:usuario, :senha, :name, 'P2P', 1, NOW(), :vencimento, 1, :admin_id)
            ");
            $stmt_insert->execute([
                ':usuario' => $username,
                ':senha' => $default_pass,
                ':name' => $name ?: $username,
                ':vencimento' => $vencimento,
                ':admin_id' => $_SESSION['admin_id'] ?? 1
            ]);
            
            echo json_encode([
                'status' => true,
                'user_id' => $conexao->lastInsertId(),
                'username' => $username,
                'password' => $default_pass,
                'exp_date' => strtotime($vencimento),
                'message' => 'Usuário P2P criado com sucesso!'
            ]);
        }
        break;
    
    // ==================================================================
    // USER INFO - Informações do usuário
    // ==================================================================
    case 'user_info':
        if ($method === 'GET') {
            $username = $_GET['username'] ?? '';
            
            $stmt = $conexao->prepare("
                SELECT id, usuario, name, Vencimento, plano, conexoes 
                FROM clientes 
                WHERE usuario = :usuario AND is_p2p = 1
            ");
            $stmt->execute([':usuario' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo json_encode([
                    'status' => true,
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['usuario'],
                        'name' => $user['name'],
                        'exp_date' => strtotime($user['Vencimento']),
                        'max_connections' => (int)$user['conexoes'],
                        'plan' => $user['plano']
                    ]
                ]);
            } else {
                echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
            }
        }
        break;
    
    // ==================================================================
    // RENEW - Renovar usuário
    // ==================================================================
    case 'renew':
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $username = $input['username'] ?? '';
            $days = $input['days'] ?? 30;
            
            $stmt = $conexao->prepare("
                UPDATE clientes 
                SET Vencimento = DATE_ADD(Vencimento, INTERVAL :days DAY)
                WHERE usuario = :usuario AND is_p2p = 1
            ");
            $stmt->execute([
                ':usuario' => $username,
                ':days' => (int)$days
            ]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => true, 'message' => 'Renovado com sucesso!']);
            } else {
                echo json_encode(['status' => false, 'message' => 'Usuário não encontrado']);
            }
        }
        break;
    
    // ==================================================================
    // SERVERS LIST - Lista de servidores
    // ==================================================================
    case 'servers':
        echo json_encode([
            'status' => true,
            'servers' => [
                [
                    'id' => 1,
                    'name' => 'Principal',
                    'url' => STREAMFLOW_HOST,
                    'port' => 80,
                    'https_port' => 443,
                    'status' => 'online'
                ]
            ]
        ]);
        break;
    
    // ==================================================================
    // STREAM AUTH - Autenticar stream
    // ==================================================================
    case 'stream_auth':
        if ($method === 'GET') {
            $username = $_GET['username'] ?? '';
            $password = $_GET['password'] ?? '';
            $stream_id = $_GET['stream_id'] ?? 0;
            
            $stmt = $conexao->prepare("
                SELECT id FROM clientes 
                WHERE usuario = :usuario AND senha = :senha AND is_p2p = 1
            ");
            $stmt->execute([':usuario' => $username, ':senha' => $password]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Proxy para o stream
                $stmt_stream = $conexao->prepare("SELECT * FROM streams WHERE id = :id");
                $stmt_stream->execute([':id' => $stream_id]);
                $stream = $stmt_stream->fetch(PDO::FETCH_ASSOC);
                
                if ($stream && !empty($stream['link'])) {
                    echo json_encode([
                        'status' => true,
                        'stream_url' => STREAMFLOW_HOST . '/get.php?username=' . $username . '&password=' . $password . '&type=m3u_plus&id=' . $stream_id
                    ]);
                } else {
                    echo json_encode(['status' => false, 'message' => 'Stream não encontrado']);
                }
            } else {
                echo json_encode(['status' => false, 'message' => 'Acesso negado']);
            }
        }
        break;
    
    // ==================================================================
    // DEFAULT - Endpoint não encontrado
    // ==================================================================
    default:
        echo json_encode([
            'status' => false,
            'message' => 'Endpoint não encontrado',
            'available_endpoints' => [
                'POST /api/auth - Autenticar usuário',
                'POST /api/register - Registrar usuário',
                'GET /api/user_info - Informações do usuário',
                'POST /api/renew - Renovar acesso',
                'GET /api/servers - Lista de servidores',
                'GET /api/stream_auth - Autenticar stream'
            ]
        ]);
        break;
}
?>