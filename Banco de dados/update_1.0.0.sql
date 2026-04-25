-- ============================================
-- ATUALIZAÇÃO DE BANCO DE DADOS - XTREAM SERVER
-- Versão: 1.0.0
-- Data: 2025-04-25
-- ============================================

-- Tabela de Tickets de Suporte
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    resposta TEXT,
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    status ENUM('aberto', 'em_andamento', 'resolvido', 'fechado') DEFAULT 'aberto',
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Pagamentos (caso não exista)
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(50) NOT NULL,
    status ENUM('pendente', 'aprovado', 'reprovado', 'cancelado') DEFAULT 'pendente',
    data_pagamento DATETIME,
    data_vencimento DATETIME,
    referencia VARCHAR(100),
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_status (status),
    INDEX idx_data (data_pagamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna max_conexoes na tabela clientes (caso não exista)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS max_conexoes INT DEFAULT 1 AFTER Vencimento;

-- Adicionar coluna telefone na tabela clientes (caso não exista)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) AFTER email;

-- Adicionar coluna status na tabela clientes (caso não exista)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'cancelado', 'suspenso') DEFAULT 'ativo' AFTER Vencimento;

-- Adicionar coluna plano na tabela clientes (caso não exista)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS plano VARCHAR(100) AFTER tipo;

-- Tabela de Histórico de Atualizações
CREATE TABLE IF NOT EXISTS update_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_from VARCHAR(20),
    version_to VARCHAR(20) NOT NULL,
    update_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sucesso', 'falha', 'parcial') DEFAULT 'sucesso',
    files_updated INT DEFAULT 0,
    backup_dir VARCHAR(255),
    error_message TEXT,
    INDEX idx_version (version_to),
    INDEX idx_date (update_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados de exemplo para tickets (opcional)
-- INSERT INTO tickets (cliente_id, assunto, mensagem, prioridade) 
-- SELECT id, 'Primeiro Ticket', 'Este é um ticket de exemplo.', 'media' 
-- FROM clientes LIMIT 1;

-- ============================================
-- FIM DA ATUALIZAÇÃO
-- ============================================
