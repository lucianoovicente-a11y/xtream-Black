<?php
error_reporting(0);
ini_set('display_errors', 0);

$db = new PDO("sqlite:".__DIR__."/configuracoes/data.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$senha = crypt('132004', '$6$rounds=20000$i9teamp2panel$');

// Verifica se existe usuario com id 1
$stmt = $db->query("SELECT id FROM usuarios WHERE id = 1");
if ($stmt->fetch()) {
    // Atualiza o usuário ID 1 para ser super admin
    $sql = "UPDATE usuarios SET nome = 'Luciano', email = 'luciano@admin.com', senha = :senha, nivel = 1, status = 1 WHERE id = 1";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":senha", $senha, PDO::PARAM_STR, 255);
    $stmt->execute();
    echo "<h1>Super Admin ATUALIZADO!</h1>";
} else {
    // Cria novo usuário com ID 1
    $sql = "INSERT INTO usuarios (id, nome, email, senha, status, nivel) VALUES (1, 'Luciano', 'luciano@admin.com', :senha, 1, 1)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":senha", $senha, PDO::PARAM_STR, 255);
    $stmt->execute();
    echo "<h1>Super Admin CRIADO!</h1>";
}

echo "<p>Login: luciano@admin.com</p>";
echo "<p>Senha: 132004</p>";
echo "<p>Nivel: 1 (Admin)</p>";
echo "<p>ID: 1 (Super Admin)</p>";
echo "<br><a href='./'>Voltar</a>";
?>