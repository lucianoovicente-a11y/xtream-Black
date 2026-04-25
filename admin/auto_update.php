<?php
/**
 * Sistema de Atualização Automática - XTREAM SERVER
 * 
 * COMO FUNCIONA:
 * 1. Toda vez que esta página é carregada, verifica no GitHub se há nova versão
 * 2. Compara a versão local (version.json) com a do GitHub
 * 3. Se a versão do GitHub for maior, mostra botão para atualizar
 * 4. Ao clicar, baixa todos os arquivos novos, mantém config.json e .env
 * 5. Faz backup automático antes de atualizar
 * 
 * CONFIGURAÇÃO:
 * - Mude $githubRepo para seu usuário/repositório no GitHub
 * - Crie version.json na raiz do seu repositório GitHub
 * - A cada nova versão, atualize o version.json e crie uma tag
 */

require_once '../includes/config.php';
require_once '../classes/Database.class.php';

class AutoUpdater {
    private $versionFile = 'version.json';
    private $currentVersion;
    private $latestVersion;
    private $githubRepo = 'seu-usuario/xtream-server'; // MUDE AQUI!
    private $githubBranch = 'main';
    
    public function __construct() {
        $this->loadCurrentVersion();
    }
    
    private function loadCurrentVersion() {
        if (file_exists($this->versionFile)) {
            $data = json_decode(file_get_contents($this->versionFile), true);
            $this->currentVersion = $data['version'] ?? '0.0.0';
        } else {
            $this->currentVersion = '0.0.0';
        }
    }
    
    public function checkForUpdates() {
        $url = "https://raw.githubusercontent.com/{$this->githubRepo}/{$this->githubBranch}/version.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XTream-AutoUpdater/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->latestVersion = $data['version'] ?? '0.0.0';
                
                return [
                    'has_update' => version_compare($this->latestVersion, $this->currentVersion, '>'),
                    'current_version' => $this->currentVersion,
                    'latest_version' => $this->latestVersion,
                    'changelog_url' => $data['changelog_url'] ?? '',
                    'download_url' => $data['download_url'] ?? '',
                    'features' => $data['features'] ?? [],
                    'bugs_fixed' => $data['bugs_fixed'] ?? [],
                    'breaking_changes' => $data['breaking_changes'] ?? false,
                    'release_date' => $data['release_date'] ?? ''
                ];
            }
        }
        
        return ['error' => 'Não foi possível verificar atualizações', 'curl_error' => $curlError];
    }
    
    public function update() {
        $updateInfo = $this->checkForUpdates();
        
        if (isset($updateInfo['error']) || !$updateInfo['has_update']) {
            return ['success' => false, 'message' => 'Nenhuma atualização disponível'];
        }
        
        $downloadUrl = $updateInfo['download_url'];
        $tempFile = sys_get_temp_dir() . '/xtream-update-' . time() . '.zip';
        $extractDir = sys_get_temp_dir() . '/xtream-extract-' . time();
        
        // Download
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, fopen($tempFile, 'w'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XTream-AutoUpdater/1.0');
        
        $success = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if (!$success || !file_exists($tempFile) || filesize($tempFile) === 0) {
            return ['success' => false, 'message' => 'Falha no download: ' . $curlError];
        }
        
        // Extrair
        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== TRUE) {
            return ['success' => false, 'message' => 'Falha ao extrair'];
        }
        
        if (!is_dir($extractDir)) mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();
        
        // Backup
        $backupDir = '../backup/update_' . date('Ymd_His');
        $this->createBackup($backupDir);
        
        // Arquivos protegidos
        $excludeFiles = ['config.json', '.env', 'version.json', '.htaccess'];
        $protectedDirs = ['backup', 'logs', 'uploads'];
        
        $extracted = 0;
        $skipped = 0;
        $errors = [];
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractDir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) continue;
            
            $relativePath = str_replace($extractDir . '/', '', $file->getPathname());
            $relativePath = preg_replace('/^[^\/]+\//', '', $relativePath);
            
            if (empty($relativePath)) continue;
            
            $skip = false;
            foreach ($protectedDirs as $protDir) {
                if (strpos($relativePath, $protDir . '/') === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if (in_array(basename($relativePath), $excludeFiles)) {
                $skip = true;
            }
            
            if ($skip) {
                $skipped++;
                continue;
            }
            
            $targetPath = '../' . $relativePath;
            $dir = dirname($targetPath);
            
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                $errors[] = "Falha ao criar: $dir";
                continue;
            }
            
            if (!copy($file->getPathname(), $targetPath)) {
                $errors[] = "Falha ao copiar: $relativePath";
                continue;
            }
            
            $extracted++;
        }
        
        unlink($tempFile);
        $this->deleteDirectory($extractDir);
        
        // Atualiza version.json
        $remoteJson = file_get_contents("https://raw.githubusercontent.com/{$this->githubRepo}/{$this->githubBranch}/version.json");
        if ($remoteJson && json_decode($remoteJson)) {
            file_put_contents($this->versionFile, $remoteJson);
        }
        
        return [
            'success' => empty($errors),
            'message' => "Atualizado! {$extracted} arquivos, {$skipped} mantidos.",
            'backup_dir' => $backupDir,
            'from_version' => $this->currentVersion,
            'to_version' => $this->latestVersion
        ];
    }
    
    private function createBackup($dir) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        copy('../version.json', $dir . '/version.json');
        if (file_exists('../config.json')) copy('../config.json', $dir . '/config.json');
    }
    
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}

// API
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $updater = new AutoUpdater();
    
    switch ($_GET['action']) {
        case 'check':
            echo json_encode($updater->checkForUpdates());
            break;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode($updater->update());
            }
            break;
        default:
            echo json_encode(['error' => 'Ação inválida']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualização Automática</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .card { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .error { background: #f8d7da; color: #721c24; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; }
        #status { margin-top: 20px; padding: 15px; display: none; }
    </style>
</head>
<body>
    <h1>🔄 Atualização Automática</h1>
    
    <?php
    $updater = new AutoUpdater();
    $updateInfo = $updater->checkForUpdates();
    ?>
    
    <div class="card">
        <h3>Versão Atual: <strong><?php echo $updateInfo['current_version']; ?></strong></h3>
        <?php if (isset($updateInfo['has_update']) && $updateInfo['has_update']): ?>
            <p class="warning">
                ✅ Nova versão disponível: <strong><?php echo $updateInfo['latest_version']; ?></strong>
            </p>
            <button onclick="updateSystem()">🚀 Atualizar Agora</button>
        <?php elseif (isset($updateInfo['error'])): ?>
            <p class="error">❌ Erro: <?php echo $updateInfo['error']; ?></p>
        <?php else: ?>
            <p class="success">✅ Você está na versão mais recente!</p>
        <?php endif; ?>
    </div>
    
    <div id="status"></div>
    
    <script>
    async function updateSystem() {
        const status = document.getElementById('status');
        status.style.display = 'block';
        status.className = 'card warning';
        status.innerHTML = '⏳ Baixando atualização...';
        
        try {
            const response = await fetch('?action=update', { method: 'POST' });
            const result = await response.json();
            
            if (result.success) {
                status.className = 'card success';
                status.innerHTML = '✅ ' + result.message;
                setTimeout(() => location.reload(), 2000);
            } else {
                status.className = 'card error';
                status.innerHTML = '❌ Erro: ' + result.message;
            }
        } catch (e) {
            status.className = 'card error';
            status.innerHTML = '❌ Erro na comunicação: ' + e.message;
        }
    }
    
    // Auto-check ao carregar
    setInterval(async () => {
        const response = await fetch('?action=check');
        const result = await response.json();
        if (result.has_update) {
            console.log('Nova versão disponível:', result.latest_version);
        }
    }, 300000); // Check a cada 5 minutos
    </script>
</body>
</html>
