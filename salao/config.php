<?php
$db_file = __DIR__ . '/salao.db';

try {
    $conn = new PDO("sqlite:$db_file");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$conn->exec("PRAGMA foreign_keys = ON");

$sql = "
CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    telefone TEXT,
    email TEXT,
    whatsapp TEXT,
    obs TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS servicos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    preco REAL NOT NULL,
    duracao INTEGER DEFAULT 30,
    descricao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agendamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER,
    servico_id INTEGER,
    data_hora TEXT NOT NULL,
    status TEXT DEFAULT 'agendado',
    obs TEXT,
    valor_pago REAL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS despesas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao TEXT NOT NULL,
    valor REAL NOT NULL,
    categoria TEXT,
    data TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cobrancas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER,
    valor REAL NOT NULL,
    descricao TEXT,
    data_vencimento TEXT NOT NULL,
    data_pagamento TEXT,
    status TEXT DEFAULT 'pendente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS config (
    id INTEGER PRIMARY KEY,
    nome_salao TEXT DEFAULT 'Bia Manicure',
    whatsapp TEXT DEFAULT '',
    mensagem_confirmacao TEXT DEFAULT 'Olá {nome}! Seu agendamento para {servico} está confirmado para {data} às {hora}. A Bia Manicure agradeceu!'
);
";

$conn->exec($sql);

$stmt = $conn->query("SELECT COUNT(*) as cnt FROM config");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && $row['cnt'] == 0) {
    $conn->exec("INSERT INTO config (id, nome_salao) VALUES (1, 'Bia Manicure')");
}

function getResults($result) {
    $rows = [];
    if ($result) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function getConfig($conn) {
    $stmt = $conn->query("SELECT * FROM config WHERE id = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: ['nome_salao' => 'Bia Manicure', 'whatsapp' => '', 'mensagem_confirmacao' => ''];
}

function getEstatisticas($conn) {
    $mes_atual = date('Y-m');
    $inicio_mes = $mes_atual . '-01';
    $fim_mes = date('Y-m-t');
    
    $stmt = $conn->query("SELECT COALESCE(SUM(valor_pago), 0) as total FROM agendamentos WHERE status = 'concluido' AND data_hora >= '$inicio_mes' AND data_hora <= '$fim_mes'");
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE data >= '$inicio_mes' AND data <= '$fim_mes'");
    $despesa_mes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM cobrancas WHERE status = 'pendente'");
    $cobrancas_pendentes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM agendamentos WHERE data_hora >= '$inicio_mes' AND data_hora <= '$fim_mes'");
    $agendamentos_mes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
    $total_clientes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'receita' => $receita['total'] ?? 0,
        'despesas' => $despesa_mes['total'] ?? 0,
        'lucro' => ($receita['total'] ?? 0) - ($despesa_mes['total'] ?? 0),
        'cobrancas_pendentes' => $cobrancas_pendentes['total'] ?? 0,
        'agendamentos_mes' => $agendamentos_mes['total'] ?? 0,
        'total_clientes' => $total_clientes['total'] ?? 0
    ];
}
?>