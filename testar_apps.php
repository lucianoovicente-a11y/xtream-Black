<?php
/**
 * Testar Apps - Simulador de Aplicativos IPTV
 * Testa se a lista roda em: IBOPro, IBOPlayer, SmartOne, TiviMate, IPTV Smarters, etc.
 */
require_once("menu.php");

if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) {
    echo '<div class="alert alert-danger">Acesso restrito ao administrador.</div>';
    exit;
}

$config_file = 'config.json';
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}
$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="/css/retro.css">
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-mobile-alt"></i> Testar Aplicativos IPTV</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Simule e teste se sua lista roda em cada aplicativo. Selecione o app, informe os dados e clique em testar.</p>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Aplicativo para Testar</label>
                                <select class="form-select" id="app_selector" onchange="showAppInfo()">
                                    <option value="ibopro">I BO Pro</option>
                                    <option value="iboplayer">I BO Player</option>
                                    <option value="smartone">SmartOne</option>
                                    <option value="tivimate">TiviMate</option>
                                    <option value="smarters">I PTV Smarters</option>
                                    <option value="vlc">VLC Media Player</option>
                                    <option value="exo">ExoPlayer</option>
                                    <option value="generic">Genérico (Xtream Codes)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Usuário</label>
                                <input type="text" class="form-control" id="test_username" placeholder="Usuário">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Senha</label>
                                <input type="password" class="form-control" id="test_password" placeholder="Senha">
                            </div>
                            
                            <div class="mb-3" id="mac_field" style="display: none;">
                                <label class="form-label">MAC Address (opcional)</label>
                                <input type="text" class="form-control" id="test_mac" placeholder="00:11:22:33:44:55:66">
                                <small class="text-muted">Alguns apps usam MAC para autenticação</small>
                            </div>
                            
                            <button class="btn btn-primary w-100 mb-3" onclick="testarApp()">
                                <i class="fas fa-play"></i> Testar Aplicativo
                            </button>
                            
                            <button class="btn btn-success w-100 mb-3" onclick="testarTodos()">
                                <i class="fas fa-check-double"></i> Testar Todos os Apps
                            </button>
                            
                            <div id="app_info" class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Informações do App</h5>
                                <p id="app_description">Selecione um aplicativo para ver detalhes.</p>
                                <div id="app_params"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Resultados dos Testes</h5>
                                    <span class="badge bg-primary" id="test_status">Aguardando...</span>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="test_results"></div>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-code"></i> URLs para Configuração</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Use estas URLs para configurar o app manualmente:</p>
                                    <div class="mb-2">
                                        <label class="form-label">Player API URL:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="player_api_url" readonly 
                                                   value="<?php echo $base_url; ?>/player_api.php">
                                            <button class="btn btn-outline-secondary" onclick="copiarTexto('player_api_url')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Get API URL:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="get_api_url" readonly 
                                                   value="<?php echo $base_url; ?>/get.php">
                                            <button class="btn btn-outline-secondary" onclick="copiarTexto('get_api_url')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">M3U Playlist URL:</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="m3u_url" readonly 
                                                   value="<?php echo $base_url; ?>/get.php?action=m3u&username=USUARIO&password=SENHA">
                                            <button class="btn btn-outline-secondary" onclick="copiarTexto('m3u_url')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const appInfo = {
    ibopro: {
        name: 'I BO Pro',
        description: 'App popular para Android TV, Fire TV e TV Boxes. Suporte a MAC address e Xtream Codes.',
        params: [
            'Username/Password ou MAC address',
            'Server URL: player_api.php',
            'EPG suportado',
            'Catch-up suportado',
            'Multi-conexão controlada'
        ],
        headers: ['X-MAC-ADDRESS', 'User-Agent: IBO Pro/...']
    },
    iboplayer: {
        name: 'I BO Player',
        description: 'Player específico para IBO, usa Xtream Codes API com parâmetros específicos.',
        params: [
            'Username/Password',
            'Server URL: player_api.php',
            'Suporte a direct streaming'
        ],
        headers: ['User-Agent: IBO Player/...']
    },
    smartone: {
        name: 'SmartOne',
        description: 'App para Android TV, suporta múltiplos formatos de lista.',
        params: [
            'Username/Password',
            'Suporte a Xtream Codes e M3U',
            'Interface simplificada'
        ],
        headers: ['User-Agent: SmartOne/...']
    },
    tivimate: {
        name: 'TiviMate',
        description: 'App premium para Android TV, muito usado com listas IPTV.',
        params: [
            'Username/Password ou MAC',
            'Suporte completo a Xtream Codes',
            'EPG avançado',
            'Grupos favoritos'
        ],
        headers: ['User-Agent: TiviMate/...', 'X-DEVICE-ID']
    },
    smarters: {
        name: 'I PTV Smarters',
        description: 'App simples para Android, aceita Xtream Codes e M3U.',
        params: [
            'Username/Password',
            'Server URL: get.php',
            'Interface básica'
        ],
        headers: ['User-Agent: IPTV Smarters/...']
    },
    vlc: {
        name: 'VLC Media Player',
        description: 'Player universal, reproduz M3U e URLs diretas.',
        params: [
            'Apenas M3U playlist',
            'Sem autenticação via API',
            'Reprodução simples'
        ],
        headers: ['User-Agent: VLC/...']
    },
    exo: {
        name: 'ExoPlayer',
        description: 'Player nativo Android, usado por muitos apps.',
        params: [
            'URLs diretas de streaming',
            'Suporte a DASH/HLS'
        ],
        headers: ['User-Agent: ExoPlayer/...']
    },
    generic: {
        name: 'Genérico (Xtream Codes)',
        description: 'Qualquer app compatível com Xtream Codes API.',
        params: [
            'Username/Password',
            'player_api.php para user info e categorias',
            'get.php para streaming'
        ],
        headers: ['User-Agent: Generic']
    }
};

function showAppInfo() {
    const app = document.getElementById('app_selector').value;
    const info = appInfo[app];
    document.getElementById('app_description').innerText = info.description;
    
    let paramsHtml = '<ul>';
    info.params.forEach(p => {
        paramsHtml += '<li>' + p + '</li>';
    });
    paramsHtml += '</ul>';
    paramsHtml += '<p><strong>Headers típicos:</strong> ' + info.headers.join(', ') + '</p>';
    document.getElementById('app_params').innerHTML = paramsHtml;
    
    // Mostra campo MAC se necessário
    const macField = document.getElementById('mac_field');
    if (app === 'ibopro' || app === 'tivimate') {
        macField.style.display = 'block';
    } else {
        macField.style.display = 'none';
    }
}

function copiarTexto(elementId) {
    const element = document.getElementById(elementId);
    navigator.clipboard.writeText(element.value).then(() => {
        Swal.fire({
            title: 'Copiado!',
            text: 'URL copiada para a área de transferência.',
            icon: 'success',
            timer: 1500,
        });
    });
}

async function testarApp() {
    const app = document.getElementById('app_selector').value;
    const username = document.getElementById('test_username').value;
    const password = document.getElementById('test_password').value;
    const mac = document.getElementById('test_mac').value;
    
    if (!username || !password) {
        Swal.fire('Erro!', 'Informe usuário e senha.', 'error');
        return;
    }
    
    document.getElementById('test_results').innerHTML = `
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ff6b35;"></i>
            <p>Testando ${appInfo[app].name}...</p>
        </div>`;
    document.getElementById('test_status').innerText = 'Testando...';
    document.getElementById('test_status').className = 'badge bg-warning';
    
    const baseUrl = window.location.origin;
    const results = [];
    
    // Test 1: Autenticação
    try {
        let authUrl = `${baseUrl}/player_api.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;
        if (mac && (app === 'ibopro' || app === 'tivimate')) {
            authUrl += `&mac=${encodeURIComponent(mac)}`;
        }
        
        const response = await fetch(authUrl);
        const data = await response.json();
        
        if (data.user_info && data.user_info.auth == 1) {
            results.push({
                test: 'Autenticação',
                status: 'success',
                message: `Usuário autenticado com sucesso! Status: ${data.user_info.status}`,
                details: data
            });
        } else {
            results.push({
                test: 'Autenticação',
                status: 'error',
                message: 'Falha na autenticação: ' + (data.user_info?.message || 'Credenciais inválidas'),
                details: data
            });
        }
    } catch (error) {
        results.push({
            test: 'Autenticação',
            status: 'error',
            message: 'Erro ao conectar: ' + error.message,
            details: null
        });
    }
    
    // Test 2: Categorias (Live)
    try {
        const response = await fetch(`${baseUrl}/player_api.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&action=get_live_categories`);
        const data = await response.json();
        
        if (Array.isArray(data) && data.length > 0) {
            results.push({
                test: 'Categorias (Ao Vivo)',
                status: 'success',
                message: `${data.length} categorias encontradas`,
                details: data.slice(0, 3)
            });
        } else {
            results.push({
                test: 'Categorias (Ao Vivo)',
                status: 'warning',
                message: 'Nenhuma categoria encontrada',
                details: data
            });
        }
    } catch (error) {
        results.push({
            test: 'Categorias (Ao Vivo)',
            status: 'error',
            message: 'Erro: ' + error.message,
            details: null
        });
    }
    
    // Test 3: Streams (Live)
    try {
        const response = await fetch(`${baseUrl}/player_api.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&action=get_live_streams`);
        const data = await response.json();
        
        if (Array.isArray(data) && data.length > 0) {
            results.push({
                test: 'Canais (Ao Vivo)',
                status: 'success',
                message: `${data.length} canais encontrados`,
                details: data.slice(0, 2)
            });
        } else {
            results.push({
                test: 'Canais (Ao Vivo)',
                status: 'warning',
                message: 'Nenhum canal encontrado',
                details: data
            });
        }
    } catch (error) {
        results.push({
            test: 'Canais (Ao Vivo)',
            status: 'error',
            message: 'Erro: ' + error.message,
            details: null
        });
    }
    
    // Test 4: VOD
    try {
        const response = await fetch(`${baseUrl}/player_api.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&action=get_vod_streams`);
        const data = await response.json();
        
        if (Array.isArray(data) && data.length > 0) {
            results.push({
                test: 'Filmes (VOD)',
                status: 'success',
                message: `${data.length} filmes encontrados`,
                details: data.slice(0, 2)
            });
        } else {
            results.push({
                test: 'Filmes (VOD)',
                status: 'warning',
                message: 'Nenhum filme encontrado',
                details: data
            });
        }
    } catch (error) {
        results.push({
            test: 'Filmes (VOD)',
            status: 'error',
            message: 'Erro: ' + error.message,
            details: null
        });
    }
    
    // Test 5: Séries
    try {
        const response = await fetch(`${baseUrl}/player_api.php?username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&action=get_series`);
        const data = await response.json();
        
        if (Array.isArray(data) && data.length > 0) {
            results.push({
                test: 'Séries',
                status: 'success',
                message: `${data.length} séries encontradas`,
                details: data.slice(0, 2)
            });
        } else {
            results.push({
                test: 'Séries',
                status: 'warning',
                message: 'Nenhuma série encontrada',
                details: data
            });
        }
    } catch (error) {
        results.push({
            test: 'Séries',
            status: 'error',
            message: 'Erro: ' + error.message,
            details: null
        });
    }
    
    // Mostra resultados
    showResults(results);
}

function showResults(results) {
    let html = '';
    let allSuccess = true;
    
    results.forEach(r => {
        if (r.status !== 'success') allSuccess = false;
        
        const badgeClass = r.status === 'success' ? 'bg-success' : (r.status === 'warning' ? 'bg-warning' : 'bg-danger');
        const icon = r.status === 'success' ? 'fa-check' : (r.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times');
        
        html += `
        <div class="card mb-2">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong><i class="fas ${icon}"></i> ${r.test}</strong>
                <span class="badge ${badgeClass}">${r.status === 'success' ? 'OK' : (r.status === 'warning' ? 'AVISO' : 'ERRO')}</span>
            </div>
            <div class="card-body py-2">
                <p class="mb-1">${r.message}</p>
                ${r.details ? `<details><summary>Ver detalhes</summary><pre style="background: #1B1E26; color: #fff; padding: 10px; border-radius: 5px; font-size: 12px;">${JSON.stringify(r.details, null, 2)}</pre></details>` : ''}
            </div>
        </div>`;
    });
    
    document.getElementById('test_results').innerHTML = html;
    document.getElementById('test_status').innerText = allSuccess ? 'Todos OK!' : 'Alguns testes falharam';
    document.getElementById('test_status').className = allSuccess ? 'badge bg-success' : 'badge bg-danger';
}

async function testarTodos() {
    const apps = ['ibopro', 'iboplayer', 'smartone', 'tivimate', 'smarters', 'vlc', 'exo', 'generic'];
    const resultsDiv = document.getElementById('test_results');
    
    resultsDiv.innerHTML = `
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ff6b35;"></i>
            <p>Testando todos os aplicativos...</p>
        </div>`;
    
    // Testa todos sequencialmente
    for (const app of apps) {
        document.getElementById('app_selector').value = app;
        showAppInfo();
        await testarApp();
        await new Promise(resolve => setTimeout(resolve, 1000)); // 1s delay
    }
}

// Inicializa
showAppInfo();
</script>

</main>
</body>
</html>
