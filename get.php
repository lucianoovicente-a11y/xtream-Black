<?php
// ======================================================================
//  GET.PHP - API de Streaming no formato Xtream Codes (compatível com a maioria dos apps)
// ======================================================================

error_reporting(0);
ini_set('display_errors', 0);

$username = $_GET['username'] ?? $_POST['username'] ?? null;
$password = $_GET['password'] ?? $_POST['password'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$stream_id = $_GET['stream_id'] ?? $_GET['id'] ?? null;
$category_id = $_GET['category_id'] ?? null;
$output = $_GET['output'] ?? 'm3u8';

if (!$username || !$password) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['user_info' => ['auth' => 0, 'message' => 'Usuário e senha necessários']]);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
date_default_timezone_set('America/Sao_Paulo');

$base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

try {
    $conexao = conectar_bd();
    
    // Autenticar usuário
    $stmt = $conexao->prepare("SELECT * FROM clientes WHERE usuario = :username AND senha = :password");
    $stmt->bindValue(':username', $username);
    $stmt->bindValue(':password', $password);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['user_info' => ['auth' => 0, 'status' => 'Invalid Credentials']]);
        exit;
    }
    
    $exp_date = strtotime($cliente['Vencimento']);
    $status = ($exp_date >= time()) ? "Active" : "Expired";
    $auth = ($status === "Active") ? 1 : 0;
    
    switch ($action) {
        case '': // Login
        case 'get': // Login (compatibilidade)
            $response = [
                'user_info' => [
                    'username' => $username,
                    'password' => $password,
                    'message' => 'Bem-vindo!',
                    'auth' => $auth,
                    'status' => $status,
                    'exp_date' => (string)$exp_date,
                    'is_trial' => (string)($cliente['is_trial'] ?? '0'),
                    'active_cons' => 0,
                    'created_at' => (string)strtotime($cliente['Criado_em']),
                    'max_connections' => (string)($cliente['conexoes'] ?? '1'),
                    'allowed_output_formats' => ['m3u8', 'ts', 'mp4'],
                ],
                'server_info' => [
                    'url' => $_SERVER['HTTP_HOST'],
                    'port' => $_SERVER['SERVER_PORT'],
                    'https_port' => '443',
                    'server_protocol' => isset($_SERVER['HTTPS']) ? 'https' : 'http',
                    'rtmp_port' => '8880',
                    'timestamp_now' => time(),
                    'time_now' => date('Y-m-d H:i:s'),
                    'timezone' => 'America/Sao_Paulo'
                ]
            ];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            break;
            
        case 'get_live_categories':
            $query = "SELECT id, nome, parent_id FROM categoria WHERE type = 'live'";
            $params = [];
            if ($cliente['adulto'] == 0) {
                $query .= " AND is_adult = 0";
            }
            if (!empty($cliente['bouquet_id'])) {
                $query .= " AND id IN (SELECT category_id FROM bouquet_categories WHERE bouquet_id = ?)";
                $params[] = $cliente['bouquet_id'];
            }
            $query .= " ORDER BY position";
            
            $stmt = $conexao->prepare($query);
            $stmt->execute($params);
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cats);
            break;
            
        case 'get_vod_categories':
        case 'get_series_categories':
            $typeMap = ['get_vod_categories' => 'movie', 'get_series_categories' => 'series'];
            $type = $typeMap[$action] ?? 'movie';
            
            $query = "SELECT id, nome, parent_id FROM categoria WHERE type = ?";
            $params = [$type];
            if ($cliente['adulto'] == 0) {
                $query .= " AND is_adult = 0";
            }
            $query .= " ORDER BY position";
            
            $stmt = $conexao->prepare($query);
            $stmt->execute($params);
            $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cats);
            break;
            
        case 'get_live_streams':
            $query = "SELECT s.*, c.nome as categoria_nome FROM streams s LEFT JOIN categoria c ON s.category_id = c.id WHERE s.stream_type = 'live'";
            $params = [];
            
            if ($cliente['adulto'] == 0) {
                $query .= " AND (c.is_adult = 0 OR c.is_adult IS NULL)";
            }
            if (!empty($cliente['bouquet_id'])) {
                $query .= " AND s.category_id IN (SELECT category_id FROM bouquet_categories WHERE bouquet_id = ?)";
                $params[] = $cliente['bouquet_id'];
            }
            if ($category_id && $category_id !== '*') {
                $query .= " AND s.category_id = ?";
                $params[] = $category_id;
            }
            $query .= " ORDER BY c.nome, s.name";
            
            $stmt = $conexao->prepare($query);
            $stmt->execute($params);
            $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($streams as $i => $s) {
                // Gerar URL de streaming usando proxy
                $stream_url = $s['stream_url'] ?? '';
                if (!empty($stream_url) && strpos($stream_url, $_SERVER['HTTP_HOST']) === false && strpos($stream_url, 'localhost') === false) {
                    $encoded = base64_encode($stream_url);
                    $play_url = $base_url . '/api/stream_proxy.php?url=' . $encoded . '&user=' . $username . '&pass=' . $password . '&id=' . $s['id'];
                } else {
                    $play_url = $stream_url;
                }
                
                $result[] = [
                    'num' => $i + 1,
                    'name' => $s['name'],
                    'stream_type' => 'live',
                    'stream_id' => (int)$s['id'],
                    'stream_icon' => $s['stream_icon'] ?? '',
                    'epg_channel_id' => $s['epg_channel_id'] ?? '',
                    'added' => $s['added'] ?? '',
                    'category_id' => (string)$s['category_id'],
                    'tv_archive' => 0,
                    'tv_archive_duration' => '0',
                    'direct_source' => !empty($stream_url) ? 1 : 0,
                    'stream_url' => $play_url
                ];
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            break;
            
        case 'get_vod_streams':
            $query = "SELECT s.*, c.nome as categoria_nome FROM streams s LEFT JOIN categoria c ON s.category_id = c.id WHERE s.stream_type = 'movie'";
            $params = [];
            
            if ($cliente['adulto'] == 0) {
                $query .= " AND (c.is_adult = 0 OR c.is_adult IS NULL)";
            }
            if ($category_id && $category_id !== '*') {
                $query .= " AND s.category_id = ?";
                $params[] = $category_id;
            }
            $query .= " ORDER BY s.name";
            
            $stmt = $conexao->prepare($query);
            $stmt->execute($params);
            $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($streams as $i => $s) {
                $stream_url = $s['stream_url'] ?? '';
                if (!empty($stream_url)) {
                    $encoded = base64_encode($stream_url);
                    $play_url = $base_url . '/api/stream_proxy.php?url=' . $encoded . '&user=' . $username . '&pass=' . $password . '&id=' . $s['id'];
                } else {
                    $play_url = '';
                }
                
                $result[] = [
                    'num' => $i + 1,
                    'name' => $s['name'],
                    'stream_type' => 'movie',
                    'stream_id' => (int)$s['id'],
                    'stream_icon' => $s['stream_icon'] ?? '',
                    'rating' => (string)($s['rating'] ?? '0'),
                    'rating_5based' => round(($s['rating'] ?? 0) / 2, 1),
                    'added' => $s['added'] ?? '',
                    'category_id' => (string)$s['category_id'],
                    'container_extension' => $s['container_extension'] ?? 'mp4',
                    'stream_url' => $play_url
                ];
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            break;
            
        case 'get_series':
            $query = "SELECT s.* FROM series s ORDER BY s.name";
            $stmt = $conexao->prepare($query);
            $stmt->execute();
            $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($series as $i => $s) {
                $result[] = [
                    'num' => $i + 1,
                    'name' => $s['name'],
                    'series_id' => (int)$s['id'],
                    'cover' => $s['cover'] ?? '',
                    'plot' => $s['plot'] ?? '',
                    'cast' => $s['cast'] ?? '',
                    'director' => $s['director'] ?? '',
                    'genre' => $s['genre'] ?? '',
                    'releaseDate' => $s['release_date'] ?? '',
                    'last_modified' => $s['last_modified'] ?? '',
                    'rating' => (string)($s['rating'] ?? '0'),
                    'rating_5based' => round(($s['rating'] ?? 0) / 2, 1),
                    'youtube_trailer' => $s['youtube_trailer'] ?? '',
                    'episode_run_time' => $s['episode_run_time'] ?? '0',
                    'category_id' => (string)($s['category_id'] ?? 0)
                ];
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            break;
            
        case 'get_series_info':
            if (!$stream_id) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([]);
                break;
            }
            
            $stmt_ep = $conexao->prepare("SELECT * FROM series_episodes WHERE series_id = ? ORDER BY season, episode_num");
            $stmt_ep->execute([$stream_id]);
            $eps = $stmt_ep->fetchAll(PDO::FETCH_ASSOC);
            
            $seasons = [];
            foreach ($eps as $ep) {
                $season = (int)$ep['season'];
                if (!isset($seasons[$season])) $seasons[$season] = [];
                
                $stream_url = $ep['stream_url'] ?? '';
                if (!empty($stream_url)) {
                    $encoded = base64_encode($stream_url);
                    $play_url = $base_url . '/api/stream_proxy.php?url=' . $encoded . '&user=' . $username . '&pass=' . $password . '&id=' . $ep['id'];
                } else {
                    $play_url = '';
                }
                
                $seasons[$season][] = [
                    'id' => (string)$ep['id'],
                    'episode_num' => (string)$ep['episode_num'],
                    'title' => $ep['title'] ?? '',
                    'container_extension' => 'mp4',
                    'info' => [
                        'movie_image' => $ep['movie_image'] ?? '',
                        'plot' => $ep['plot'] ?? '',
                        'duration' => $ep['duration'] ?? '',
                        'rating' => (string)($ep['rating'] ?? '0')
                    ],
                    'added' => strtotime($ep['added'] ?? 'now'),
                    'stream_url' => $play_url
                ];
            }
            
            $stmt_s = $conexao->prepare("SELECT * FROM series WHERE id = ?");
            $stmt_s->execute([$stream_id]);
            $serie = $stmt_s->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'seasons' => array_values($seasons),
                'info' => [
                    'name' => $serie['name'] ?? '',
                    'cover' => $serie['cover'] ?? '',
                    'plot' => $serie['plot'] ?? '',
                    'cast' => $serie['cast'] ?? '',
                    'director' => $serie['director'] ?? '',
                    'genre' => $serie['genre'] ?? '',
                    'releaseDate' => $serie['release_date'] ?? '',
                    'rating' => (string)($serie['rating'] ?? '0'),
                    'rating_5based' => round(($serie['rating'] ?? 0) / 2, 1)
                ],
                'episodes' => $seasons
            ]);
            break;
            
        case 'get_short_epg':
            if (!$stream_id) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['epg_listings' => []]);
                break;
            }
            
            $stmt = $conexao->prepare("SELECT epg_channel_id FROM streams WHERE id = ?");
            $stmt->execute([$stream_id]);
            $epg_id = $stmt->fetchColumn();
            
            if (!$epg_id) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['epg_listings' => []]);
                break;
            }
            
            $stmt_epg = $conexao->prepare("SELECT title, start, `end` FROM epg WHERE channel_id = ? AND `end` > NOW() ORDER BY start ASC LIMIT 50");
            $stmt_epg->execute([$epg_id]);
            $epg = $stmt_epg->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
            foreach ($epg as $e) {
                $result[] = [
                    'id' => uniqid(),
                    'title' => base64_encode($e['title']),
                    'start' => $e['start'],
                    'end' => $e['end'],
                    'description' => base64_encode(''),
                    'start_timestamp' => (string)strtotime($e['start']),
                    'stop_timestamp' => (string)strtotime($e['end'])
                ];
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['epg_listings' => $result]);
            break;
            
        case 'm3u_plus':
        case 'm3u':
            // Gerar lista M3U
            header('Content-Type: application/vnd.apple.mpegurl');
            header('Content-Disposition: attachment; filename="playlist.m3u"');
            echo "#EXTM3U\n";
            
            $stmt = $conexao->prepare("SELECT s.*, c.nome as categoria_nome FROM streams s LEFT JOIN categoria c ON s.category_id = c.id WHERE s.stream_type = 'live' ORDER BY c.nome, s.name");
            $stmt->execute();
            $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($streams as $s) {
                $stream_url = $s['stream_url'] ?? '';
                if (!empty($stream_url)) {
                    if (strpos($stream_url, $_SERVER['HTTP_HOST']) === false && strpos($stream_url, 'localhost') === false) {
                        $encoded = base64_encode($stream_url);
                        $play_url = $base_url . '/api/stream_proxy.php?url=' . $encoded . '&user=' . $username . '&pass=' . $password . '&id=' . $s['id'];
                    } else {
                        $play_url = $stream_url;
                    }
                    
                    $cat = $s['categoria_nome'] ?? 'Sem Categoria';
                    echo "#EXTINF:-1 tvg-name=\"" . $s['name'] . "\" tvg-logo=\"" . ($s['stream_icon'] ?? '') . "\" group-title=\"" . $cat . "\"," . $s['name'] . "\n";
                    echo $play_url . "\n";
                }
            }
            break;
            
        default:
            // Atualizar atividade quando não é ação conhecida
            if ($stream_id) {
                $stmt_up = $conexao->prepare("UPDATE conexoes SET canal_atual = ?, ultima_atividade = NOW() WHERE usuario = ? ORDER BY id DESC LIMIT 1");
                $stmt_up->execute([$stream_id, $username]);
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([]);
            break;
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['user_info' => ['auth' => 0, 'message' => 'Erro: ' . $e->getMessage()]]);
}
?>