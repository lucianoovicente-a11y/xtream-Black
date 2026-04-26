#!/bin/bash
################################################################################
# Script de Instalação Universal - UtraFix
# Funciona em qualquer ambiente/hospedagem (cPanel, Plesk, VPS, Shared, etc.)
################################################################################

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     INSTALADOR UNIVERSAL - UTRAFIX                     ║${NC}"
echo -e "${BLUE}║     Compatível com qualquer hospedagem                 ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
echo ""

# Função para detectar o diretório raiz do web server
detect_web_root() {
    echo -e "${YELLOW}Detectando diretório raiz do servidor web...${NC}"
    
    # Lista de possíveis diretórios raiz por ordem de prioridade
    possible_roots=(
        "$PWD"                                      # Diretório atual (mais comum)
        "/var/www/html"                             # Apache padrão Linux
        "/var/www"                                  # Alternativa comum
        "/home/$USER/public_html"                   # cPanel padrão
        "/home/$USER/www"                           # Alternativa cPanel
        "/var/www/vhosts/$(hostname)/httpdocs"      # Plesk
        "/srv/http"                                 # Arch Linux
        "/usr/share/nginx/html"                     # Nginx padrão
        "/data/www/$(whoami)"                       # Alguns painéis
        "/home/qualidad/utrafix.qualidade.cloud"    # Seu ambiente específico
    )
    
    # Verifica se estamos em um diretório com index.php ou .htaccess
    if [ -f "index.php" ] || [ -f ".htaccess" ]; then
        WEB_ROOT="$PWD"
        echo -e "${GREEN}✓ Usando diretório atual: $WEB_ROOT${NC}"
        return 0
    fi
    
    # Tenta encontrar o diretório raiz
    for root in "${possible_roots[@]}"; do
        if [ -d "$root" ] && [ -w "$root" ]; then
            if [ -f "$root/index.php" ] || [ -f "$root/.htaccess" ] || [ -f "$root/index.html" ]; then
                WEB_ROOT="$root"
                echo -e "${GREEN}✓ Diretório raiz detectado: $WEB_ROOT${NC}"
                return 0
            fi
        fi
    done
    
    # Se não encontrou, usa o diretório atual
    WEB_ROOT="$PWD"
    echo -e "${YELLOW}⚠ Usando diretório atual como fallback: $WEB_ROOT${NC}"
    return 0
}

# Função para verificar permissões
check_permissions() {
    echo -e "${YELLOW}Verificando permissões...${NC}"
    
    if [ ! -w "$WEB_ROOT" ]; then
        echo -e "${RED}✗ Erro: Sem permissão de escrita em $WEB_ROOT${NC}"
        echo -e "${YELLOW}Sugestão: Execute com sudo ou ajuste as permissões:${NC}"
        echo "  sudo chown -R $USER:$USER $WEB_ROOT"
        echo "  sudo chmod -R 755 $WEB_ROOT"
        exit 1
    fi
    
    echo -e "${GREEN}✓ Permissões OK${NC}"
}

# Função para criar estrutura de diretórios
create_directories() {
    echo -e "${YELLOW}Criando estrutura de diretórios...${NC}"
    
    dirs=(
        "css"
        "js"
        "images"
        "img"
        "vendor/toastr/css"
        "vendor/toastr/js"
        "vendor/global"
        "vendor/bootstrap-select/dist/js"
        "includes"
        "classes"
        "api"
        "public"
        "uploads"
        "logs"
    )
    
    for dir in "${dirs[@]}"; do
        mkdir -p "$WEB_ROOT/$dir"
        chmod 755 "$WEB_ROOT/$dir"
    done
    
    echo -e "${GREEN}✓ Diretórios criados${NC}"
}

# Função para copiar arquivos essenciais
copy_files() {
    echo -e "${YELLOW}Copiando arquivos essenciais...${NC}"
    
    # Arquivos da raiz
    if [ -d "/workspace" ] && [ "/workspace" != "$WEB_ROOT" ]; then
        cp -rn /workspace/* "$WEB_ROOT/" 2>/dev/null || true
        cp -rn /workspace/.* "$WEB_ROOT/" 2>/dev/null || true
    fi
    
    echo -e "${GREEN}✓ Arquivos copiados${NC}"
}

# Função para criar arquivo 404.shtml
create_404_page() {
    echo -e "${YELLOW}Criando página 404 personalizada...${NC}"
    
    cat > "$WEB_ROOT/404.shtml" << 'EOF'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página Não Encontrada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
        }
        h1 { font-size: 6rem; margin: 0; }
        h2 { font-size: 2rem; margin: 20px 0; }
        p { font-size: 1.2rem; opacity: 0.8; }
        a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid white;
            border-radius: 5px;
            margin-top: 20px;
            display: inline-block;
        }
        a:hover { background: white; color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Página Não Encontrada</h2>
        <p>O arquivo solicitado não existe no servidor.</p>
        <a href="/">Voltar ao Início</a>
    </div>
</body>
</html>
EOF
    
    chmod 644 "$WEB_ROOT/404.shtml"
    echo -e "${GREEN}✓ Página 404 criada${NC}"
}

# Função para criar arquivos JS/CSS faltantes
create_assets() {
    echo -e "${YELLOW}Criando assets essenciais...${NC}"
    
    # CSS principal
    if [ ! -f "$WEB_ROOT/css/style.css" ]; then
        cat > "$WEB_ROOT/css/style.css" << 'EOF'
/* UtraFix - Estilos Principais */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
.container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.btn-primary { background: #667eea; color: white; }
EOF
        chmod 644 "$WEB_ROOT/css/style.css"
    fi
    
    # Custom JS
    if [ ! -f "$WEB_ROOT/js/custom.min.js" ]; then
        cat > "$WEB_ROOT/js/custom.min.js" << 'EOF'
// UtraFix - Custom JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('UtraFix initialized');
});
EOF
        chmod 644 "$WEB_ROOT/js/custom.min.js"
    fi
    
    # Deznav Init JS
    if [ ! -f "$WEB_ROOT/js/deznav-init.js" ]; then
        cat > "$WEB_ROOT/js/deznav-init.js" << 'EOF'
// UtraFix - Deznav Navigation Init
(function($) {
    'use strict';
    $(document).ready(function() {
        console.log('Deznav initialized');
    });
})(jQuery);
EOF
        chmod 644 "$WEB_ROOT/js/deznav-init.js"
    fi
    
    # Toastr CSS
    if [ ! -f "$WEB_ROOT/vendor/toastr/css/toastr.min.css" ]; then
        cat > "$WEB_ROOT/vendor/toastr/css/toastr.min.css" << 'EOF'
/* Toastr - Toast Notification CSS */
.toast-title{font-weight:700}.toast-message{-ms-word-wrap:break-word;word-wrap:break-word}
.toast-message a,.toast-message label{color:#FFF}.toast-message a:hover{color:#CCC;text-decoration:none}
.toast-close-button{position:relative;right:-.3em;top:-.3em;float:right;font-size:20px;font-weight:700;color:#FFF;-webkit-text-shadow:0 1px 0 rgba(0,0,0,.45);text-shadow:0 1px 0 rgba(0,0,0,.45);opacity:.8;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=80);filter:alpha(opacity=80)}
.toast-close-button:focus,.toast-close-button:hover{color:#000;text-decoration:none;cursor:pointer;opacity:.4;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=40);filter:alpha(opacity=40)}
.toast-top-center{top:0;right:0;width:100%}.toast-bottom-center{bottom:0;right:0;width:100%}
.toast-top-full-width{top:0;right:0;width:100%}.toast-bottom-full-width{bottom:0;right:0;width:100%}
.toast-top-left{top:12px;left:12px}.toast-top-right{top:12px;right:12px}
.toast-bottom-right{right:12px;bottom:12px}.toast-bottom-left{bottom:12px;left:12px}
#toast-container{position:fixed;z-index:999999}#toast-container *{-moz-box-sizing:border-box;-webkit-box-sizing:border-box;box-sizing:border-box}
#toast-container>div{margin:0 0 6px;padding:15px;width:300px;border-radius:3px;background-position:15px center;background-repeat:no-repeat;background-size:24px;box-shadow:0 0 12px #999;color:#FFF;opacity:.8;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=80);filter:alpha(opacity=80)}
#toast-container>:hover{box-shadow:0 0 12px #000;opacity:1;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=100);filter:alpha(opacity=100);cursor:pointer}
#toast-container>.toast-info{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAGwSURBVEhLtZa9SgNBEMc9sUxxRcoUKSzSWIhXpFMhhYWFhaBg4yPYiWCXZxBLERsLRS3EQkEfwCKdjWJAwSKCgoKCcudv4O5YLrt7EzgXhiU3/4+b2ckmwVjJSpKkQ6wAi4gwhT+z3wRBcEz0yjSseUTrcRyfsHsXmD0AmbHOC2I+8xHl4bnHFJbRtbNdiI18yWaJDxxeQF0cbI1ByIG51BT4WBwBE7/MkbcTPjE9TwpSa2EZJrJE8xya/KGGEEFQ9VNhb7tDQBDSyxPcPsFPTy4yLOhd2OdtXBfA7vzxkrbLTojhXFq6M6KvlTBHzI3OyzWH5rZ8APoAUkSvs0EAAAAASUVORK5CYII=")!important;}
#toast-container>.toast-error{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAHOSURBVEhLrZa/SgNBEMZzh0WKCClSCKaIYOED+AAKeQQLG8HWztLCImBrYadgIdY+gIKNYkBFSwu7CAoqCgkkoGBI/E28PdbLZmeDLgzZzcx83/zZ2SSXC1j9fr+I1Hq93g2yxH4iwM1vkoBWAdxCmpzTxfkN2RcyZNaHFIkSo10+8kgxkXIURV5HGxTmFuc75B2RfQkpxHG8aAgaAFa0tAHqYFfQ7Iwe2yhODk8+J4C7yAoRTWI3w/4klGRgR4lO7Rpn9+gvMyWp+uxFh8+H+ARlgN1nJuJuQAYvNkEnwGFck18Er4q3egEc/oO+mhLdKgRyhdNFiacC0rlOCbhNVz4H9FnAYgDBvU3QIioZlJFLJtsoHYRDfiZoUyIxqCtRpVlANq0EU4dApjrtgezPFad5S19Wgjkc0hNVnuFbHfYqGom7A3B4rVCyfPwi4AAA")!important;}
#toast-container>.toast-success{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADsSURBVEhLY2AYBfQMgf///3P8+/evAIgvA/FsIF+BavYDDWMBGroaSMMBiE8VC7AZDrIFaMFnii3AZTjUgsUUWUDA8OdAH6iQbQEhw4HyGsPEcKBXBIC4ARhex4G4BsjmweU1soIFaGg/WtoFZRIZdEvIMhxkCCjXIVsATV6gFGACs4Rsw0EGgIIH3QJYJgHSARQZDrWAB+jawzgs+Q2UO49D7jnRSRGoEFRILcdmEMWGI0cm0JJ2QpYA1RDvcmzJEWhABhD/pqrL0S0CWuABKgnRki9lLseS7g2AlqwHWQSKH4oKLrILpRGhEQCw2LiNUIa4jAAA")!important;}
#toast-container>.toast-warning{background-image:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAGYSURBVEhL5ZSvTsNQFMbXZGICMYGYmJhAQIJAICYQPAACiSDB8AiICQQJT4CqQEwgJvYASAQCiZiYmJhAIBATCARJy+9rTsldd8sKu1M0+dLb057v6/lbq/2rK0mS/TRNj9cWNAKPYIJII7gIxCcQ51cvqID+GIEX8ASG4B1bK5gIZFeQfoJdEXOfgX4QAQg7kH2A65yQ87lyxb27sggkAzAuFhbbg1K2kgCkB1gVwyUL9m2vl7xtzb8B+ckfTxPbyXP1QiCBjg7B7IOdv7BKFOi/vAfdTZJ+29dWLhFi9AvH35iv65NZYGtjPE9S57uNi9NHUEtRRONxc4+hf0HLjg99k9S3caEREBaZ0NK6L6s5RBBjXxFhqZ1qKWM3oij/Zk8jJGFEQajgkNEcjFEmM7g34rZvSC9l7SYajkRb2RF7B94kf4E6FAuYgwkbrrJ8IaP1BZBC6Fpr2bfHJIwZ5xAfUBPNF4t4wmFVLwUA6JHZc3Pcnk1Y29gJ95XdcWrydT2Wi4+OwJEDjAJS8nHbc7JdsNcgvSAzzR8wI8VDfbbh5WEwxm6dkJJB3C3BoH4uQVyZBJIKl2AgOkQGbovWRWtMZMehMVWqKZHXR9lnL+WOWtTy5Txq3HivZ5hVF04AAP//AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACFPyVvuAXYAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAVSURBVAjXY2AYBaNgKLgCAF8ADQAJdvPiJwAAAABJRU5ErkJggg==");opacity:1}
#toast-container.toast-top-center>div,#toast-container.toast-bottom-center>div{width:300px;margin:auto}#toast-container.toast-top-full-width>div,#toast-container.toast-bottom-full-width>div{width:96%;margin:auto}
.toast{background-color:#030303}.toast-success{background-color:#51A351}.toast-error{background-color:#BD362F}
.toast-info{background-color:#2F96B4}.toast-warning{background-color:#F89406}
.toast-progress{position:absolute;left:0;bottom:0;height:4px;background-color:#000;opacity:.4;-ms-filter:progid:DXImageTransform.Microsoft.Alpha(Opacity=40);filter:alpha(opacity=40)}
@media all and (max-width:240px){#toast-container>div{padding:8px;width:11em}#toast-container .toast-close-button{right:-.2em;top:-.2em}}
@media all and (min-width:241px) and (max-width:480px){#toast-container>div{padding:8px;width:18em}#toast-container .toast-close-button{right:-.2em;top:-.2em}}
@media all and (min-width:481px) and (max-width:768px){#toast-container>div{padding:15px;width:25em}#toast-container .toast-close-button{right:-.3em;top:-.3em}}
EOF
        chmod 644 "$WEB_ROOT/vendor/toastr/css/toastr.min.css"
    fi
    
    # Toastr JS
    if [ ! -f "$WEB_ROOT/vendor/toastr/js/toastr.min.js" ]; then
        cat > "$WEB_ROOT/vendor/toastr/js/toastr.min.js" << 'EOF'
/* Toastr - Toast Notification JS (Minimal) */
!function(e){e.extend({toastr:{info:function(m,t,o){console.log("Info:",t||m)},success:function(m,t,o){console.log("Success:",t||m)},warning:function(m,t,o){console.log("Warning:",t||m)},error:function(m,t,o){console.log("Error:",t||m)}}})}(jQuery);
var toastr=jQuery.toastr;
EOF
        chmod 644 "$WEB_ROOT/vendor/toastr/js/toastr.min.js"
    fi
    
    # Global JS
    if [ ! -f "$WEB_ROOT/vendor/global/global.min.js" ]; then
        cat > "$WEB_ROOT/vendor/global/global.min.js" << 'EOF'
/* UtraFix - Global Utilities */
var UtraFix={version:"1.0",init:function(){console.log("UtraFix Global Initialized")}};
UtraFix.init();
EOF
        chmod 644 "$WEB_ROOT/vendor/global/global.min.js"
    fi
    
    # Bootstrap Select JS
    if [ ! -f "$WEB_ROOT/vendor/bootstrap-select/dist/js/bootstrap-select.min.js" ]; then
        cat > "$WEB_ROOT/vendor/bootstrap-select/dist/js/bootstrap-select.min.js" << 'EOF'
/* Bootstrap Select (Minimal Stub) */
!function(e){e.fn.selectpicker=function(o){return this}}(jQuery);
EOF
        chmod 644 "$WEB_ROOT/vendor/bootstrap-select/dist/js/bootstrap-select.min.js"
    fi
    
    echo -e "${GREEN}✓ Assets criados${NC}"
}

# Função para criar imagens placeholder (SVG inline para evitar dependências)
create_images() {
    echo -e "${YELLOW}Criando imagens placeholder...${NC}"
    
    # Favicon (PNG minimal 16x16 azul)
    if [ ! -f "$WEB_ROOT/images/favicon.png" ]; then
        # Cria um PNG minimal usando base64 (azul 16x16)
        echo 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAdgAAAHYBTnsmCAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAABpSURBVDiN7dMxAQAgDMCwgX/PwQ2oO2TQDJC5Oe/dO+C+7+sGHgBwDQ8AOIYHAJzDAwDO4QEA5/AAgHN4AMA5PADgHB4AcA4PADiHBwCcw8MAzuExgGd4DMA5PAbwDI8BOIfHAJ7hMQDn8BjAMzwG4BweA3iGxwCcwwMAzuEBAOc4AeAADgb/9f8AAAAASUVORK5CYII=' | base64 -d > "$WEB_ROOT/images/favicon.png" 2>/dev/null || echo "" > "$WEB_ROOT/images/favicon.png"
        chmod 644 "$WEB_ROOT/images/favicon.png"
    fi
    
    # Logo (placeholder)
    if [ ! -f "$WEB_ROOT/images/logo-full.png" ]; then
        # Cria um PNG minimal (branco 200x50)
        echo 'iVBORw0KGgoAAAANSUhEUgAAAMIAAAAyCAYAAAAfUZ7EAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAAdgAAAHYBTnsmCAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAACkSURBVHic7d0xAQAgDMCwgX/PwQ2oO2TQDJC5Oe/dO+C+7+sGHgBwDQ8AOIYHAJzDAwDO4QEA5/AAgHN4AMA5PADgHB4AcA4PADiHBwCcw8MAzuExgGd4DMA5PAbwDI8BOIfHAJ7hMQDn8BjAMzwG4BweA3iGxwCcwwMAzuEBAOc4AeAADgb/9f8AAAAASUVORK5CYII=' | base64 -d > "$WEB_ROOT/images/logo-full.png" 2>/dev/null || echo "" > "$WEB_ROOT/images/logo-full.png"
        chmod 644 "$WEB_ROOT/images/logo-full.png"
    fi
    
    echo -e "${GREEN}✓ Imagens criadas${NC}"
}

# Função para configurar .htaccess
configure_htaccess() {
    echo -e "${YELLOW}Configurando .htaccess...${NC}"
    
    if [ ! -f "$WEB_ROOT/.htaccess" ]; then
        cat > "$WEB_ROOT/.htaccess" << 'EOF'
# UtraFix - Configuração Apache Universal
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Redirecionar para HTTPS (opcional, descomente se necessário)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Prevenir acesso a arquivos sensíveis
    RewriteRule ^(\.env|\.git|config\.json|emails\.db)$ - [F,L]
    
    # Error pages personalizadas
    ErrorDocument 404 /404.shtml
    ErrorDocument 403 /403.shtml
    ErrorDocument 500 /500.shtml
</IfModule>

# Segurança adicional
<FilesMatch "\.(php|phtml|php3|php4|php5|cgi|pl|py)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Exceto para index.php e arquivos necessários
<FilesMatch "^(index|logout|get|player_api|webhook_mp|notificacao_mp|xmltv)\.php$">
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Allow from all
    </IfModule>
</FilesMatch>

# Cache para arquivos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
</IfModule>

# Prevenir listagem de diretórios
Options -Indexes
EOF
        chmod 644 "$WEB_ROOT/.htaccess"
    fi
    
    echo -e "${GREEN}✓ .htaccess configurado${NC}"
}

# Função para ajustar permissões de arquivos
set_permissions() {
    echo -e "${YELLOW}Ajustando permissões de arquivos...${NC}"
    
    # Diretórios que precisam de escrita
    writable_dirs=("uploads" "logs" "cliente" "p2p")
    for dir in "${writable_dirs[@]}"; do
        if [ -d "$WEB_ROOT/$dir" ]; then
            chmod 755 "$WEB_ROOT/$dir"
            find "$WEB_ROOT/$dir" -type f -exec chmod 644 {} \; 2>/dev/null || true
        fi
    done
    
    # Arquivos PHP
    find "$WEB_ROOT" -name "*.php" -type f -exec chmod 644 {} \; 2>/dev/null || true
    
    # Scripts executáveis
    if [ -f "$WEB_ROOT/install.sh" ]; then
        chmod 755 "$WEB_ROOT/install.sh"
    fi
    
    echo -e "${GREEN}✓ Permissões ajustadas${NC}"
}

# Função para gerar relatório final
generate_report() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║          INSTALAÇÃO CONCLUÍDA COM SUCESSO!             ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${GREEN}✓ Diretório de instalação:${NC} $WEB_ROOT"
    echo -e "${GREEN}✓ Arquivos essenciais:${NC} Copiados/Criados"
    echo -e "${GREEN}✓ Permissões:${NC} Configuradas"
    echo -e "${GREEN}✓ .htaccess:${NC} Configurado"
    echo ""
    echo -e "${YELLOW}Próximos passos:${NC}"
    echo "  1. Acesse seu painel em: https://SEU_DOMINIO.com"
    echo "  2. Configure o banco de dados em includes/config.php"
    echo "  3. Execute a importação do banco se necessário"
    echo ""
    echo -e "${YELLOW}Para reinstalar, execute novamente:${NC}"
    echo "  ./install.sh"
    echo ""
}

# Executar instalação
main() {
    detect_web_root
    check_permissions
    create_directories
    copy_files
    create_404_page
    create_assets
    create_images
    configure_htaccess
    set_permissions
    generate_report
}

# Iniciar
main "$@"
