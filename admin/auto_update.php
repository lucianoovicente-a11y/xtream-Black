<?php
/**
 * Sistema de Atualização Automática - XTREAM SERVER
 * 
 * Verifica nova versão no GitHub e atualiza automaticamente
 */

require_once '../includes/config.php';
require_once '../classes/Database.class.php';

class AutoUpdater {
    private $versionFile = 'version.json';
    private $currentVersion;
    private $latestVersion;
    private $githubRepo = 'seu-usuario/xtream-server';
    
    public function __construct() {
        $this->loadCurrentVersion();
    }
    
    /**
     * Carrega versão atual do arquivo local
     */
    private function loadCurrentVersion() {
        if (file_exists($this->versionFile)) {
            $data = json_decode(file_get_contents($this->versionFile), true);
            $this->currentVersion = $data['version'] ?? '0.0.0';
        } else {
            $this->currentVersion = '0.0.0';
        }
    }
    
    /**
     * Busca última versão do GitHub
     */
    public function checkForUpdates() {
        $url = "https://raw.githubusercontent.com/{$this->githubRepo}/main/version.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $this->latestVersion = $data['version'] ?? '0.0.0';
            
            return [
                'has_update' => version_compare($this->latestVersion, $this->currentVersion, '>'),
                'current_version' => $this->currentVersion,
                'latest_version' => $this->latestVersion,
                'changelog_url' => $data['changelog_url'] ?? '',
                'download_url' => $data['download_url'] ?? '',
                'features' => $data['features'] ?? [],
                'bugs_fixed' => $data['bugs_fixed'] ?? []
            ];
        }
        
        return ['error' => 'Não foi possível verificar atualizações'];
    }
    
    /**
     * Realiza o download e instalação da atualização
     */
    public function update() {
        $updateInfo = $this->checkForUpdates();
        
        if (isset($updateInfo['error']) || !$updateInfo['has_update']) {
            return ['success' => false, 'message' => 'Nenhuma atualização disponível'];
        }
        
        $downloadUrl = $updateInfo['download_url'];
        $tempFile = sys_get_temp_dir() . '/xtream-update.zip';
        
        // Download do ZIP
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $downloadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, fopen($tempFile, 'w'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        
        $success = curl_exec($ch);
        curl_close($ch);
        
        if (!$success || !file_exists($tempFile)) {
            return ['success' => false, 'message' => 'Falha no download da atualização'];
        }
        
        // Extrair ZIP
        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== TRUE) {
            return ['success' => false, 'message' => 'Falha ao extrair arquivo'];
        }
        
        // Backup dos arquivos atuais
        $backupDir = 'backup/update_' . date('Ymd_His');
        $this->createBackup($backupDir);
        
        // Extrair novos arquivos (mantendo config.json e banco de dados)
        $excludeFiles = ['config.json', '.env', 'version.json'];
        $extracted = 0;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->statIndex($i);
            $filename = $file['name'];
            
            // Remove prefixo da pasta do repositório
            $filename = preg_replace('/^[^\/]+\//', '', $filename);
            
            // Pula arquivos que não devem ser sobrescritos
            if (in_array($filename, $excludeFiles) || empty($filename)) {
                continue;
            }
            
            // Extrai arquivo
            if ($file['size'] > 0) {
                $content = $zip->getFromIndex($i);
                $targetPath = './' . $filename;
                
                // Cria diretórios se necessário
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                file_put_contents($targetPath, $content);
                $extracted++;
            }
        }
        
        $zip->close();
        unlink($tempFile);
        
        // Atualiza versão local
        $remoteVersionJson = file_get_contents("https://raw.githubusercontent.com/{$this->githubRepo}/main/version.json");
        if ($remoteVersionJson) {
            file_put_contents($this->versionFile, $remoteVersionJson);
        }
        
        return [
            'success' => true,
            'message' => "Atualização concluída! {$extracted} arquivos atualizados.",
            'backup_dir' => $backupDir,
            'from_version' => $this->currentVersion,
            'to_version' => $this->latestVersion
        ];
    }
    
    /**
     * Cria backup dos arquivos atuais
     */
    private function createBackup($backupDir) {
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filesToBackup = glob('./*.{php,json,js,css,html}', GLOB_BRACE);
        foreach ($filesToBackup as $file) {
            if (basename($file) !== 'auto_update.php') {
                copy($file, $backupDir . '/' . basename($file));
            }
        }
        
        // Backup de diretórios importantes
        $dirsToBackup = ['classes', 'api', 'includes'];
        foreach ($dirsToBackup as $dir) {
            if (is_dir($dir)) {
                $this->copyDirectory($dir, $backupDir . '/' . $dir);
            }
        }
    }
    
    /**
     * Copia diretório recursivamente
     */
    private function copyDirectory($src, $dst) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            
            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }
    
    /**
     * Obtém histórico de versões
     */
    public function getVersionHistory() {
        $url = "https://raw.githubusercontent.com/{$this->githubRepo}/main/CHANGELOG.md";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $content = curl_exec($ch);
        curl_close($ch);
        
        return $content ?: null;
    }
}

// API Endpoint
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
            } else {
                echo json_encode(['error' => 'Método não permitido']);
            }
            break;
            
        case 'history':
            $history = $updater->getVersionHistory();
            echo json_encode(['changelog' => $history]);
            break;
            
        default:
            echo json_encode(['error' => 'Ação inválida']);
    }
    exit;
}
?>
