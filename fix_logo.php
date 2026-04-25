<?php
// Script para diagnosticar e corrigir problema da logo
require_once("menu.php");

echo "<h2>Diagnóstico da Logo</h2>";

// Verifica arquivo config.json
$config_file = 'config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    echo "<h3>✅ Config.json encontrado:</h3>";
    echo "<pre>" . json_encode($config, JSON_PRETTY_PRINT) . "</pre>";
    
    // Verifica se o arquivo da logo existe
    $logo_path = $config['logo_path'];
    echo "<h3>Verificando arquivo: {$logo_path}</h3>";
    
    if (file_exists($logo_path)) {
        echo "<p>✅ Arquivo existe!</p>";
        echo "<p>Tamanho: " . filesize($logo_path) . " bytes</p>";
        echo "<p>URL completa: http://" . $_SERVER['HTTP_HOST'] . "/" . ltrim($logo_path, './') . "</p>";
        echo "<img src='{$logo_path}' style='max-width: 300px; border: 1px solid #ccc;'>";
    } else {
        echo "<p>❌ Arquivo NÃO existe no caminho especificado!</p>";
        
        // Busca logos na pasta img/
        echo "<h3>Logos disponíveis em /img/:</h3>";
        $logos = glob('img/logo*');
        foreach ($logos as $logo) {
            echo "<div style='margin: 10px; border: 1px solid #ccc; padding: 10px; display: inline-block;'>";
            echo "<p>{$logo}</p>";
            echo "<img src='{$logo}' style='max-width: 200px;'>";
            echo "<br><a href='?fix={$logo}'>Usar esta logo</a>";
            echo "</div>";
        }
    }
} else {
    echo "<p>❌ config.json não existe!</p>";
}

// Se pediu para corrigir
if (isset($_GET['fix'])) {
    $nova_logo = $_GET['fix'];
    $config['logo_path'] = $nova_logo;
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    echo "<script>alert('Logo atualizada!'); window.location.href='fix_logo.php';</script>";
}
?>
