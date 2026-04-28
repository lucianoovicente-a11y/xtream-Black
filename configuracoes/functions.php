<?php
/**
 * Funções auxiliares do sistema
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');

/**
 * Conexão com banco de dados SQLite
 */
function conexaoPDOlocal() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dbDir = __DIR__ . '/../Banco de dados';
            if (!file_exists($dbDir)) {
                mkdir($dbDir, 0777, true);
            }
            $dbPath = $dbDir . '/streamblack.sqlite';
            $connection = new PDO("sqlite:" . $dbPath);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connection->exec("PRAGMA journal_mode=WAL;");
            $connection->exec("PRAGMA foreign_keys=ON;");
        } catch (PDOException $e) {
            die("Erro na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    return $connection;
}

/**
 * Função usada pelo Database.class.php
 */
function conectar_bd() {
    return conexaoPDOlocal();
}
