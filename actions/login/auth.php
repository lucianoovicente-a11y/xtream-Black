<?php
session_start();
require_once __DIR__ . '/../../configuracoes/functions.php';

$response = ['status' => false, 'msg' => 'Erro ao processar login'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $response['msg'] = 'Preencha todos os campos';
        echo json_encode($response);
        exit;
    }
    
    try {
        $db = conexaoPDOlocal();
        $stmt = $db->prepare("SELECT * FROM admin WHERE user = ? AND admin IN ('1', '2', '3') LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['pass'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_logged'] = true;
            $_SESSION['admin_user'] = $user['user'];
            
            $response['status'] = true;
            $response['msg'] = 'Login realizado com sucesso';
        } else {
            $response['msg'] = 'Usuário ou senha inválidos';
        }
    } catch (Exception $e) {
        $response['msg'] = 'Erro no sistema: ' . $e->getMessage();
    }
}

echo json_encode($response);
