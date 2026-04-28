<?php
/**
 * Área do Cliente Completa - XTREAM SERVER
 * 
 * Features:
 * - Histórico de conexões
 * - Pagamento de mensalidades
 * - Abertura de tickets
 * - Cancelamento de assinatura
 * - Dados da conta
 */

session_start();
require_once '../api/controles/db.php';
require_once '../classes/ConnectionManager.class.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$pdo = conectar_bd();
$username = $_SESSION['username'];
$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'abrir_ticket':
                $assunto = filter_input(INPUT_POST, 'assunto', FILTER_SANITIZE_STRING);
                $mensagem_ticket = filter_input(INPUT_POST, 'mensagem', FILTER_SANITIZE_STRING);
                $prioridade = filter_input(INPUT_POST, 'prioridade', FILTER_SANITIZE_STRING);
                
                if ($assunto && $mensagem_ticket) {
                    $stmt = $pdo->prepare("INSERT INTO tickets (cliente_id, assunto, mensagem, prioridade, status) 
                                          SELECT id, ?, ?, ?, 'aberto' FROM clientes WHERE usuario = ?");
                    $stmt->execute([$assunto, $mensagem_ticket, $prioridade, $username]);
                    $mensagem = 'Ticket aberto com sucesso!';
                    $tipo_mensagem = 'success';
                }
                break;
                
            case 'cancelar_assinatura':
                $confirmacao = filter_input(INPUT_POST, 'confirmacao', FILTER_SANITIZE_STRING);
                if ($confirmacao === 'SIM') {
                    $stmt = $pdo->prepare("UPDATE clientes SET status = 'cancelado', Vencimento = NOW() WHERE usuario = ?");
                    $stmt->execute([$username]);
                    $mensagem = 'Assinatura cancelada. Sentimos muito!';
                    $tipo_mensagem = 'warning';
                }
                break;
                
            case 'atualizar_dados':
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
                
                if ($email) {
                    $stmt = $pdo->prepare("UPDATE clientes SET email = ?, telefone = ? WHERE usuario = ?");
                    $stmt->execute([$email, $telefone, $username]);
                    $mensagem = 'Dados atualizados com sucesso!';
                    $tipo_mensagem = 'success';
                }
                break;
        }
    }
}

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario = ?");
$stmt->execute([$username]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Histórico de conexões
$cm = new ConnectionManager();
$conexoes = $cm->getUserConnectionHistory($cliente['id'], 50);

// Tickets do cliente
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE cliente_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$cliente['id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Histórico de pagamentos
$stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE cliente_id = ? ORDER BY data_pagamento DESC LIMIT 20");
$stmt->execute([$cliente['id']]);
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status da conta
$status = 'Ativo';
$data_vencimento = 'Não disponível';

if (!empty($cliente['Vencimento'])) {
    $timestamp_vencimento = strtotime($cliente['Vencimento']);
    $timestamp_atual = time();
    
    if ($timestamp_vencimento > $timestamp_atual) {
        $data_vencimento = date('d/m/Y H:i', $timestamp_vencimento);
        $status = 'Ativo';
    } elseif ($cliente['status'] === 'cancelado') {
        $data_vencimento = date('d/m/Y', $timestamp_vencimento) . ' (Cancelado)';
        $status = 'Cancelado';
    } else {
        $data_vencimento = date('d/m/Y', $timestamp_vencimento) . ' (Vencido)';
        $status = 'Vencido';
    }
}

// Conexões ativas atuais
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM conexoes_ativas WHERE cliente_id = ? AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute([$cliente['id']]);
$conexoes_ativas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Área do Cliente - XTREAM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            background: var(--secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }
        
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover, .tab-btn.active {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .card h3 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--secondary);
        }
        
        .info-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 18px;
            color: var(--dark);
            font-weight: 600;
            margin-top: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .status-ativo { background: var(--success); }
        .status-vencido { background: var(--danger); }
        .status-cancelado { background: var(--warning); }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        tr:hover {
            background: var(--light);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #22c55e;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 20px;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            transition: width 0.5s;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> Área do Cliente</h1>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                    <br>
                    <small style="color: var(--gray);"><?php echo htmlspecialchars($cliente['email'] ?? ''); ?></small>
                </div>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>
        
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="showTab('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </button>
            <button class="tab-btn" onclick="showTab('conexoes')">
                <i class="fas fa-wifi"></i> Conexões
            </button>
            <button class="tab-btn" onclick="showTab('pagamentos')">
                <i class="fas fa-credit-card"></i> Pagamentos
            </button>
            <button class="tab-btn" onclick="showTab('tickets')">
                <i class="fas fa-ticket-alt"></i> Tickets
            </button>
            <button class="tab-btn" onclick="showTab('dados')">
                <i class="fas fa-user-cog"></i> Meus Dados
            </button>
            <button class="tab-btn" onclick="showTab('cancelar')">
                <i class="fas fa-times-circle"></i> Cancelar
            </button>
        </div>
        
        <!-- DASHBOARD -->
        <div id="dashboard" class="tab-content active">
            <h2><i class="fas fa-chart-line"></i> Visão Geral</h2>
            <br>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo strtolower($status); ?>">
                            <?php echo $status; ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Vencimento</div>
                    <div class="info-value"><?php echo $data_vencimento; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Conexões Ativas</div>
                    <div class="info-value">
                        <?php echo $conexoes_ativas; ?> / <?php echo $cliente['max_conexoes'] ?? 1; ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($conexoes_ativas / ($cliente['max_conexoes'] ?? 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Plano</div>
                    <div class="info-value"><?php echo htmlspecialchars($cliente['plano'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <br>
            <a href="gerar_pagamento.php" class="btn btn-success">
                <i class="fas fa-dollar-sign"></i> Renovar Assinatura
            </a>
        </div>
        
        <!-- CONEXÕES -->
        <div id="conexoes" class="tab-content">
            <h2><i class="fas fa-wifi"></i> Histórico de Conexões</h2>
            <p style="color: var(--gray); margin-bottom: 20px;">Últimas 50 conexões realizadas</p>
            
            <?php if (empty($conexoes)): ?>
                <p>Nenhuma conexão registrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Tipo</th>
                            <th>IP</th>
                            <th>Dispositivo</th>
                            <th>Duração</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conexoes as $conn): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($conn['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php echo $conn['tipo_conexao'] === 'live' ? '#3b82f6' : '#8b5cf6'; ?>">
                                        <?php echo $conn['tipo_conexao'] === 'live' ? 'TV Ao Vivo' : ($conn['tipo_conexao'] === 'movie' ? 'Filme' : 'Série'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($conn['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars(substr($conn['user_agent'] ?? 'Desconhecido', 0, 30)); ?>...</td>
                                <td>
                                    <?php 
                                    if ($conn['checkout_time']) {
                                        $duration = strtotime($conn['checkout_time']) - strtotime($conn['checkin_time']);
                                        echo gmdate('H\h i\m s\s', $duration);
                                    } else {
                                        echo '<span style="color: var(--success);">Online</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($conn['checkout_time']): ?>
                                        <span class="status-badge status-vencido">Finalizada</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ativo">Ativa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- PAGAMENTOS -->
        <div id="pagamentos" class="tab-content">
            <h2><i class="fas fa-credit-card"></i> Histórico de Pagamentos</h2>
            
            <?php if (empty($pagamentos)): ?>
                <p>Nenhum pagamento registrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Valor</th>
                            <th>Método</th>
                            <th>Status</th>
                            <th>Referência</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagamentos as $pag): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($pag['data_pagamento'])); ?></td>
                                <td>R$ <?php echo number_format($pag['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($pag['metodo']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $pag['status'] === 'aprovado' ? 'ativo' : 'vencido'; ?>">
                                        <?php echo ucfirst($pag['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($pag['referencia'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <br>
            <a href="gerar_pagamento.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Pagamento
            </a>
        </div>
        
        <!-- TICKETS -->
        <div id="tickets" class="tab-content">
            <h2><i class="fas fa-ticket-alt"></i> Meus Tickets</h2>
            
            <div class="card">
                <h3>Abrir Novo Ticket</h3>
                <form method="POST">
                    <input type="hidden" name="acao" value="abrir_ticket">
                    <div class="form-group">
                        <label>Assunto</label>
                        <input type="text" name="assunto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select name="prioridade" class="form-control">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mensagem</label>
                        <textarea name="mensagem" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Abrir Ticket
                    </button>
                </form>
            </div>
            
            <h3>Tickets Anteriores</h3>
            <?php if (empty($tickets)): ?>
                <p>Nenhum ticket encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Assunto</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['assunto']); ?></td>
                                <td>
                                    <span class="status-badge" style="background: <?php 
                                        echo $ticket['prioridade'] === 'urgente' ? '#ef4444' : 
                                            ($ticket['prioridade'] === 'alta' ? '#f59e0b' : 
                                            ($ticket['prioridade'] === 'media' ? '#3b82f6' : '#22c55e')); 
                                    ?>">
                                        <?php echo ucfirst($ticket['prioridade']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $ticket['status'] === 'aberto' ? 'ativo' : 'vencido'; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- DADOS -->
        <div id="dados" class="tab-content">
            <h2><i class="fas fa-user-cog"></i> Meus Dados</h2>
            
            <div class="card">
                <h3>Informações da Conta</h3>
                <form method="POST">
                    <input type="hidden" name="acao" value="atualizar_dados">
                    <div class="info-grid">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($username); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" class="form-control" value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Plano</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['plano'] ?? 'N/A'); ?>" disabled>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
        
        <!-- CANCELAR -->
        <div id="cancelar" class="tab-content">
            <h2><i class="fas fa-exclamation-triangle"></i> Cancelar Assinatura</h2>
            
            <div class="alert alert-warning">
                <strong>Atenção!</strong> O cancelamento é irreversível. Você perderá acesso imediatamente ao conteúdo.
            </div>
            
            <div class="card">
                <h3>Confirmar Cancelamento</h3>
                <form method="POST" onsubmit="return confirm('Tem CERTEZA que deseja cancelar? Esta ação não pode ser desfeita!');">
                    <input type="hidden" name="acao" value="cancelar_assinatura">
                    <div class="form-group">
                        <label>Digite "SIM" para confirmar o cancelamento:</label>
                        <input type="text" name="confirmacao" class="form-control" required placeholder="Digite SIM">
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Cancelar Assinatura
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabId) {
            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and content
            event.target.closest('.tab-btn').classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</body>
</html>
