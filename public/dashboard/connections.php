<?php
/**
 * Dashboard de Conexões em Tempo Real
 * Interface administrativa para monitoramento de conexões ativas
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/ConnectionManager.class.php';
require_once __DIR__ . '/../menu.php';

// Verifica se está logado
if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

$connectionManager = new ConnectionManager();
$stats = $connectionManager->getConnectionStats();

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'set_limit') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $maxConnections = filter_input(INPUT_POST, 'max_connections', FILTER_VALIDATE_INT);
        
        if ($userId && $maxConnections !== null) {
            $success = $connectionManager->setUserMaxConnections($userId, $maxConnections);
            $message = $success ? 
                ['type' => 'success', 'text' => 'Limite atualizado com sucesso!'] : 
                ['type' => 'error', 'text' => 'Erro ao atualizar limite.'];
        }
    } elseif ($action === 'close_session') {
        $sessionId = $_POST['session_id'] ?? '';
        if ($sessionId) {
            $connectionManager->closeConnection($sessionId);
            $message = ['type' => 'success', 'text' => 'Conexão finalizada com sucesso!'];
        }
    } elseif ($action === 'close_all_user') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($userId) {
            $connectionManager->closeAllUserConnections($userId);
            $message = ['type' => 'success', 'text' => 'Todas as conexões do usuário finalizadas!'];
        }
    }
}

// Obtém lista de usuários com conexões ativas
try {
    $db = conectar_bd();
    $sql = "SELECT DISTINCT c.user_id, u.username, u.max_connections, 
                   COUNT(c.id) as active_connections
            FROM active_connections c
            JOIN users u ON c.user_id = u.id
            WHERE c.is_active = 1
            GROUP BY c.user_id, u.username, u.max_connections
            ORDER BY active_connections DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $usersWithConnections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usersWithConnections = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Conexões - XTREAM SERVER</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="/public/assets/css/layout-compacto.css?v=<?php echo time(); ?>">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: bold;
        }
        .stats-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .stats-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .connection-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .connection-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 15px;
        }
        .connection-table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-limit {
            background: #fff3cd;
            color: #856404;
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 0.85rem;
            margin: 2px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 1000;
        }
        .auto-refresh {
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-size: 0.9rem;
            z-index: 1000;
        }
        .progress-connections {
            height: 10px;
            border-radius: 5px;
            background: #e9ecef;
            overflow: hidden;
        }
        .progress-connections .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        .progress-connections .progress-bar.warning {
            background: linear-gradient(90deg, #f093fb, #f5576c);
        }
        .progress-connections .progress-bar.danger {
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
        }
    </style>
</head>
<body>
    <?php montaMenu(2); ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fa fa-sitemap"></i> Dashboard de Conexões em Tempo Real</h2>
                    <p class="text-muted">Monitore e gerencie todas as conexões ativas do sistema</p>
                </div>
            </div>
            
            <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php endif; ?>
            
            <!-- Cards de Estatísticas -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['total_active']; ?></h3>
                        <p><i class="fa fa-wifi"></i> Conexões Ativas</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card success">
                        <h3><?php echo count($usersWithConnections); ?></h3>
                        <p><i class="fa fa-users"></i> Usuários Online</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card info">
                        <h3>
                            <?php 
                            $liveCount = 0;
                            foreach ($stats['by_type'] as $type) {
                                if ($type['connection_type'] === 'live') {
                                    $liveCount = $type['count'];
                                }
                            }
                            echo $liveCount;
                            ?>
                        </h3>
                        <p><i class="fa fa-tv"></i> Canais Ao Vivo</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card warning">
                        <h3>
                            <?php 
                            $vodCount = 0;
                            foreach ($stats['by_type'] as $type) {
                                if (in_array($type['connection_type'], ['vod', 'series'])) {
                                    $vodCount += $type['count'];
                                }
                            }
                            echo $vodCount;
                            ?>
                        </h3>
                        <p><i class="fa fa-film"></i> VOD/Séries</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Usuários com Conexões -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="connection-table">
                        <div class="p-3 bg-light border-bottom">
                            <h5 class="mb-0"><i class="fa fa-list"></i> Usuários com Conexões Ativas</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Conexões Ativas</th>
                                        <th>Limite Máximo</th>
                                        <th>Uso</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($usersWithConnections)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fa fa-info-circle"></i> Nenhuma conexão ativa no momento
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($usersWithConnections as $user): ?>
                                            <?php 
                                            $usagePercent = ($user['active_connections'] / $user['max_connections']) * 100;
                                            $progressClass = '';
                                            if ($usagePercent >= 100) {
                                                $progressClass = 'danger';
                                            } elseif ($usagePercent >= 80) {
                                                $progressClass = 'warning';
                                            }
                                            ?>
                                        <tr>
                                            <td><?php echo $user['user_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                            <td>
                                                <span class="badge badge-primary"><?php echo $user['active_connections']; ?></span>
                                            </td>
                                            <td><?php echo $user['max_connections']; ?></td>
                                            <td style="width: 200px;">
                                                <div class="progress-connections">
                                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                                         style="width: <?php echo min($usagePercent, 100); ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo number_format($usagePercent, 1); ?>%</small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-action" 
                                                        onclick="viewUserConnections(<?php echo $user['user_id']; ?>)"
                                                        title="Ver detalhes">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-action" 
                                                        onclick="editLimit(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['max_connections']; ?>)"
                                                        title="Editar limite">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action" 
                                                        onclick="confirmCloseAll(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                        title="Desconectar todos">
                                                    <i class="fa fa-power-off"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Usuários -->
            <?php if (!empty($stats['top_users'])): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="connection-table">
                        <div class="p-3 bg-light border-bottom">
                            <h5 class="mb-0"><i class="fa fa-trophy"></i> Top 10 Usuários com Mais Conexões</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Usuário</th>
                                        <th>Conexões Ativas</th>
                                        <th>Limite</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['top_users'] as $index => $topUser): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index === 0): ?>
                                                <i class="fa fa-trophy text-warning"></i>
                                            <?php elseif ($index === 1): ?>
                                                <i class="fa fa-medal text-secondary"></i>
                                            <?php elseif ($index === 2): ?>
                                                <i class="fa fa-medal text-danger"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($topUser['username']); ?></strong></td>
                                        <td><?php echo $topUser['active_connections']; ?></td>
                                        <td><?php echo $topUser['max_connections']; ?></td>
                                        <td>
                                            <?php if ($topUser['active_connections'] >= $topUser['max_connections']): ?>
                                                <span class="status-badge status-limit">No Limite</span>
                                            <?php else: ?>
                                                <span class="status-badge status-active">Ativo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Botão de Refresh -->
    <button class="btn btn-primary refresh-btn" onclick="location.reload()" title="Atualizar">
        <i class="fa fa-sync"></i>
    </button>
    
    <!-- Auto Refresh Indicator -->
    <div class="auto-refresh">
        <small><i class="fa fa-clock"></i> Atualização automática: <strong>30s</strong></small>
    </div>
    
    <!-- Modal para Editar Limite -->
    <div class="modal fade" id="limitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="set_limit">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Limite de Conexões</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Usuário: <strong id="modalUsername"></strong></p>
                        <div class="form-group">
                            <label for="maxConnections">Novo Limite de Conexões:</label>
                            <input type="number" class="form-control" id="maxConnections" name="max_connections" 
                                   min="1" max="100" required>
                            <small class="form-text text-muted">
                                Defina o número máximo de conexões simultâneas permitidas.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal para Ver Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes das Conexões</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Carregando...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        // Auto refresh a cada 30 segundos
        setInterval(function() {
            location.reload();
        }, 30000);
        
        function editLimit(userId, username, currentLimit) {
            $('#modalUserId').val(userId);
            $('#modalUsername').text(username);
            $('#maxConnections').val(currentLimit);
            $('#limitModal').modal('show');
        }
        
        function confirmCloseAll(userId, username) {
            if (confirm('Tem certeza que deseja desconectar TODAS as conexões do usuário ' + username + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="close_all_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewUserConnections(userId) {
            $('#detailsModal').modal('show');
            $.ajax({
                url: 'api/connection.php/user/' + userId,
                method: 'GET',
                success: function(data) {
                    let html = '<table class="table table-sm">';
                    html += '<thead><tr><th>Sessão</th><th>IP</th><th>Tipo</th><th>Início</th><th>Último Heartbeat</th><th>Ações</th></tr></thead><tbody>';
                    
                    if (data.connections && data.connections.length > 0) {
                        data.connections.forEach(function(conn) {
                            html += `<tr>
                                <td><small>${conn.session_id.substring(0, 12)}...</small></td>
                                <td>${conn.ip_address}</td>
                                <td><span class="badge badge-info">${conn.connection_type}</span></td>
                                <td>${conn.started_at}</td>
                                <td>${conn.last_heartbeat}</td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="close_session">
                                        <input type="hidden" name="session_id" value="${conn.session_id}">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Desconectar esta sessão?')">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>`;
                        });
                    } else {
                        html += '<tr><td colspan="6" class="text-center">Nenhuma conexão encontrada</td></tr>';
                    }
                    
                    html += '</tbody></table>';
                    $('#detailsContent').html(html);
                },
                error: function() {
                    $('#detailsContent').html('<div class="alert alert-danger">Erro ao carregar detalhes</div>');
                }
            });
        }
    </script>
    <script src="/public/assets/js/connections-live.js?v=<?php echo time(); ?>"></script>
</body>
</html>
