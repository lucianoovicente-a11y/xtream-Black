<?php
session_start();

// Verifica se o usuário está logado, redireciona para nova área do cliente
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Redireciona para a nova área completa do cliente
header('Location: area_cliente.php');
exit();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://example.com/favicon.ico" type="image/x-icon">
</head>
<body>
    <p>Redirecionando para nova área do cliente...</p>
    <script>window.location.href = 'area_cliente.php';</script>
</body>
</html>