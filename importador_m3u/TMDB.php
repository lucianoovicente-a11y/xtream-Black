<?php
// =======================================================================
// CORREÇÃO FINAL: Inclui o arquivo de configuração centralizado
// Agora busca a chave e as configurações da constante global TMDB_API_KEY.
// =======================================================================
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db_TMDB.php');

class TMDB {
    
    // 🚨 A CHAVE LOCAL FOI REMOVIDA DAQUI.
    // private static $apiKey foi removido com sucesso.

    /**
     * Função base para fazer requisições à API do TMDB.
     */
    private static function fetch($endpoint, $params = []) {
        // Usa as constantes globais do db_TMDB.php
        $params['api_key'] = TMDB_API_KEY;
        $params['language'] = API_LANGUAGE;
        
        $url = 'https://api.themoviedb.org/3/' . $endpoint . '?' . http_build_query($params);
        
        $json = @file_get_contents($url);
        return $json ? json_decode($json, true) : null;
    }

    public static function findBestMatch($type, $name) {
        $patternsToRemove = ['/\((19|20)\d{2}\)/', '/\[.*?\]/', '/\b(1080p|720p|4k|HD|WEB-DL|DUBLADO|LEGENDADO|LEG|DUAL|NACIONAL)\b/i'];
        $cleanName = trim(preg_replace('/\s+/', ' ', preg_replace($patternsToRemove, '', $name)));
        if (empty($cleanName)) { $cleanName = $name; }
        $data = self::fetch("search/{$type}", ['query' => $cleanName, 'include_adult' => 'true']);
        return $data['results'][0] ?? null;
    }

    public static function getDetails($type, $tmdbId) {
        $data = self::fetch("{$type}/{$tmdbId}", ['append_to_response' => 'credits']);
        if (!$data) return null;

        $details = [];
        // Usa a constante global do db_TMDB.php
        $imageBaseUrl = TMDB_IMAGE_BASE_URL;

        if ($type === 'movie') {
            $director = '';
            foreach ($data['credits']['crew'] ?? [] as $crew) { if ($crew['job'] == 'Director') { $director = $crew['name']; break; } }
            $details['name'] = $data['title'] ?? '';
            $details['stream_icon'] = $data['poster_path'] ? $imageBaseUrl . $data['poster_path'] : '';
            $details['releaseDate'] = $data['release_date'] ?? '';
            $details['actors'] = implode(', ', array_slice(array_column($data['credits']['cast'] ?? [], 'name'), 0, 5));
            $details['duration'] = isset($data['runtime']) && $data['runtime'] > 0 ? gmdate("H:i:s", $data['runtime'] * 60) : '00:00:00';
        } else { // 'tv'
            $details['name'] = $data['name'] ?? '';
            $details['cover'] = $data['poster_path'] ? $imageBaseUrl . $data['poster_path'] : '';
            $details['releaseDate'] = $data['first_air_date'] ?? '';
            $details['cast'] = implode(', ', array_slice(array_column($data['credits']['cast'] ?? [], 'name'), 0, 5));
        }

        $details['plot'] = $data['overview'] ?? '';
        // Usa a constante global do db_TMDB.php
        $details['backdrop_path'] = $data['backdrop_path'] ? TMDB_BACKDROP_BASE_URL . $data['backdrop_path'] : '';
        $details['rating'] = $data['vote_average'] ?? 0;
        $details['rating_5based'] = round(($data['vote_average'] ?? 0) / 2, 1);
        $details['year'] = !empty($details['releaseDate']) ? substr($details['releaseDate'], 0, 4) : '';
        $details['genre'] = implode(', ', array_column($data['genres'] ?? [], 'name'));
        $details['director'] = $director ?? implode(', ', array_column($data['created_by'] ?? [], 'name'));
        $details['youtube_trailer'] = '';

        return $details;
    }
}
?>