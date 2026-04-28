<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('./api/controles/db.php');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Teste Conexão API</title>
    <style>
        body { font-family: monospace; background: #0a0a12; color: #fff; padding: 20px; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        table { border-collapse: collapse; margin-top: 20px; }
        td, th { border: 1px solid #333; padding: 8px; }
    </style>
</head>
<body>
<h1>Teste - Usuários Online</h1>";

$conexao = conectar_bd();

if (!$conexao) {
    echo "<p class='error'>❌ ERRO: Não foi possível conectar ao banco de dados!</p>";
    exit;
}

echo "<p class='success'>✓ Conectado ao banco de dados!</p>";

// Verificar se a tabela existe
echo "<p class='info'>Verificando tabela 'conexoes'...</p>";

try {
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM conexoes");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de conexões na tabela: <strong>{$count['total']}</strong></p>";
    
    if ($count['total'] == 0) {
        echo "<p class='error'>⚠️ Nenhuma conexão registrada! Isso significa que nenhum usuário está assistindo streams neste momento.</p>";
        echo "<p>Os usuários devem estar usando os apps (IBO, Beta XC, etc) e assistindo canais para aparecerem aqui.</p>";
    } else {
        // Listar conexoes
        echo "<p class='success'>Mostrando últimos registros...</p>";
        
        $stmt = $conexao->query("SELECT * FROM conexoes ORDER BY ultima_atividade DESC LIMIT 10");
        $conexoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
        <tr>
            <th>ID</th>
            <th>Usuário</th>
            <th>IP</th>
            <th>Canal Atual</th>
            <th>Última Atividade</th>
        </tr>";
        
        foreach ($conexoes as $c) {
            echo "<tr>
                <td>{$c['id']}</td>
                <td>{$c['usuario']}</td>
                <td>{$c['ip']}</td>
                <td>{$c['canal_atual']}</td>
                <td>{$c['ultima_atividade']}</td>
            </tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>Erro ao buscar dados: " . $e->getMessage() . "</p>";
}

// Verificar streams
echo "<hr>";
echo "<p class='info'>Verificando tabela 'streams'...</p>";

try {
    $stmt = $conexao->query("SELECT COUNT(*) as total FROM streams");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de streams cadastrados: <strong>{$count['total']}</strong></p>";
    
    // Ver alguns streams
    $stmt = $conexao->query("SELECT id, name, stream_url, stream_type FROM streams LIMIT 5");
    $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Tipo</th>
        <th>URL</th>
    </tr>";
    
    foreach ($streams as $s) {
        $url = substr($s['stream_url'] ?? '', 0, 50);
        echo "<tr>
            <td>{$s['id']}</td>
            <td>{$s['name']}</td>
            <td>{$s['stream_type']}</td>
            <td>{$url}...</td>
        </tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p class='error'>Erro ao buscar streams: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>