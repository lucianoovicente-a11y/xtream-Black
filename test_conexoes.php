<?php
// Script de diagnóstico das conexões
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/ConnectionManager.class.php');

echo "<h2>DIAGNÓSTICO DO SISTEMA DE CONEXÕES</h2>";

// 1. Verifica se a tabela existe
$conn = conectar_bd();
if (!$conn) {
    echo "<p style='color:red'>❌ Falha ao conectar no banco</p>";
    exit;
}

echo "<h3>1. Estrutura da tabela 'conexoes'</h3>";
try {
    $stmt = $conn->query("DESCRIBE conexoes");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($cols, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Tabela 'conexoes' não existe: " . $e->getMessage() . "</p>";
}

// 2. Conta registros
echo "<h3>2. Total de registros na tabela 'conexoes'</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM conexoes");
    $total = $stmt->fetch()['total'];
    echo "<p>Total: <strong>$total</strong> registros</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erro: " . $e->getMessage() . "</p>";
}

// 3. Últimas 10 conexões
echo "<h3>3. Últimas 10 conexões registradas</h3>";
try {
    $stmt = $conn->query("SELECT usuario, ip, canal_atual, tipo_stream, ultima_atividade FROM conexoes ORDER BY ultima_atividade DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "<p style='color:orange'>⚠️ Nenhuma conexão registrada ainda!</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Usuário</th><th>IP</th><th>Canal</th><th>Tipo</th><th>Atividade</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ip']) . "</td>";
            echo "<td>" . htmlspecialchars($row['canal_atual'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo_stream'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($row['ultima_atividade']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erro: " . $e->getMessage() . "</p>";
}

// 4. Testa ConnectionManager
echo "<h3>4. Testando ConnectionManager</h3>";
try {
    $cm = new ConnectionManager();
    echo "<p style='color:green'>✅ ConnectionManager instanciado com sucesso</p>";
    
    // Testa cleanup
    $cm->cleanupDeadConnections();
    echo "<p>✅ Cleanup executado</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Erro no ConnectionManager: " . $e->getMessage() . "</p>";
}

// 5. Verifica se há colunas faltantes
echo "<h3>5. Verificação de colunas necessárias</h3>";
$required_cols = ['usuario', 'ip', 'canal_atual', 'tipo_stream', 'ultima_atividade', 'user_agent', 'serie_nome'];
$missing = [];

foreach ($required_cols as $col) {
    if (!in_array($col, $cols)) {
        $missing[] = $col;
    }
}

if (empty($missing)) {
    echo "<p style='color:green'>✅ Todas as colunas necessárias existem</p>";
} else {
    echo "<p style='color:red'>❌ Colunas faltando: " . implode(', ', $missing) . "</p>";
}

echo "<hr>";
echo "<p><small>Execute este script sempre que precisar diagnosticar problemas nas conexões.</small></p>";
?>
