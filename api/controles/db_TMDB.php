<?php
/**
 * ARQUIVO: /api/controles/db_TMDB.php
 * DESCRIÇÃO: Centraliza chaves de API e URLs base relacionadas ao The Movie Database (TMDB).
 *
 * Use CONSTANTES (define) para armazenar valores que não mudam.
 */

// =========================================================================
// CHAVES DE API
// =========================================================================

// Chave da API do The Movie Database (TMDB)
// A chave fornecida no seu exemplo: s654gd6sf4gd6f4g6df46dgsd156er6
define('TMDB_API_KEY', 'coloca_sua_api_do_tmdb_aqui');


// =========================================================================
// CONFIGURAÇÕES GERAIS E URLS BASE
// =========================================================================

// Língua padrão para as requisições (TMDB usa o formato pt-BR)
define('API_LANGUAGE', 'pt-BR');

// URL base para imagens de poster (w500)
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500');

// URL base para imagens de backdrop (original)
define('TMDB_BACKDROP_BASE_URL', 'https://image.tmdb.org/t/p/original');