<?php
/**
 * Configurações do Painel - Editar nome, logo e WhatsApp
 * Admin pode alterar as informações que aparecem em todo o site
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

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['title'] = $_POST['title'] ?? 'Prof. Luciano Vicente';
    $config['whatsapp'] = $_POST['whatsapp'] ?? '21971877485';
    $config['signature'] = $_POST['signature'] ?? 'Prof. Luciano Vicente - 21971877485';
    
    // Upload de logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'img/';
        if (!is_dir($upload_dir)) {
            mk_dir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = 'logo.' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $config['logo_path'] = './img/' . $new_filename;
                $success = 'Logo atualizado com sucesso!';
            } else {
                $error = 'Erro ao fazer upload do logo.';
            }
        } else {
            $error = 'Formato de arquivo não permitido. Use: png, jpg, jpeg, gif, svg, webp.';
        }
    }
    
    // Salva configurações
    if (file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        $success = ($success ?? '') . ' Configurações salvas com sucesso!';
    } else {
        $error = 'Erro ao salvar configurações.';
    }
}

// Recarrega configurações
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="/css/retro.css">

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Configurações do Painel</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Nome do Painel</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($config['title'] ?? 'Prof. Luciano Vicente'); ?>" 
                                   required>
                            <small class="text-muted">Este nome aparecerá em todo o site</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" 
                                   value="<?php echo htmlspecialchars($config['whatsapp'] ?? '21971877485'); ?>" 
                                   placeholder="21971877485" required>
                            <small class="text-muted">Número do WhatsApp (apenas números)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="signature" class="form-label">Assinatura</label>
                            <input type="text" class="form-control" id="signature" name="signature" 
                                   value="<?php echo htmlspecialchars($config['signature'] ?? 'Prof. Luciano Vicente - 21971877485'); ?>" 
                                   required>
                            <small class="text-muted">Assinatura que aparece no rodapé das páginas</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo" class="form-label">Logotipo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Envie uma nova imagem para o logo (png, jpg, jpeg, gif, svg, webp)</small>
                            
                            <?php if (!empty($config['logo_path'])): ?>
                                <div class="mt-3">
                                    <p>Logo atual:</p>
                                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" 
                                         alt="Logo atual" 
                                         style="max-width: 300px; max-height: 100px; border: 1px solid rgba(255,107,53,0.3); border-radius: 8px; padding: 10px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                        
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fas fa-info-circle"></i> Informações</h4>
                </div>
                <div class="card-body">
                    <p><strong>Nome do Painel:</strong> <?php echo htmlspecialchars($config['title'] ?? 'Prof. Luciano Vicente'); ?></p>
                    <p><strong>WhatsApp:</strong> <?php echo htmlspecialchars($config['whatsapp'] ?? '21971877485'); ?></p>
                    <p><strong>Assinatura:</strong> <?php echo htmlspecialchars($config['signature'] ?? 'Prof. Luciano Vicente - 21971877485'); ?></p>
                    <p><strong>Logo:</strong> <?php echo htmlspecialchars($config['logo_path'] ?? './img/logo.png'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</main>
</body>
</html>
