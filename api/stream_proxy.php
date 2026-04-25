// ======================================================================
//  PROXY DE STREAMING - StreamFlow utrafix.qualidade.cloud
//  Resolve CORS e bloqueios para apps Smart/Android
// ======================================================================

error_reporting(0);
ini_set('display_errors', 0);

define('STREAMFLOW_HOST', 'https://utrafix.qualidade.cloud');
define('STREAMFLOW_P2P_HOST', 'https://utrafix.qualidade.cloud/p2p');

$url = $_GET['url'] ?? '';
$user = $_GET['user'] ?? '';
$pass = $_GET['pass'] ?? '';
$id = $_GET['id'] ?? 0;

if (empty($url) || empty($user) || empty($pass) || empty($id)) {
    http_response_code(400);
    die("Parâmetros inválidos");
}

// Decodificar URL
$stream_url = base64_decode($url);

if (empty($stream_url) || !filter_var($stream_url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die("URL inválida");
}

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Range, Authorization');
header('Access-Control-Expose-Headers: Content-Length, Content-Range');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Detectar tipo
$ext = strtolower(pathinfo($stream_url, PATHINFO_EXTENSION));
if ($ext === 'm3u8' || strpos($stream_url, '.m3u8') !== false) {
    header('Content-Type: application/vnd.apple.mpegurl');
} elseif ($ext === 'ts') {
    header('Content-Type: video/mp2ts');
    header('Content-Transfer-Encoding: binary');
} elseif ($ext === 'mp4') {
    header('Content-Type: video/mp4');
} elseif ($ext === 'mp3' || $ext === 'aac') {
    header('Content-Type: audio/mpeg');
} else {
    header('Content-Type: application/octet-stream');
}

// Simular user-agent de app popular
$userAgents = [
    'Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'VLC/3.0.0',
    'ExoPlayer/2.19'
];
$userAgent = $userAgents[array_rand($userAgents)];

// Verificar se é StreamFlow utrafix
$is_streamflow = (
    strpos($stream_url, 'utrafix.qualidade.cloud') !== false || 
    strpos($stream_url, 'streamflow') !== false || 
    strpos($stream_url, 'sempre.sbs') !== false
);

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $stream_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Streaming direto
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

// Headers para StreamFlow utrafix
$headers = [
    'Accept: */*',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Referer: ' . STREAMFLOW_HOST . '/',
    'Origin: ' . STREAMFLOW_HOST
];

// Se for StreamFlow utrafix, adicionar headers específicos
if ($is_streamflow) {
    $headers[] = 'X-Requested-With: XMLHttpRequest';
    $headers[] = 'Sec-Fetch-Mode: cors';
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Executar request
curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    http_response_code(502);
    die("Erro ao conectar: " . $error);
}

if ($http_code >= 400) {
    http_response_code($http_code);
    die("Erro HTTP: " . $http_code);
}