<?php
// ARQUIVO: /gerenciador/ajax/filme_buscar_tmdb.php (VERSÃO CORRIGIDA)
header('Content-Type: application/json');

// =======================================================================
// CORREÇÃO: Inclui o arquivo de configuração centralizado
// O caminho deve ser absoluto.
// =======================================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db_TMDB.php');

// !!! COLOQUE SUA CHAVE DA API DO TMDB AQUI !!!
// 🚨 LINHA REMOVIDA: A chave local foi removida e substituída pela constante TMDB_API_KEY.
// $apiKey = 'f99aa9ae1fe7619969cc7db0938c1ae5';

$query = $_GET['query'] ?? '';

// Agora, a chave é verificada pelo arquivo db_TMDB.php, 
// mas podemos manter uma verificação de segurança aqui, se TMDB_API_KEY for uma constante vazia por engano.
if (empty(TMDB_API_KEY)) {
    echo json_encode(['error' => 'A chave da API do TMDB não foi configurada no backend (TMDB_API_KEY vazia).']);
    exit;
}
if (empty($query)) { echo json_encode([]); exit; }

// Busca por filmes na API
// 🚨 ALTERAÇÃO: Usa a constante global TMDB_API_KEY e API_LANGUAGE
$url = "https://api.themoviedb.org/3/search/movie?api_key=" . urlencode(TMDB_API_KEY) . "&language=" . API_LANGUAGE . "&query=" . urlencode($query);
$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode(['error' => 'Não foi possível se comunicar com a API do TMDB.']);
    exit;
}
echo $response;
?>