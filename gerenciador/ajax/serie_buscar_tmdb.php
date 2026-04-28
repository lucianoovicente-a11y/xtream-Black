<?php
// ARQUIVO: /gerenciador/ajax/serie_buscar_tmdb.php

header('Content-Type: application/json');

// =======================================================================
// CORREÇÃO: Inclui o arquivo de configuração centralizado
// O caminho deve ser absoluto.
// =======================================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db_TMDB.php');

// !!! IMPORTANTE !!!
// 🚨 LINHA REMOVIDA: A chave local foi removida e substituída pela constante TMDB_API_KEY.
// $apiKey = 'f99aa9ae1fe7619969cc7db0938c1ae5';

$query = $_GET['query'] ?? '';

// Agora, a verificação usa a constante global
if (!defined('TMDB_API_KEY') || empty(TMDB_API_KEY)) {
    echo json_encode(['error' => 'A chave da API do TMDB não foi configurada no backend (TMDB_API_KEY vazia).']);
    exit;
}

if (empty($query)) {
    echo json_encode([]);
    exit;
}

// A única mudança é aqui: 'search/tv'
// 🚨 ALTERAÇÃO: Usa as constantes globais TMDB_API_KEY e API_LANGUAGE
$url = "https://api.themoviedb.org/3/search/tv?api_key=" . urlencode(TMDB_API_KEY) . "&language=" . API_LANGUAGE . "&query=" . urlencode($query);

$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode(['error' => 'Não foi possível se comunicar com a API do TMDB.']);
    exit;
}

echo $response;
?>