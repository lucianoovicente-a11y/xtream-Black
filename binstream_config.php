<?php
include 'menu.php';

require_once './api/controles/db.php';

$conexao = conectar_bd();
$mensagem = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $binstream_domain = $_POST['binstream_domain'];
        $binstream_token = $_POST['binstream_token'];
        $binstream_api_url = $_POST['binstream_api_url'];
        
        $stmt_domain = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'binstream_domain'");
        $stmt_domain->bindParam(':valor', $binstream_domain);
        $stmt_domain->execute();
        
        $stmt_token = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'binstream_token'");
        $stmt_token->bindParam(':valor', $binstream_token);
        $stmt_token->execute();
        
        $stmt_api = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'binstream_api_url'");
        $stmt_api->bindParam(':valor', $binstream_api_url);
        $stmt_api->execute();
        
        $mensagem = 'Configurações Binstream salvas com sucesso!';
        $msg_type = 'alert-success';
    } catch (PDOException $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $msg_type = 'alert-danger';
    }
}

$configs = [];
$stmt = $conexao->prepare("SELECT setting_name, setting_value FROM settings WHERE setting_name LIKE 'binstream_%'");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $configs[$row['setting_name']] = $row['setting_value'];
}
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="m-0"><i class="fas fa-satellite-dish"></i> Configurações Binstream P2P</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($mensagem)): ?>
                <div class="alert <?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">🔗 Domain / Host</label>
                            <input type="text" name="binstream_domain" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['binstream_domain'] ?? ''); ?>" 
                                   placeholder="ex: p2p.seudominio.com" required>
                            <div class="form-text">Domínio do servidor Binstream P2P</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">🔑 Token / Auth Key</label>
                            <input type="text" name="binstream_token" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['binstream_token'] ?? ''); ?>" 
                                   placeholder="Seu token de autenticação" required>
                            <div class="form-text">Token fornecido pela Binstream</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">🌐 API URL</label>
                            <input type="text" name="binstream_api_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['binstream_api_url'] ?? ''); ?>" 
                                   placeholder="https://api.binstream.com/v1" required>
                            <div class="form-text">URL da API Binstream</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-3 bg-dark rounded border">
                            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle"></i> Como Obter os Dados</h5>
                            <ol class="mb-0">
                                <li class="mb-2">Acesse sua conta em <strong>binstream.tv</strong></li>
                                <li class="mb-2">Vá em <strong>Settings</strong> ou <strong>API</strong></li>
                                <li class="mb-2">Copie o <strong>Domain</strong> do seu servidor P2P</li>
                                <li class="mb-2">Copie o <strong>Token/Auth</strong> fornecido</li>
                                <li class="mb-2">Use a <strong>API URL</strong> padrão ou a fornecida</li>
                            </ol>
                        </div>
                        
                        <div class="mt-3 p-3 bg-dark rounded border">
                            <h5 class="fw-bold mb-2"><i class="fas fa-plug"></i> Endpoints Comuns</h5>
                            <p class="mb-1 small"><code>Auth:</code> /auth</p>
                            <p class="mb-1 small"><code>Register:</code> /register</p>
                            <p class="mb-1 small"><code>Login:</code> /login</p>
                            <p class="mb-0 small"><code>Streams:</code> /streams</p>
                        </div>
                    </div>
                </div>
                
                <hr>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Configurações</button>
                <a href="p2p_config.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </form>
        </div>
    </div>
</div>