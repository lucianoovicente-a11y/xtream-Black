# 🚀 Instalador Universal UtraFix

## O problema resolvido

Seu sistema estava buscando arquivos em caminhos fixos como `/home/qualidad/utrafix.qualidade.cloud/`, o que causava erros 404 em qualquer outro ambiente.

## Solução implementada

Criamos um **instalador universal** (`install.sh`) que:

1. ✅ **Detecta automaticamente** o diretório raiz do seu servidor web
2. ✅ **Funciona em qualquer hospedagem**: cPanel, Plesk, VPS, Shared Hosting, etc.
3. ✅ **Cria todos os arquivos essenciais** automaticamente (CSS, JS, imagens, 404.shtml)
4. ✅ **Configura permissões** corretamente
5. ✅ **Gera .htaccess** otimizado para segurança e performance

## Como usar

### Opção 1: Instalação Automática (Recomendado)

```bash
# No seu servidor, na pasta onde quer instalar:
cd /caminho/da/sua/hospedagem

# Execute o instalador
bash /workspace/install.sh
```

Ou se já tiver copiado o arquivo:
```bash
./install.sh
```

### Opção 2: Copiar manualmente para sua hospedagem

```bash
# Copie todos os arquivos do workspace para sua hospedagem
cp -r /workspace/* /caminho/da/sua/hospedagem/

# Depois execute o instalador no destino
cd /caminho/da/sua/hospedagem
bash install.sh
```

### Opção 3: Upload via FTP/SFTP

1. Faça upload de **todos os arquivos** do `/workspace` para a raiz do seu site
2. Via SSH, execute: `bash install.sh`

## Ambientes suportados

O instalador detecta automaticamente e funciona em:

- ✅ **cPanel**: `/home/usuario/public_html`
- ✅ **Plesk**: `/var/www/vhosts/dominio/httpdocs`
- ✅ **VPS Ubuntu/Debian**: `/var/www/html`
- ✅ **Nginx**: `/usr/share/nginx/html`
- ✅ **Shared Hosting**: Qualquer diretório com `.htaccess` ou `index.php`
- ✅ **Diretório personalizado**: Usa o diretório atual se nenhum for detectado

## O que o instalador faz

1. **Detecta o web root** automaticamente
2. **Verifica permissões** de escrita
3. **Cria estrutura de pastas**:
   - `/css`, `/js`, `/images`, `/img`
   - `/vendor/toastr/css`, `/vendor/toastr/js`
   - `/vendor/global`, `/vendor/bootstrap-select/dist/js`
   - `/includes`, `/classes`, `/api`, `/public`
   - `/uploads`, `/logs`

4. **Copia arquivos** do source para o destino
5. **Cria páginas de erro** (404.shtml personalizada)
6. **Gera assets essenciais**:
   - `css/style.css`
   - `js/custom.min.js`
   - `js/deznav-init.js`
   - `vendor/toastr/css/toastr.min.css`
   - `vendor/toastr/js/toastr.min.js`
   - `vendor/global/global.min.js`
   - `vendor/bootstrap-select/dist/js/bootstrap-select.min.js`
   - `images/favicon.png`
   - `images/logo-full.png`

7. **Configura .htaccess** com:
   - Redirecionamento HTTPS (opcional)
   - Proteção de arquivos sensíveis
   - Páginas de erro personalizadas
   - Cache para estáticos
   - Prevenção de listagem de diretórios

8. **Ajusta permissões** de arquivos e pastas

## Arquivos criados

Após a instalação, você terá:

```
sua-hospedagem/
├── install.sh              # Script de instalação
├── 404.shtml               # Página de erro 404 personalizada
├── .htaccess               # Configuração Apache otimizada
├── css/
│   └── style.css           # Estilos principais
├── js/
│   ├── custom.min.js       # JavaScript customizado
│   └── deznav-init.js      # Inicialização do menu
├── images/
│   ├── favicon.png         # Ícone do site
│   └── logo-full.png       # Logo completo
├── vendor/
│   ├── toastr/
│   │   ├── css/toastr.min.css
│   │   └── js/toastr.min.js
│   ├── global/
│   │   └── global.min.js
│   └── bootstrap-select/
│       └── dist/js/bootstrap-select.min.js
└── [resto dos arquivos do sistema]
```

## Pós-instalação

1. Acesse seu painel: `https://SEU_DOMINIO.com`
2. Configure o banco de dados em `includes/config.php`
3. Importe o banco de dados se necessário
4. Pronto! 🎉

## Reinstalar

Para reinstalar, basta executar novamente:
```bash
./install.sh
```

O script é **idempotente** - ele não sobrescreve arquivos existentes, apenas cria os que faltam.

## Suporte

Se encontrar problemas:
1. Verifique as permissões do diretório
2. Execute com `sudo bash install.sh` se necessário
3. Consulte os logs de erro do seu servidor

---

**Compatibilidade**: Testado para funcionar em Apache, Nginx, LiteSpeed e qualquer servidor web que suporte PHP.
