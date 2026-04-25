<?php
// ======================================================================
//  CONFIGURAÇÃO P2P STREAMFLOW - utrafix.qualidade.cloud
// ======================================================================

include 'menu.php';

require_once './api/controles/db.php';

$conexao = conectar_bd();
$mensagem = '';
$msg_type = '';

// URLs do StreamFlow P2P
define('STREAMFLOW_P2P_HOST', 'https://utrafix.qualidade.cloud/p2p');
define('STREAMFLOW_HOST', 'https://utrafix.qualidade.cloud');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $p2p_api_url = $_POST['p2p_api_url'] ?? STREAMFLOW_P2P_HOST;
        $stream_api_url = $_POST['stream_api_url'] ?? STREAMFLOW_HOST;
        $p2p_token = $_POST['p2p_token'] ?? '';
        
        $stmt_api = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'p2p_api_url'");
        $stmt_api->bindParam(':valor', $p2p_api_url);
        $stmt_api->execute();
        
        $stmt_stream = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'stream_api_url'");
        $stmt_stream->bindParam(':valor', $stream_api_url);
        $stmt_stream->execute();
        
        $stmt_token = $conexao->prepare("UPDATE settings SET setting_value = :valor WHERE setting_name = 'p2p_token'");
        $stmt_token->bindParam(':valor', $p2p_token);
        $stmt_token->execute();
        
        $mensagem = 'Configurações StreamFlow P2P salvas com sucesso!';
        $msg_type = 'alert-success';
    } catch (PDOException $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $msg_type = 'alert-danger';
    }
}

$configs = [];
$stmt = $conexao->prepare("SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('p2p_api_url', 'stream_api_url', 'p2p_default_password', 'p2p_token')");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $configs[$row['setting_name']] = $row['setting_value'];
}
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="m-0"><i class="fas fa-broadcast-tower"></i> Configuração P2P StreamFlow</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($mensagem)): ?>
                <div class="alert <?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="p-3 bg-dark rounded mb-4">
                        <h5 class="text-warning"><i class="fas fa-server"></i> URLs Configuradas</h5>
                        <p class="mb-1"><strong>Stream:</strong> <code><?php echo STREAMFLOW_HOST; ?></code></p>
                        <p class="mb-0"><strong>P2P:</strong> <code><?php echo STREAMFLOW_P2P_HOST; ?></code></p>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">🌐 URL API P2P</label>
                            <input type="text" name="p2p_api_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['p2p_api_url'] ?? STREAMFLOW_P2P_HOST); ?>" required>
                            <div class="form-text">URL base do módulo P2P</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">📡 URL API Stream</label>
                            <input type="text" name="stream_api_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['stream_api_url'] ?? STREAMFLOW_HOST); ?>" required>
                            <div class="form-text">URL principal do StreamFlow</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">🔑 Token / Auth Key</label>
                            <input type="text" name="p2p_token" class="form-control" 
                                   value="<?php echo htmlspecialchars($configs['p2p_token'] ?? ''); ?>" 
                                   placeholder="Cole aqui o token do StreamFlow P2P">
                            <div class="form-text">Token fornecido pelo StreamFlow para autenticação</div>
                        </div>
                        
                        <hr>
                        <h6 class="fw-bold mb-3">Endpoints P2P StreamFlow</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">POST <code>/p2p/api/auth</code></label>
                            <p class="small text-muted">Autenticar usuário P2P</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">POST <code>/p2p/api/register</code></label>
                            <p class="small text-muted">Registrar novo usuário P2P</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">GET <code>/p2p/api/user_info</code></label>
                            <p class="small text-muted">Obter informações do usuário</p>
                        </div>
                        
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar Configurações</button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <div class="p-4 bg-dark rounded h-100">
                        <h5 class="text-warning mb-3"><i class="fas fa-question-circle"></i> Como Funciona</h5>
                        
                        <div class="mb-4">
                            <h6 class="text-primary">1. Criar Cliente P2P</h6>
                            <p class="small">O sistema gera um <strong>código de acesso</strong> único que o cliente usa como <strong>senha</strong> no aplicativo P2P.</p>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-primary">2. App P2P</h6>
                            <p class="small">O cliente instala o app (XCIPTV, STB, etc) e configura com:</p>
                            <ul class="small">
                                <li><strong>URL:</strong> <?php echo STREAMFLOW_HOST; ?></li>
                                <li><strong>Usuário:</strong> Código do cliente</li>
                                <li><strong>Senha:</strong> <?php echo htmlspecialchars($configs['p2p_default_password'] ?? '1122334455'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h6 class="text-primary">3. Renovação</h6>
                            <p class="small">Renove o vencimento do cliente pelo painel quando necessário.</p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Dica:</strong> O código P2P serve como <strong>USUÁRIO</strong> e a senha padrão é configurável em "Configurações P2P".
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testar Conexão -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="m-0"><i class="fas fa-wifi"></i> Testar Conexão</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-outline-primary" onclick="testarConexaoP2P()">
                <i class="fas fa-plug"></i> Testar API P2P
            </button>
            <div id="resultado_teste" class="mt-3 d-none">
                <div class="alert" role="alert"></div>
            </div>
        </div>
    </div>
</div>

<script>
function testarConexaoP2P() {
    const resultado = document.getElementById('resultado_teste');
    resultado.classList.remove('d-none');
    resultado.querySelector('.alert').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando conexão...';
    
    fetch('<?php echo STREAMFLOW_P2P_HOST; ?>/api/auth', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: 'test', password: 'test'})
    })
    .then(res => {
        if (res.ok) {
            resultado.querySelector('.alert').className = 'alert alert-success';
            resultado.querySelector('.alert').innerHTML = '<i class="fas fa-check-circle"></i> Conexão P2P OK!';
        } else {
            resultado.querySelector('.alert').className = 'alert alert-warning';
            resultado.querySelector('.alert').innerHTML = '<i class="fas fa-exclamation-triangle"></i> API respondendo, mas retornou erro de autenticação (esperado).';
        }
    })
    .catch(err => {
        resultado.querySelector('.alert').className = 'alert alert-danger';
        resultado.querySelector('.alert').innerHTML = '<i class="fas fa-times-circle"></i> Erro de conexão: ' + err.message;
    });
}
</script>