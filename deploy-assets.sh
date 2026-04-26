#!/bin/bash
# Script de Deploy Universal de Assets Estáticos
# Funciona em qualquer hospedagem (cPanel, VPS, Shared, etc.)

echo "=========================================="
echo "  Deploy de Assets - UtraFix"
echo "=========================================="

# Detectar diretório raiz do script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="${1:-$SCRIPT_DIR}"

echo "Diretório de origem: $SCRIPT_DIR"
echo "Diretório de destino: $TARGET_DIR"
echo ""

# Criar estrutura de diretórios
echo "Criando estrutura de diretórios..."
mkdir -p "$TARGET_DIR/css"
mkdir -p "$TARGET_DIR/js"
mkdir -p "$TARGET_DIR/images"
mkdir -p "$TARGET_DIR/vendor/toastr/css"
mkdir -p "$TARGET_DIR/vendor/toastr/js"
mkdir -p "$TARGET_DIR/vendor/global"
mkdir -p "$TARGET_DIR/vendor/bootstrap-select/dist/js"

# Copiar arquivos CSS
echo "Copiando arquivos CSS..."
if [ -f "$SCRIPT_DIR/css/style.css" ]; then
    cp "$SCRIPT_DIR/css/style.css" "$TARGET_DIR/css/"
    echo "  ✓ style.css"
fi

# Copiar arquivos JS
echo "Copiando arquivos JS..."
if [ -f "$SCRIPT_DIR/js/custom.min.js" ]; then
    cp "$SCRIPT_DIR/js/custom.min.js" "$TARGET_DIR/js/"
    echo "  ✓ custom.min.js"
fi
if [ -f "$SCRIPT_DIR/js/deznav-init.js" ]; then
    cp "$SCRIPT_DIR/js/deznav-init.js" "$TARGET_DIR/js/"
    echo "  ✓ deznav-init.js"
fi

# Copiar imagens
echo "Copiando imagens..."
if [ -f "$SCRIPT_DIR/images/favicon.png" ]; then
    cp "$SCRIPT_DIR/images/favicon.png" "$TARGET_DIR/images/"
    echo "  ✓ favicon.png"
fi
if [ -f "$SCRIPT_DIR/images/logo-full.png" ]; then
    cp "$SCRIPT_DIR/images/logo-full.png" "$TARGET_DIR/images/"
    echo "  ✓ logo-full.png"
fi

# Copiar vendor toastr
echo "Copiando vendor.toastr..."
if [ -f "$SCRIPT_DIR/vendor/toastr/css/toastr.min.css" ]; then
    cp "$SCRIPT_DIR/vendor/toastr/css/toastr.min.css" "$TARGET_DIR/vendor/toastr/css/"
    echo "  ✓ toastr.min.css"
fi
if [ -f "$SCRIPT_DIR/vendor/toastr/js/toastr.min.js" ]; then
    cp "$SCRIPT_DIR/vendor/toastr/js/toastr.min.js" "$TARGET_DIR/vendor/toastr/js/"
    echo "  ✓ toastr.min.js"
fi

# Copiar vendor global
echo "Copiando vendor.global..."
if [ -f "$SCRIPT_DIR/vendor/global/global.min.js" ]; then
    cp "$SCRIPT_DIR/vendor/global/global.min.js" "$TARGET_DIR/vendor/global/"
    echo "  ✓ global.min.js"
fi

# Copiar vendor bootstrap-select
echo "Copiando vendor.bootstrap-select..."
if [ -f "$SCRIPT_DIR/vendor/bootstrap-select/dist/js/bootstrap-select.min.js" ]; then
    cp "$SCRIPT_DIR/vendor/bootstrap-select/dist/js/bootstrap-select.min.js" "$TARGET_DIR/vendor/bootstrap-select/dist/js/"
    echo "  ✓ bootstrap-select.min.js"
fi

# Copiar página 404
echo "Copiando página 404..."
if [ -f "$SCRIPT_DIR/404.shtml" ]; then
    cp "$SCRIPT_DIR/404.shtml" "$TARGET_DIR/"
    echo "  ✓ 404.shtml"
fi

echo ""
echo "=========================================="
echo "  Deploy concluído com sucesso!"
echo "=========================================="
echo ""
echo "Arquivos disponíveis em: $TARGET_DIR"
echo ""
echo "Para usar em produção, execute:"
echo "  bash deploy-assets.sh /caminho/do/seu/site"
echo ""
