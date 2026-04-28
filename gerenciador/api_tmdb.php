<?php
header('Content-Type: application/json'); // Informa que a resposta será em formato JSON

// =======================================================================
// CORREÇÃO: Inclui o arquivo de configuração centralizado
// Agora busca a chave na constante global TMDB_API_KEY.
// =======================================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db_TMDB.php');

// Sua chave da API do TMDB
// 🚨 LINHA REMOVIDA: A variável $apiKey local foi removida e substituída pela constante TMDB_API_KEY.
// $apiKey = 'coloca_sua_api_tmdb_aqui';

// Obtém os parâmetros
$query = isset($_GET['query']) ? urlencode($_GET['query']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'movie'; // 'movie' ou 'tv' para séries

if (empty($query)) {
    echo json_encode(['error' => 'Nenhum termo de busca fornecido.']);
    exit;
}

// Monta a URL da API
// 🚨 ALTERAÇÃO: Usa a constante global TMDB_API_KEY e API_LANGUAGE
$url = "https://api.themoviedb.org/3/search/{$type}?api_key=" . TMDB_API_KEY . "&language=" . API_LANGUAGE . "&query={$query}";

// Faz a requisição e retorna o resultado
$response = file_get_contents($url);
echo $response;
?>