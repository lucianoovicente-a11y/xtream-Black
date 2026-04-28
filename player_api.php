<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/controles/db.php');
header('Content-Type: application/json; charset=utf-8');
header('Connection: close');
header('Access-Control-Allow-Credentials: *');
date_default_timezone_set('America/Sao_Paulo');

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

$url = $_SERVER['HTTP_HOST'];
$username = isset($_GET['username']) ? $_GET['username'] : null;
$password = isset($_GET['password']) ? $_GET['password'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;
$series_id = isset($_GET['series_id']) ? $_GET['series_id'] : null;
$vod_id = isset($_GET['vod_id']) ? $_GET['vod_id'] : null;
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

if($category_id === "*"){
    $category_id = null;
}

// ... [Código de redirecionamento POST para GET e Autenticação - Sem Alterações] ...
if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['action']) && isset($_POST['series_id'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $action = $_POST['action'];
  $series_id = $_POST['series_id'];
  header('Location: player_api.php?username=' . urlencode($username) . '&password=' . urlencode($password) . '&action=' . urlencode($action) . '&series_id=' . urlencode($series_id));
  exit;
}
if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['action'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $action = $_POST['action'];
  header('Location: player_api.php?username=' . urlencode($username) . '&password=' . urlencode($password) . '&action=' . urlencode($action));
  exit;
}
if(isset($_POST['username']) && isset($_POST['password'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  exit;
}
if (!$username || !$password) {
    http_response_code(401);
   $errorResponse['user_info'] = array();
   $errorResponse['user_info']['auth'] = 0;
   $errorResponse['user_info']['msg'] = "username e password necessario!";
    echo json_encode($errorResponse);
    exit();
}
$conexao = conectar_bd();
$query = "SELECT * FROM clientes WHERE usuario = :username AND senha = :password";
$statement = $conexao->prepare($query);
$statement->bindValue(':username', $username);
$statement->bindValue(':password', $password);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
if (!$result) {
    http_response_code(401);
    $errorResponse = json_encode(["user_info" => ["auth" => 0]]);
    echo $errorResponse;
    exit();
}
// ... [Fim do código de autenticação] ...


//-----GET de Informações do Usuário (sem action)
if (isset($_GET['username']) && isset($_GET['password']) && !isset($_GET['action'])) {
    // ... [Código de informações do usuário - Sem Alterações] ...
    $exp_date = strtotime($result['Vencimento']);
    $created_at = strtotime($result['Criado_em']);
    $status = "Active";
    $auth = "1";

    if ($exp_date < strtotime(date("Y-m-d H:i:s"))) {
        $status = "Inactive";
        $auth = "0";
    }

    $response = array(
        'user_info' => array(
            'username' => $result['usuario'],
            'password' => $result['senha'],
            'message' => 'BEM-VINDOS AO TOP IPTV!',
            'auth' => $auth,
            'status' => $status,
            'exp_date' => "$exp_date",
            'is_trial' => "".$result['is_trial'],
            'active_cons' => 0,
            'created_at' => "$created_at",
            'max_connections' => "".$result['conexoes'],
            'allowed_output_formats' => array('m3u8', 'ts', 'rtmp')
        ),
        'server_info' => array(
            'painel' => 'FURIA XTREAM',
            'version' => '0.0.1',
            'revision' => 1,
            'url' => $_SERVER['HTTP_HOST'],
            'port' => $_SERVER['SERVER_PORT'],
            'https_port' => "443",
            'server_protocol' => $_SERVER['REQUEST_SCHEME'],
            'rtmp_port' => '8880',
            'timestamp_now' => time(),
            'time_now' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        )
    );
    echo json_encode($response);
    exit();
}


//-----GET Categorias (Canais, VOD, Séries)
if (isset($_GET['action']) && in_array($_GET['action'], ['get_live_categories', 'get_vod_categories', 'get_series_categories'])) {
    $action = $_GET['action'];
    $adulto = $result['adulto'];
    $type = '';
    switch ($action) {
        case 'get_live_categories':
            // ==========================================================
            // ALTERAÇÃO PARA O TESTE
            // ==========================================================
            $type = 'live'; // Alterado de volta para 'live' para o teste
            break;
        case 'get_vod_categories':
            $type = 'movie';
            break;
        case 'get_series_categories':
            $type = 'series';
            break;
    }
    $query_str = "SELECT * FROM categoria WHERE type = :type ORDER BY position ASC";
    if ($adulto == 0) {
        $query_str = "SELECT * FROM categoria WHERE type = :type AND is_adult = '0' ORDER BY position ASC";
    }
    $query = $conexao->prepare($query_str);
    $query->bindValue(":type", $type);
    $query->execute();
    $results = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [ "category_id" => (string)$row["id"], "category_name" => $row["nome"], "parent_id" => $row["parent_id"] ];
    }
    if (empty($results)) {
        $results[] = ["category_id" => "1", "category_name" => "Sem categorias", "parent_id" => 0];
    }
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}


//-----GET streams (Canais, VOD, Séries)
if (isset($_GET['action']) && in_array($_GET['action'], ['get_live_streams', 'get_vod_streams', 'get_series'])) {
    $action = $_GET['action'];
    $stream_type = '';
    $table = 'streams';
    $adulto = isset($result['adulto']) ? $result['adulto'] : 0;

    switch ($action) {
        case 'get_live_streams':
            // ==========================================================
            // ALTERAÇÃO PARA O TESTE
            // ==========================================================
            $stream_type = 'live'; // Alterado de volta para 'live' para o teste
            break;
        case 'get_vod_streams': $stream_type = 'movie'; break;
        case 'get_series': $stream_type = 'series'; $table = 'series'; break;
    }

    $query_str = "SELECT * FROM `{$table}` WHERE `stream_type` = :stream_type";
    $params = [':stream_type' => $stream_type];
    if ($adulto == 0) {
        $query_str .= " AND `is_adult` = '0'";
    }
    if (isset($category_id)) {
        $query_str .= " AND `category_id` = :category_id";
        $params[':category_id'] = $category_id;
    }
    $query_str .= " ORDER BY `name` ASC";
    $query = $conexao->prepare($query_str);
    $query->execute($params);

    $num = 0;
    $results = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $num++;
        // ... [Lógica de formatação do JSON - Sem Alterações] ...
        if ($action == 'get_live_streams') {
            $results[] = [
                "num" => $num, "name" => htmlspecialchars_decode($row["name"]), "stream_type" => $row["stream_type"],
                "stream_id" => $row["id"], "stream_icon" => $row["stream_icon"], "epg_channel_id" => $row["epg_channel_id"],
                "added" => $row["added"], "is_adult" => "0", "custom_sid" => "", "tv_archive" => 0,
                "direct_source" => "", "tv_archive_duration" => 0, "category_id" => $row["category_id"],
                "category_ids" => [$row["category_id"]], "thumbnail" => "",
            ];
        } elseif ($action == 'get_vod_streams') {
            $results[] = [
                "num" => $num, "name" => htmlspecialchars_decode($row["name"]), "title" => htmlspecialchars_decode($row["name"]),
                "year" => $row["year"], "stream_type" => $row["stream_type"], "stream_id" => $row["id"],
                "stream_icon" => $row["stream_icon"], "rating" => $row["rating"], "rating_5based" => $row["rating_5based"],
                "added" => $row["added"], "is_adult" => $row["is_adult"], "category_id" => $row["category_id"],
                "container_extension" => "mp4", "custom_sid" => "", "direct_source" => "",
            ];
        } elseif ($action == 'get_series') {
            $results[] = [
                "num" => $num, "name" => htmlspecialchars_decode($row["name"]) ?? "", "title" => htmlspecialchars_decode($row["name"]) ?? "",
                "year" => $row["year"] ?? "", "stream_type" => $row["stream_type"], "series_id" => $row["id"],
                "cover" => $row["cover"] ?? "", "plot" => $row["plot"] ?? "", "cast" => !empty($row["cast"]) ? $row["cast"] : null,
                "director" => !empty($row["director"]) ? $row["director"] : null, "genre" => $row["genre"] ?? "",
                "release_date" => $row["release_date"] ?? "", "releaseDate" => $row["release_date"] ?? "",
                "last_modified" => $row["last_modified"] ?? "", "rating" => $row["rating"] ?? "0",
                "rating_5based" => floatval($row["rating_5based"] ?? 0),
                "backdrop_path" => !empty($row["backdrop_path"]) ? explode(",", $row["backdrop_path"]) : [],
                "youtube_trailer" => !empty($row["youtube_trailer"]) ? $row["youtube_trailer"] : null,
                "episode_run_time" => $row["episode_run_time"] ?? "0", "category_id" => $row["category_id"],
                "category_ids" => [$row["category_id"]],
            ];              
        }
    }
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}


// ... [O resto do arquivo que lida com get_vod_info e get_series_info - Sem Alterações] ...
if (isset($_GET['action']) && $_GET['action'] === 'get_vod_info' && isset($_GET['vod_id'])) {
    $vod_id = $_GET['vod_id'];
    $query = $conexao->prepare("SELECT * FROM streams WHERE id = :vod_id");
    $query->execute(array(":vod_id" => $vod_id));
    $info = []; $movie_data = [];
    if ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        function timeToSeconds($timeStr) {
            $parts = explode(":", $timeStr);
            if (count($parts) === 3) { list($hours, $minutes, $seconds) = $parts; }
            elseif (count($parts) === 2) { list($hours, $minutes) = $parts; $seconds = 0; }
            else { return 0; }
            return ((int)$hours * 3600) + ((int)$minutes * 60) + (int)$seconds;
        }
        $durationStr = $row["duration"] ?? "00:00:00";
        $durationSecs = timeToSeconds($durationStr);
        $info = [
            "kinopoisk_url" => $row["kinopoisk_url"] ?? "", "tmdb_id" => strval($row["tmdb_id"] ?? ""),
            "name" => htmlspecialchars_decode($row["name"] ?? ""), "o_name" => htmlspecialchars_decode($row["name"] ?? ""),
            "cover_big" => $row["stream_icon"] ?? "", "movie_image" => $row["stream_icon"] ?? "",
            "release_date" => $row["releasedate"] ?? "", "releasedate" => $row["releasedate"] ?? "",
            "episode_run_time" => $row["episode_run_time"] ?? "", "youtube_trailer" => $row["youtube_trailer"] ?? "",
            "director" => $row["director"] ?? "", "actors" => $row["actors"] ?? "", "cast" => $row["actors"] ?? "",
            "description" => $row["plot"] ?? "", "plot" => $row["plot"] ?? "", "age" => $row["age"] ?? "", "mpaa_rating" => "",
            "rating_count_kinopoisk" => intval($row["rating_count_kinopoisk"] ?? 0), "country" => $row["country"] ?? "",
            "genre" => $row["genre"] ?? "", "backdrop_path" => array_filter(explode(",", $row["backdrop_path"] ?? "")),
            "duration_secs" => $durationSecs, "duration" => $row["duration"] ?? "00:00:00", "runtime" => intval($row["duration"] ?? 0),
            "bitrate" => intval($row["bitrate"] ?? 0), "rating" => $row["rating"] ?? "",
            "subtitles" => array_filter(explode(",", $row["subtitles"] ?? "")), "video" => [], "audio" => []
        ];
        $movie_data = [
            "name" => htmlspecialchars_decode($row["name"] ?? ""), "title" => htmlspecialchars_decode($row["name"] ?? ""),
            "year" => intval($row["year"] ?? 0), "added" => $row["added"] ?? null, "stream_id" => intval($row["id"]),
            "category_id" => intval($row["category_id"]), "category_ids" => [intval($row["category_id"])],
            "container_extension" => $row["container_extension"] ?? "mp4", "custom_sid" => "", "direct_source" => ""
        ];
    }
    echo json_encode(["info" => $info, "movie_data" => $movie_data]);
    exit;
}
if (isset($_GET['action'], $_GET['series_id']) && $_GET['action'] === 'get_series_info') {
    $series_id = filter_input(INPUT_GET, 'series_id', FILTER_VALIDATE_INT);
    if (!$series_id) { http_response_code(400); echo json_encode(["error" => "ID da série inválido."]); exit; }
    $query = $conexao->prepare("SELECT * FROM series WHERE id = :series_id");
    $query->execute([":series_id" => $series_id]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); echo json_encode(["error" => "Série não encontrada."]); exit; }
    $series = [
        "name" => htmlspecialchars_decode($row["name"]) ?? "", "title" => htmlspecialchars_decode($row["name"]) ?? "",
        "cover" => $row["cover"] ?? "", "year" => $row["year"] ?? "", "plot" => $row["plot"] ?? "", "cast" => $row["cast"] ?? "",
        "director" => $row["director"] ?? "", "genre" => $row["genre"] ?? "", "release_date" => $row["release_date"] ?? "",
        "last_modified" => !empty($row["last_modified"]) ? explode(",", $row["last_modified"]) : [], "rating" => $row["rating"] ?? "",
        "rating_5based" => $row["rating_5based"] ?? "", "backdrop_path" => !empty($row["backdrop_path"]) ? explode(",", $row["backdrop_path"]) : [],
        "youtube_trailer" => $row["youtube_trailer"] ?? "", "episode_run_time" => $row["episode_run_time"] ?? "",
        "category_id" => $row["category_id"] ?? "", "category_ids" => [$row["category_id"] ?? ""]
    ];
    $query = $conexao->prepare("SELECT * FROM series_seasons WHERE series_id = :series_id");
    $query->execute([":series_id" => $series_id]);
    $seasons = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $seasons[] = [ "air_date" => $row["air_date"] ?? "", "episode_count" => $row["episode_count"] ?? "", "id" => $row["id"] ?? "", "name" => htmlspecialchars_decode($row["name"]) ?? "", "overview" => $row["overview"] ?? "", "season_number" => $row["season_number"] ?? "", "cover" => $row["cover"] ?? "", "cover_big" => $row["cover_big"] ?? "" ];
    }
    $query = $conexao->prepare("SELECT * FROM series_episodes WHERE series_id = :series_id ORDER BY season, episode_num");
    $query->execute([":series_id" => $series_id]);
    $episodes = [];
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $season = $row['season'] ?? 0;
        if (!isset($episodes[$season])) { $episodes[$season] = []; }
        $episodes[$season][] = [
            "id" => $row["id"] ?? "", "episode_num" => $row["episode_num"] ?? "", "title" => htmlspecialchars_decode($row["title"]) ?? "",
            "container_extension" => "mp4", "info" => [ "tmdb_id" => $row["tmdb_id"] ?? "", "duration_secs" => $row["duration_secs"] ?? "", "duration" => $row["duration"] ?? "", "bitrate" => $row["bitrate"] ?? "", "cover_big" => $row["cover_big"] ?? "", "movie_image" => $row["movie_image"] ?? "", "plot" => $row["plot"] ?? "" ],
            "subtitles" => !empty($row["subtitles"]) ? explode(",", $row["subtitles"]) : [], "custom_sid" => $row["custom_sid"] ?? "", "added" => $row["added"] ?? "", "season" => $season, "direct_source" => ""
        ];
    }
    header('Content-Type: application/json');
    echo json_encode(["seasons" => $seasons, "info" => $series, "episodes" => $episodes]);
    exit;
}

// Resposta padrão caso nenhuma ação seja encontrada
http_response_code(401);
$errorResponse = json_encode(["user_info" => ["auth" => 0]]);
echo $errorResponse;
exit();
?>