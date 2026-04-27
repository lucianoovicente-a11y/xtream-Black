<?php
/**
 * DESENVOLVIDO POR Luciano Vicente - 21971877485
 * Ação de autenticação de login
 */
session_start();

require("../../includes/config.php");
require("../../autoload.php");

header('Content-Type: application/json');

if (!isset($_POST['email']) || !isset($_POST['password'])) {
    echo json_encode(["status" => false, "msg" => "Email e senha são obrigatórios!"]);
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

if (empty($email)) {
    echo json_encode(["status" => false, "msg" => "O campo email não pode estar em branco!"]);
    exit;
}

if (empty($password)) {
    echo json_encode(["status" => false, "msg" => "O campo senha não pode estar em branco!"]);
    exit;
}

try {
    $pdo = conectar_bd();
    
    if ($pdo === null) {
        echo json_encode(["status" => false, "msg" => "Erro de conexão com o banco de dados!"]);
        exit;
    }
    
    // Verifica se é email ou username
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM usuarios WHERE email = :login AND status = 1 LIMIT 1";
    } else {
        $sql = "SELECT * FROM usuarios WHERE usuario = :login AND status = 1 LIMIT 1";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':login', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(["status" => false, "msg" => "Usuário não encontrado ou inativo!"]);
        exit;
    }
    
    // Verifica senha (pode estar em diferentes formatos)
    $senha_valida = false;
    
    // Tenta verificar senha hash crypt
    $hash_crypt = crypt($password, '$6$rounds=20000$i9teamp2panel$');
    if ($hash_crypt === $user['senha']) {
        $senha_valida = true;
    }
    
    // Tenta verificar senha MD5
    if (!$senha_valida && md5($password) === $user['senha']) {
        $senha_valida = true;
    }
    
    // Tenta verificar senha pura (fallback)
    if (!$senha_valida && $password === $user['senha']) {
        $senha_valida = true;
    }
    
    if (!$senha_valida) {
        echo json_encode(["status" => false, "msg" => "Senha incorreta!"]);
        exit;
    }
    
    // Login bem sucedido
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_nome'] = $user['nome'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_nivel'] = $user['nivel'] ?? 'admin';
    
    echo json_encode(["status" => true, "msg" => "Login realizado com sucesso! Redirecionando..."]);
    
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    echo json_encode(["status" => false, "msg" => "Erro interno do servidor. Tente novamente!"]);
} catch (Exception $e) {
    error_log("Erro no login: " . $e->getMessage());
    echo json_encode(["status" => false, "msg" => "Erro interno do servidor. Tente novamente!"]);
}
