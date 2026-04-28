<?php
require_once 'config.php';

$acao = $_GET['acao'] ?? 'dashboard';
$mensagem = '';

if ($_POST) {
    if (isset($_POST['salvar_cliente'])) {
        $stmt = $conn->prepare("INSERT INTO clientes (nome, telefone, email, whatsapp, obs) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['telefone'], $_POST['email'], $_POST['whatsapp'], $_POST['obs']]);
        $mensagem = 'Cliente cadastrado com sucesso!';
    }
    
    if (isset($_POST['salvar_servico'])) {
        $stmt = $conn->prepare("INSERT INTO servicos (nome, preco, duracao, descricao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['nome'], $_POST['preco'], $_POST['duracao'], $_POST['descricao']]);
        $mensagem = 'Serviço cadastrado com sucesso!';
    }
    
    if (isset($_POST['salvar_agendamento'])) {
        $stmt = $conn->prepare("INSERT INTO agendamentos (cliente_id, servico_id, data_hora, obs) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['cliente_id'], $_POST['servico_id'], $_POST['data_hora'], $_POST['obs']]);
        $mensagem = 'Agendamento realizado com sucesso!';
    }
    
    if (isset($_POST['salvar_despesa'])) {
        $stmt = $conn->prepare("INSERT INTO despesas (descricao, valor, categoria, data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['descricao'], $_POST['valor'], $_POST['categoria'], $_POST['data']]);
        $mensagem = 'Despesa cadastrada com sucesso!';
    }
    
    if (isset($_POST['salvar_cobranca'])) {
        $stmt = $conn->prepare("INSERT INTO cobrancas (cliente_id, valor, descricao, data_vencimento) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['cliente_id'], $_POST['valor'], $_POST['descricao'], $_POST['data_vencimento']]);
        $mensagem = 'Cobrança cadastrada com sucesso!';
    }
    
    if (isset($_POST['pagar_cobranca'])) {
        $stmt = $conn->prepare("UPDATE cobrancas SET status = 'pago', data_pagamento = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d'), $_POST['cobranca_id']]);
        $mensagem = 'Cobrança marcada como paga!';
    }
    
    if (isset($_POST['finalizar_agendamento'])) {
        $stmt = $conn->prepare("UPDATE agendamentos SET status = 'concluido', valor_pago = ? WHERE id = ?");
        $stmt->execute([$_POST['valor_pago'], $_POST['agendamento_id']]);
        $mensagem = 'Agendamento concluído!';
    }
    
    if (isset($_POST['cancelar_agendamento'])) {
        $stmt = $conn->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?");
        $stmt->execute([$_POST['agendamento_id']]);
        $mensagem = 'Agendamento cancelado!';
    }
    
    if (isset($_POST['salvar_config'])) {
        $stmt = $conn->prepare("UPDATE config SET nome_salao = ?, whatsapp = ?, mensagem_confirmacao = ? WHERE id = 1");
        $stmt->execute([$_POST['nome_salao'], $_POST['whatsapp'], $_POST['mensagem_confirmacao']]);
        $mensagem = 'Configurações salvas!';
    }
    
    if (isset($_POST['excluir_cliente'])) {
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$_POST['cliente_id']]);
        $mensagem = 'Cliente excluído!';
    }
    
    if (isset($_POST['excluir_servico'])) {
        $stmt = $conn->prepare("DELETE FROM servicos WHERE id = ?");
        $stmt->execute([$_POST['servico_id']]);
        $mensagem = 'Serviço excluído!';
    }
    
    if (isset($_POST['editar_agendamento'])) {
        $stmt = $conn->prepare("UPDATE agendamentos SET cliente_id = ?, servico_id = ?, data_hora = ?, status = ?, valor_pago = ?, obs = ? WHERE id = ?");
        $stmt->execute([$_POST['cliente_id'], $_POST['servico_id'], $_POST['data_hora'], $_POST['status'], $_POST['valor_pago'], $_POST['obs'], $_POST['id']]);
        $mensagem = 'Agendamento atualizado!';
    }
    
    if (isset($_POST['excluir_agendamento'])) {
        $stmt = $conn->prepare("DELETE FROM agendamentos WHERE id = ?");
        $stmt->execute([$_POST['agendamento_id']]);
        $mensagem = 'Agendamento excluído!';
    }
    
    if (isset($_POST['editar_cliente'])) {
        $stmt = $conn->prepare("UPDATE clientes SET nome = ?, telefone = ?, whatsapp = ?, email = ?, obs = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['telefone'], $_POST['whatsapp'], $_POST['email'], $_POST['obs'], $_POST['id']]);
        $mensagem = 'Cliente atualizado!';
    }
    
    if (isset($_POST['editar_servico'])) {
        $stmt = $conn->prepare("UPDATE servicos SET nome = ?, preco = ?, duracao = ?, descricao = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['preco'], $_POST['duracao'], $_POST['descricao'], $_POST['id']]);
        $mensagem = 'Serviço atualizado!';
    }
    
    if (isset($_POST['editar_despesa'])) {
        $stmt = $conn->prepare("UPDATE despesas SET descricao = ?, valor = ?, categoria = ?, data = ? WHERE id = ?");
        $stmt->execute([$_POST['descricao'], $_POST['valor'], $_POST['categoria'], $_POST['data'], $_POST['id']]);
        $mensagem = 'Despesa atualizada!';
    }
    
    if (isset($_POST['excluir_despesa'])) {
        $stmt = $conn->prepare("DELETE FROM despesas WHERE id = ?");
        $stmt->execute([$_POST['despesa_id']]);
        $mensagem = 'Despesa excluída!';
    }
    
    if (isset($_POST['editar_cobranca'])) {
        $stmt = $conn->prepare("UPDATE cobrancas SET cliente_id = ?, valor = ?, descricao = ?, data_vencimento = ?, status = ?, data_pagamento = ? WHERE id = ?");
        $stmt->execute([$_POST['cliente_id'], $_POST['valor'], $_POST['descricao'], $_POST['data_vencimento'], $_POST['status'], $_POST['data_pagamento'], $_POST['id']]);
        $mensagem = 'Cobrança atualizada!';
    }
    
    if (isset($_POST['excluir_cobranca'])) {
        $stmt = $conn->prepare("DELETE FROM cobrancas WHERE id = ?");
        $stmt->execute([$_POST['cobranca_id']]);
        $mensagem = 'Cobrança excluída!';
    }
}

$clientes = getResults($conn->query("SELECT * FROM clientes ORDER BY nome"));
$servicos = getResults($conn->query("SELECT * FROM servicos ORDER BY nome"));
$agendamentos = getResults($conn->query("SELECT a.*, c.nome as cliente_nome, s.nome as servico_nome, s.preco as servico_preco 
    FROM agendamentos a 
    LEFT JOIN clientes c ON a.cliente_id = c.id 
    LEFT JOIN servicos s ON a.servico_id = s.id 
    ORDER BY a.data_hora DESC LIMIT 50"));
$despesas = getResults($conn->query("SELECT * FROM despesas ORDER BY data DESC LIMIT 50"));
$cobrancas = getResults($conn->query("SELECT co.*, c.nome as cliente_nome 
    FROM cobrancas co 
    LEFT JOIN clientes c ON co.cliente_id = c.id 
    ORDER BY co.data_vencimento DESC"));
$config = getConfig($conn);
$estatisticas = getEstatisticas($conn);

require_once 'header.php';
?>

<?php if ($acao == 'dashboard'): ?>
    <h2 class="mb-4">Dashboard</h2>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Receita do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['receita'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <h6>Despesas do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['despesas'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card <?= $estatisticas['lucro'] >= 0 ? 'green' : 'orange' ?>">
                <h6>Lucro do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['lucro'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <h6>Cobranças Pendentes</h6>
                <h3>R$ <?= number_format($estatisticas['cobrancas_pendentes'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Próximos Agendamentos</div>
                <div class="card-body">
                    <?php 
                    $proximos = getResults($conn->query("SELECT a.*, c.nome as cliente_nome, s.nome as servico_nome 
                        FROM agendamentos a 
                        LEFT JOIN clientes c ON a.cliente_id = c.id 
                        LEFT JOIN servicos s ON a.servico_id = s.id 
                        WHERE a.data_hora >= NOW() AND a.status = 'agendado'
                        ORDER BY a.data_hora LIMIT 5"));
                    foreach ($proximos as $p): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong><?= htmlspecialchars($p['cliente_nome'] ?? 'Cliente') ?></strong><br>
                                <small><?= htmlspecialchars($p['servico_nome'] ?? 'Serviço') ?></small>
                            </div>
                            <div class="text-end">
                                <small><?= date('d/m H:i', strtotime($p['data_hora'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-currency-dollar me-2"></i>Cobranças Pendentes</div>
                <div class="card-body">
                    <?php 
                    $pendentes = getResults($conn->query("SELECT co.*, c.nome as cliente_nome 
                        FROM cobrancas co 
                        LEFT JOIN clientes c ON co.cliente_id = c.id 
                        WHERE co.status = 'pendente'
                        ORDER BY co.data_vencimento LIMIT 5"));
                    foreach ($pendentes as $c): ?>
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong><?= htmlspecialchars($c['cliente_nome'] ?? 'Cliente') ?></strong><br>
                                <small><?= htmlspecialchars($c['descricao'] ?? '') ?></small>
                            </div>
                            <div class="text-end">
                                <strong>R$ <?= number_format($c['valor'], 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><i class="bi bi-graph-up me-2"></i>Performance do Mês</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <h4><?= $estatisticas['agendamentos_mes'] ?></h4>
                    <p class="text-muted">Agendamentos</p>
                </div>
                <div class="col-md-4">
                    <h4><?= $estatisticas['receita'] > 0 ? number_format($estatisticas['receita'] / max($estatisticas['agendamentos_mes'], 1), 2, ',', '.') : '0' ?></h4>
                    <p class="text-muted">Média por agendamento</p>
                </div>
                <div class="col-md-4">
                    <h4><?= $estatisticas['total_clientes'] ?></h4>
                    <p class="text-muted">Total de clientes</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($acao == 'agendamentos' || $acao == 'calendario'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><?= $acao == 'calendario' ? 'Calendário' : 'Agendamentos' ?></h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgendamento">
            <i class="bi bi-plus"></i> Novo Agendamento
        </button>
    </div>
    
    <?php if ($acao == 'calendario'): ?>
        <?php
        $ano = $_GET['ano'] ?? date('Y');
        $mes = $_GET['mes'] ?? date('n');
        $primeiro_dia = mktime(0, 0, 0, $mes, 1, $ano);
        $dias_no_mes = date('t', $primeiro_dia);
        $dia_semana = date('w', $primeiro_dia);
        $nomes_meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <a href="?acao=calendario&ano=<?= $ano - 1 ?>&mes=<?= $mes ?>" class="btn btn-sm btn-outline-secondary">&laquo;</a>
                <h5 class="mb-0"><?= $nomes_meses[$mes] ?> de <?= $ano ?></h5>
                <a href="?acao=calendario&ano=<?= $ano + 1 ?>&mes=<?= $mes ?>" class="btn btn-sm btn-outline-secondary">&raquo;</a>
            </div>
            <div class="card-body p-0">
                <div class="row text-center mb-2">
                    <div class="col">Dom</div><div class="col">Seg</div><div class="col">Ter</div>
                    <div class="col">Qua</div><div class="col">Qui</div><div class="col">Sex</div><div class="col">Sáb</div>
                </div>
                <div class="row">
                    <?php for ($i = 0; $i < $dia_semana; $i++): ?>
                        <div class="col calendar-day"></div>
                    <?php endfor; ?>
                    <?php for ($dia = 1; $dia <= $dias_no_mes; $dia++): 
                        $data_busca = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                        $agenda_dia = getResults($conn->query("SELECT a.*, c.nome as cliente_nome, s.nome as servico_nome 
                            FROM agendamentos a 
                            LEFT JOIN clientes c ON a.cliente_id = c.id 
                            LEFT JOIN servicos s ON a.servico_id = s.id 
                            WHERE DATE(a.data_hora) = '$data_busca' ORDER BY a.data_hora"));
                    ?>
                        <div class="col calendar-day <?= $data_busca == date('Y-m-d') ? 'today' : '' ?>">
                            <strong><?= $dia ?></strong>
                            <?php foreach ($agenda_dia as $ag): ?>
                                <div class="calendar-event status-<?= $ag['status'] ?>" title="<?= htmlspecialchars($ag['cliente_nome']) ?> - <?= htmlspecialchars($ag['servico_nome']) ?>">
                                    <?= date('H:i', strtotime($ag['data_hora'])) ?> <?= htmlspecialchars($ag['cliente_nome']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $a): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                                <td><?= htmlspecialchars($a['cliente_nome'] ?? 'Cliente removido') ?></td>
                                <td><?= htmlspecialchars($a['servico_nome'] ?? 'Serviço removido') ?></td>
                                <td>
                                    <span class="badge bg-<?= $a['status'] == 'agendado' ? 'primary' : ($a['status'] == 'concluido' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($a['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" title="Editar" 
                                        onclick="editarAgendamento(<?= $a['id'] ?>, '<?= $a['cliente_id'] ?>', '<?= $a['servico_id'] ?>', '<?= $a['data_hora'] ?>', '<?= $a['status'] ?>', '<?= $a['valor_pago'] ?>', '<?= addslashes($a['obs'] ?? '') ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($a['status'] == 'agendado'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="agendamento_id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="valor_pago" value="<?= $a['servico_preco'] ?>">
                                            <button type="submit" name="finalizar_agendamento" class="btn btn-sm btn-success" title="Finalizar">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="agendamento_id" value="<?= $a['id'] ?>">
                                            <button type="submit" name="cancelar_agendamento" class="btn btn-sm btn-danger" title="Cancelar">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir agendamento?')">
                                        <input type="hidden" name="agendamento_id" value="<?= $a['id'] ?>">
                                        <button type="submit" name="excluir_agendamento" class="btn btn-sm btn-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php 
                                    $msg = $config['mensagem_confirmacao'];
                                    $msg = str_replace('{nome}', $a['cliente_nome'], $msg);
                                    $msg = str_replace('{servico}', $a['servico_nome'], $msg);
                                    $msg = str_replace('{data}', date('d/m/Y', strtotime($a['data_hora'])), $msg);
                                    $msg = str_replace('{hora}', date('H:i', strtotime($a['data_hora'])), $msg);
                                    $whatsapp = $config['whatsapp'] ?? '';
                                    ?>
                                    <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $whatsapp) ?>?text=<?= urlencode($msg) ?>" 
                                       class="btn btn-sm btn-success" target="_blank" title="Enviar WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($acao == 'clientes'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Clientes</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
            <i class="bi bi-plus"></i> Novo Cliente
        </button>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>WhatsApp</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nome']) ?></td>
                            <td><?= htmlspecialchars($c['telefone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['whatsapp'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" title="Editar" 
                                    onclick="editarCliente(<?= $c['id'] ?>, '<?= addslashes($c['nome']) ?>', '<?= addslashes($c['telefone'] ?? '') ?>', '<?= addslashes($c['whatsapp'] ?? '') ?>', '<?= addslashes($c['email'] ?? '') ?>', '<?= addslashes($c['obs'] ?? '') ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir cliente?')">
                                    <input type="hidden" name="cliente_id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="excluir_cliente" class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($acao == 'servicos'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Serviços</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServico">
            <i class="bi bi-plus"></i> Novo Serviço
        </button>
    </div>
    <div class="row">
        <?php foreach ($servicos as $s): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($s['nome']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($s['descricao'] ?? '') ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 text-primary">R$ <?= number_format($s['preco'], 2, ',', '.') ?></span>
                            <span class="badge bg-secondary"><?= $s['duracao'] ?> min</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="button" class="btn btn-sm btn-warning" title="Editar" 
                            onclick="editarServico(<?= $s['id'] ?>, '<?= addslashes($s['nome']) ?>', '<?= $s['preco'] ?>', '<?= $s['duracao'] ?>', '<?= addslashes($s['descricao'] ?? '') ?>')">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Excluir serviço?')">
                            <input type="hidden" name="servico_id" value="<?= $s['id'] ?>">
                            <button type="submit" name="excluir_servico" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> Excluir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($acao == 'despesas'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Despesas</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDespesa">
            <i class="bi bi-plus"></i> Nova Despesa
        </button>
    </div>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total do Mês</h6>
                    <h3 class="text-danger">R$ <?= number_format($estatisticas['despesas'], 2, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($despesas as $d): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($d['data'])) ?></td>
                            <td><?= htmlspecialchars($d['descricao']) ?></td>
                            <td><span class="badge bg-secondary"><?= $d['categoria'] ?></span></td>
                            <td class="text-danger">- R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" title="Editar" 
                                    onclick="editarDespesa(<?= $d['id'] ?>, '<?= addslashes($d['descricao']) ?>', '<?= $d['valor'] ?>', '<?= $d['categoria'] ?>', '<?= $d['data'] ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir despesa?')">
                                    <input type="hidden" name="despesa_id" value="<?= $d['id'] ?>">
                                    <button type="submit" name="excluir_despesa" class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($acao == 'cobrancas'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Cobranças</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCobranca">
            <i class="bi bi-plus"></i> Nova Cobrança
        </button>
    </div>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Pendente</h6>
                    <h3 class="text-warning">R$ <?= number_format($estatisticas['cobrancas_pendentes'], 2, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cobrancas as $cob): ?>
                        <tr>
                            <td><?= htmlspecialchars($cob['cliente_nome'] ?? 'Cliente removido') ?></td>
                            <td><?= htmlspecialchars($cob['descricao'] ?? '-') ?></td>
                            <td>R$ <?= number_format($cob['valor'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($cob['data_vencimento'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $cob['status'] == 'pago' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($cob['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" title="Editar" 
                                    onclick="editarCobranca(<?= $cob['id'] ?>, '<?= $cob['cliente_id'] ?>', '<?= $cob['valor'] ?>', '<?= addslashes($cob['descricao'] ?? '') ?>', '<?= $cob['data_vencimento'] ?>', '<?= $cob['status'] ?>', '<?= $cob['data_pagamento'] ?? '' ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($cob['status'] == 'pendente'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="cobranca_id" value="<?= $cob['id'] ?>">
                                        <button type="submit" name="pagar_cobranca" class="btn btn-sm btn-success">
                                            <i class="bi bi-check"></i> Pagar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" class="d-inline" onsubmit="return confirm('Excluir cobrança?')">
                                    <input type="hidden" name="cobranca_id" value="<?= $cob['id'] ?>">
                                    <button type="submit" name="excluir_cobranca" class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($acao == 'financas'): ?>
    <h2 class="mb-4">Resumo Financeiro</h2>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h6>Receita do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['receita'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green">
                <h6>Despesas do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['despesas'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card <?= $estatisticas['lucro'] >= 0 ? 'green' : 'orange' ?>">
                <h6>Lucro do Mês</h6>
                <h3>R$ <?= number_format($estatisticas['lucro'], 2, ',', '.') ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <h6>Cobranças Pendentes</h6>
                <h3>R$ <?= number_format($estatisticas['cobrancas_pendentes'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">Performance do Mês</div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4><?= $estatisticas['agendamentos_mes'] ?></h4>
                    <p class="text-muted">Agendamentos realizados</p>
                </div>
                <div class="col-md-3">
                    <h4><?= $estatisticas['total_clientes'] ?></h4>
                    <p class="text-muted">Clientes cadastrados</p>
                </div>
                <div class="col-md-3">
                    <h4>R$ <?= $estatisticas['receita'] > 0 ? number_format($estatisticas['receita'] / max($estatisticas['agendamentos_mes'], 1), 2, ',', '.') : '0,00' ?></h4>
                    <p class="text-muted">Ticket médio</p>
                </div>
                <div class="col-md-3">
                    <h4><?= $estatisticas['lucro'] > 0 && $estatisticas['receita'] > 0 ? number_format(($estatisticas['lucro'] / $estatisticas['receita']) * 100, 0) : 0 ?>%</h4>
                    <p class="text-muted">Margem de lucro</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Últimas Receitas</div>
                <div class="card-body">
                    <?php 
                    $receitas_lista = getResults($conn->query("SELECT a.*, c.nome as cliente_nome, s.nome as servico_nome 
                        FROM agendamentos a 
                        LEFT JOIN clientes c ON a.cliente_id = c.id 
                        LEFT JOIN servicos s ON a.servico_id = s.id 
                        WHERE a.status = 'concluido' 
                        ORDER BY a.data_hora DESC LIMIT 10"));
                    foreach ($receitas_lista as $r): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong><?= htmlspecialchars($r['cliente_nome'] ?? '-') ?></strong><br>
                                <small><?= htmlspecialchars($r['servico_nome'] ?? '-') ?></small>
                            </div>
                            <div class="text-success">+ R$ <?= number_format($r['valor_pago'], 2, ',', '.') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Últimas Despesas</div>
                <div class="card-body">
                    <?php 
                    $ult_despesas = getResults($conn->query("SELECT * FROM despesas ORDER BY data DESC LIMIT 10"));
                    foreach ($ult_despesas as $ud): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong><?= htmlspecialchars($ud['descricao']) ?></strong><br>
                                <small><?= $ud['categoria'] ?></small>
                            </div>
                            <div class="text-danger">- R$ <?= number_format($ud['valor'], 2, ',', '.') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($acao == 'config'): ?>
    <h2 class="mb-4">Configurações</h2>
    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Nome do Salão</label>
                    <input type="text" name="nome_salao" class="form-control" value="<?= htmlspecialchars($config['nome_salao'] ?? 'Bia Manicure') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">WhatsApp (com DDD)</label>
                    <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($config['whatsapp'] ?? '') ?>" placeholder="11999999999">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensagem de Confirmação</label>
                    <textarea name="mensagem_confirmacao" class="form-control" rows="4"><?= htmlspecialchars($config['mensagem_confirmacao'] ?? '') ?></textarea>
                    <small class="text-muted">Variáveis: {nome}, {servico}, {data}, {hora}</small>
                </div>
                <button type="submit" name="salvar_config" class="btn btn-primary">Salvar</button>
            </form>
        </div>
    </div>
<?php endif; ?>
        </div>
    </div>

<?php require_once 'modais.php'; ?>
<?php require_once 'footer.php'; ?>