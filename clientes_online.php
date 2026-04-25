<?php
require_once("menu.php");

if (!isset($_SESSION['nivel_admin']) || $_SESSION['nivel_admin'] != 1) {
    http_response_code(403);
    echo '<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Proibido</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: "Rajdhani", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .access-denied {
            text-align: center;
            padding: 60px;
            background: #161b22;
            border-radius: 20px;
            border: 1px solid #30363d;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        .access-denied h1 {
            font-family: "Orbitron", sans-serif;
            font-size: 1.5rem;
            color: #f85149;
            margin-bottom: 20px;
        }
        .access-denied p {
            font-size: 1.3rem;
            color: #8b949e;
        }
        .access-denied a {
            display: inline-block;
            margin-top: 30px;
            color: #58a6ff;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .access-denied a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="access-denied">
        <h1><i class="fas fa-shield-alt"></i> ACESSO RESTRITO</h1>
        <p>Apenas administradores podem acessar esta página.</p>
        <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Voltar ao Painel</a>
    </div>
</body>
</html>';
    exit;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
.monitor-header {
    background: linear-gradient(135deg, #161b22 0%, #0d1117 100%);
    border-bottom: 1px solid #30363d;
    padding: 25px 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.monitor-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #58a6ff;
    letter-spacing: 2px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.monitor-subtitle {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1rem;
    color: #8b949e;
    margin-top: 5px;
    font-weight: 400;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.monitor-btn {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.monitor-btn-refresh {
    background: linear-gradient(135deg, #1f6feb 0%, #1158c7 100%);
    border: 1px solid #58a6ff;
    color: #fff;
}

.monitor-btn-refresh:hover {
    box-shadow: 0 0 25px rgba(88, 166, 255, 0.4);
    transform: translateY(-2px);
}

.monitor-btn-danger {
    background: linear-gradient(135deg, #da3633 0%, #b62324 100%);
    border: 1px solid #f85149;
    color: #fff;
}

.monitor-btn-danger:hover {
    box-shadow: 0 0 25px rgba(248, 81, 73, 0.4);
    transform: translateY(-2px);
}

.monitor-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 30px 35px;
}

.monitor-stat-card {
    background: linear-gradient(135deg, #161b22 0%, #21262d 100%);
    border: 1px solid #30363d;
    border-radius: 16px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.3s;
}

.monitor-stat-card:hover {
    border-color: #58a6ff;
    box-shadow: 0 8px 30px rgba(88, 166, 255, 0.15);
}

.monitor-stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    border-radius: 12px;
}

.monitor-stat-icon.connections {
    background: rgba(88, 166, 255, 0.15);
    color: #58a6ff;
    border: 1px solid rgba(88, 166, 255, 0.3);
}

.monitor-stat-icon.clients {
    background: rgba(63, 185, 80, 0.15);
    color: #3fb950;
    border: 1px solid rgba(63, 185, 80, 0.3);
}

.monitor-stat-info h3 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #8b949e;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.monitor-stat-info p {
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #58a6ff;
    margin: 5px 0 0 0;
}

.monitor-table-wrapper {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 16px;
    margin: 0 35px 35px 35px;
    overflow: hidden;
}

.monitor-table-header {
    padding: 20px 25px;
    border-bottom: 1px solid #30363d;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.monitor-table-title {
    font-family: 'Orbitron', sans-serif;
    font-size: 0.85rem;
    color: #58a6ff;
    letter-spacing: 2px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.monitor-table {
    width: 100%;
    border-collapse: collapse;
}

.monitor-table th {
    background: #0d1117;
    color: #8b949e;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 16px 20px;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px solid #30363d;
}

.monitor-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #21262d;
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.05rem;
    color: #c9d1d9;
}

.monitor-table tbody tr {
    transition: all 0.2s;
}

.monitor-table tbody tr:hover {
    background: rgba(88, 166, 255, 0.05);
}

.monitor-table tbody tr:last-child td {
    border-bottom: none;
}

.user-cell strong {
    color: #58a6ff;
    font-weight: 600;
}

.user-cell small {
    display: block;
    color: #8b949e;
    font-size: 0.9rem;
    margin-top: 3px;
}

.ip-cell {
    font-family: 'Share Tech Mono', monospace;
    color: #8b949e;
}

.channel-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.channel-thumb {
    width: 50px;
    height: 35px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #30363d;
}

.channel-info {
    display: flex;
    flex-direction: column;
}

.channel-name {
    font-weight: 600;
    color: #c9d1d9;
    display: flex;
    align-items: center;
    gap: 8px;
}

.watching-dot {
    width: 8px;
    height: 8px;
    background: #3fb950;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.9); }
}

.channel-type {
    font-size: 0.85rem;
    color: #8b949e;
}

.time-cell {
    font-family: 'Share Tech Mono', monospace;
    color: #d29922;
}

.activity-cell {
    color: #8b949e;
    font-size: 0.95rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.watching {
    background: rgba(63, 185, 80, 0.15);
    color: #3fb950;
    border: 1px solid rgba(63, 185, 80, 0.3);
}

.status-badge.online {
    background: rgba(88, 166, 255, 0.15);
    color: #58a6ff;
    border: 1px solid rgba(88, 166, 255, 0.3);
}

.kick-btn {
    background: linear-gradient(135deg, #da3633 0%, #b62324 100%);
    border: none;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    font-size: 0.9rem;
}

.kick-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 0 20px rgba(248, 81, 73, 0.4);
}

.loading-row td {
    text-align: center;
    padding: 60px 20px;
    color: #8b949e;
}

.loading-row i {
    font-size: 2rem;
    color: #58a6ff;
    animation: spin 1s linear infinite;
    display: block;
    margin-bottom: 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-row td {
    text-align: center;
    padding: 60px 20px;
    color: #8b949e;
}

.empty-row i {
    font-size: 3rem;
    color: #30363d;
    display: block;
    margin-bottom: 15px;
}

@media (max-width: 992px) {
    .monitor-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .monitor-stats {
        padding: 20px;
    }
    
    .monitor-table-wrapper {
        margin: 0 20px 20px 20px;
        overflow-x: auto;
    }
    
    .monitor-table {
        min-width: 800px;
    }
}
</style>

<div class="monitor-header">
    <div>
        <div class="monitor-title">
            <i class="fas fa-satellite-dish"></i> MONITOR DE USUÁRIOS ONLINE
        </div>
        <div class="monitor-subtitle">Tempo real • Atualização a cada 5 segundos</div>
    </div>
    <div class="header-actions">
        <button id="refreshBtn" class="monitor-btn monitor-btn-refresh" onclick="atualizarDados()">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
        <button id="kickAllButton" class="monitor-btn monitor-btn-danger">
            <i class="fas fa-bomb"></i> Derrubar Tudo
        </button>
    </div>
</div>

<div class="monitor-stats">
    <div class="monitor-stat-card">
        <div class="monitor-stat-icon connections">
            <i class="fas fa-signal"></i>
        </div>
        <div class="monitor-stat-info">
            <h3>Total de Conexões</h3>
            <p id="total-online">0</p>
        </div>
    </div>
    
    <div class="monitor-stat-card">
        <div class="monitor-stat-icon clients">
            <i class="fas fa-users"></i>
        </div>
        <div class="monitor-stat-info">
            <h3>Clientes Multi-Conexão</h3>
            <p id="clientes-multiplas-conexoes">0</p>
        </div>
    </div>
</div>

<div class="monitor-table-wrapper">
    <div class="monitor-table-header">
        <div class="monitor-table-title">
            <i class="fas fa-list"></i> Conexões Ativas
        </div>
    </div>
    <table class="monitor-table">
        <thead>
            <tr>
                <th>Usuário</th>
                <th>IP</th>
                <th>Assistindo</th>
                <th>Tempo Online</th>
                <th>Última Atividade</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="tabela-atividade">
            <tr class="loading-row">
                <td colspan="7">
                    <i class="fas fa-spinner"></i>
                    <div style="margin-top: 10px; font-family: 'Rajdhani', sans-serif; font-size: 1rem;">Carregando dados...</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
async function atualizarDados() {
    const refreshBtn = document.getElementById('refreshBtn');
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
    refreshBtn.disabled = true;
    
    try {
        const response = await fetch('api/api_dashboard.php');
        const contentType = response.headers.get("content-type");
        
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Erro no servidor");
        }

        const data = await response.json();

        document.getElementById('total-online').innerText = data.online_count || 0;
        document.getElementById('clientes-multiplas-conexoes').innerText = data.multi_connection_count || 0;

        const tabelaCorpo = document.getElementById('tabela-atividade');
        
        if (!data.activity || data.activity.length === 0) {
            tabelaCorpo.innerHTML = `
                <tr class="empty-row">
                    <td colspan="7">
                        <i class="fas fa-user-slash"></i>
                        <div style="margin-top: 10px; font-size: 1.1rem;">Nenhum usuário online no momento</div>
                    </td>
                </tr>`;
            return;
        }

        tabelaCorpo.innerHTML = '';

        data.activity.forEach(item => {
            const sessionId = item.id;
            const statusInativos = ["Menu Principal", "Nenhum canal ativo", "N/A", "Menu/Offline", "menu", "offline", ""];
            const isWatching = !statusInativos.includes(item.canal_atual) && item.is_watching;
            
            let canalHtml = '';
            if (isWatching && item.canal_atual) {
                let tipoIcone = '📺';
                if (item.tipo_conteudo && item.tipo_conteudo.includes('Filme')) tipoIcone = '🎬';
                
                canalHtml = `
                    <div class="channel-cell">
                        ${item.canal_icon ? `<img src="${item.canal_icon}" class="channel-thumb" onerror="this.style.display='none'">` : ''}
                        <div class="channel-info">
                            <div class="channel-name">
                                <span class="watching-dot"></span>
                                ${item.canal_atual}
                            </div>
                            <div class="channel-type">${item.tipo_conteudo}</div>
                        </div>
                    </div>`;
            } else {
                canalHtml = `<span style="color: #8b949e;">${item.canal_atual || 'N/A'}</span>`;
            }

            const linha = `
            <tr data-session-id="${sessionId}">
                <td class="user-cell">
                    <strong>${item.usuario}</strong>
                    <small>${item.conexoes_total} conexão${item.conexoes_total > 1 ? 's' : ''}</small>
                </td>
                <td class="ip-cell">${item.ip}</td>
                <td>${canalHtml}</td>
                <td class="time-cell"><i class="fas fa-clock" style="color: #d29922; margin-right: 6px;"></i>${item.tempo_online}</td>
                <td class="activity-cell">${item.ultima_atividade}</td>
                <td>
                    ${isWatching 
                        ? '<span class="status-badge watching"><i class="fas fa-play"></i> Assistindo</span>' 
                        : '<span class="status-badge online"><i class="fas fa-check"></i> Online</span>'}
                </td>
                <td>
                    <button class="kick-btn" data-id="${sessionId}" title="Derrubar Sessão">
                        <i class="fas fa-power-off"></i>
                    </button>
                </td>
            </tr>`;
            tabelaCorpo.innerHTML += linha;
        });

    } catch (error) {
        console.error("Erro:", error);
        document.getElementById('tabela-atividade').innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #f85149;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <div>Erro ao carregar dados: ${error.message}</div>
                </td>
            </tr>`;
    } finally {
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Atualizar';
        refreshBtn.disabled = false;
    }
}

document.addEventListener('click', async function(event) {
    if (event.target.closest('.kick-btn')) {
        const button = event.target.closest('.kick-btn');
        const sessionId = button.dataset.id;
        const row = button.closest('tr');
        const userName = row ? row.querySelector('strong').innerText : sessionId;

        const result = await Swal.fire({
            title: 'Confirmar ação?',
            html: `Derrubar sessão do usuário <strong>${userName}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#da3633',
            cancelButtonColor: '#30363d',
            confirmButtonText: 'Sim, derrubar!',
            cancelButtonText: 'Cancelar',
            background: '#161b22',
            color: '#c9d1d9'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`api/kick_session.php?session_id=${sessionId}`);
                const result_api = await response.json();

                if (result_api.success) {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Sessão derrubada com sucesso!',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        background: '#161b22',
                        color: '#c9d1d9'
                    });
                    button.closest('tr').remove();
                    atualizarDados();
                } else {
                    Swal.fire('Erro!', result_api.message || 'Erro ao derrubar sessão.', 'error', {
                        background: '#161b22',
                        color: '#c9d1d9'
                    });
                }
            } catch (error) {
                Swal.fire('Erro!', 'Erro de comunicação com o servidor.', 'error', {
                    background: '#161b22',
                    color: '#c9d1d9'
                });
            }
        }
    }
});

document.getElementById('kickAllButton').addEventListener('click', async function() {
    const result = await Swal.fire({
        title: '⚠️ Atenção Máxima!',
        html: 'Derrubar <strong>TODAS</strong> as conexões?<br>Todos os clientes serão desconectados!',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#da3633',
        cancelButtonColor: '#30363d',
        confirmButtonText: 'Sim, derrubar tudo!',
        cancelButtonText: 'Cancelar',
        background: '#161b22',
        color: '#c9d1d9'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('api/kick_all_sessions.php');
            const result_api = await response.json();

            if (result_api.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: 'Todas as sessões foramderrubadas!',
                    icon: 'success',
                    background: '#161b22',
                    color: '#c9d1d9'
                });
                atualizarDados();
            } else {
                Swal.fire('Erro!', result_api.message || 'Erro ao derrubar sessões.', 'error', {
                    background: '#161b22',
                    color: '#c9d1d9'
                });
            }
        } catch (error) {
            Swal.fire('Erro!', 'Erro de comunicação.', 'error', {
                background: '#161b22',
                color: '#c9d1d9'
            });
        }
    }
});

atualizarDados();
setInterval(atualizarDados, 5000);
</script>

</main>
</body>
</html>