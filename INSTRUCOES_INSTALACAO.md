# 📘 INSTRUÇÕES DE INSTALAÇÃO E CONFIGURAÇÃO
## XTREAM SERVER OPENSOURCE - GUIA COMPLETO

---

## 📋 ÍNDICE

1. [Requisitos do Sistema](#1-requisitos-do-sistema)
2. [Instalação Passo a Passo](#2-instalação-passo-a-passo)
3. [Configuração Inicial](#3-configuração-inicial)
4. [Configuração do Sistema de Atualização Automática](#4-configuração-do-sistema-de-atualização-automática)
5. [Como Publicar Atualizações no GitHub](#5-como-publicar-atualizações-no-github)
6. [Primeiros Passos no Sistema](#6-primeiros-passos-no-sistema)
7. [Solução de Problemas](#7-solução-de-problemas)

---

## 1. REQUISITOS DO SISTEMA

### Mínimos:
- **PHP:** 7.4 ou superior (recomendado 8.0+)
- **MySQL:** 5.7 ou superior / MariaDB 10.2+
- **Web Server:** Apache com mod_rewrite habilitado
- **Extensões PHP:** pdo, pdo_mysql, curl, json, mbstring, openssl
- **Permissões:** Escrita nas pastas `logs/`, `backup/`, `uploads/`
- **SSL:** Certificado SSL instalado (recomendado)

### Recomendados:
- **RAM:** 2GB mínimo (4GB+ para produção)
- **CPU:** 2 cores mínimos
- **Armazenamento:** SSD com espaço suficiente para streams
- **Banda:** 1Gbps+ para múltiplos streams

---

## 2. INSTALAÇÃO PASSO A PASSO

### Passo 1: Download dos Arquivos

```bash
# Clone o repositório ou faça upload via FTP
cd /var/www/html
git clone https://github.com/SEU-USUARIO/xtream-server.git .
# OU faça upload manual dos arquivos via FTP/cPanel
```

### Passo 2: Configurar Permissões

```bash
# Defina permissões corretas
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/logs
chmod -R 777 /var/www/html/backup
chmod -R 777 /var/www/html/uploads
chown -R www-data:www-data /var/www/html
```

### Passo 3: Criar Banco de Dados

```sql
-- Acesse o MySQL/MariaDB
mysql -u root -p

-- Crie o banco de dados
CREATE DATABASE xtream_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crie o usuário (opcional, pode usar root)
CREATE USER 'xtream_user'@'localhost' IDENTIFIED BY 'sua_senha_forte';
GRANT ALL PRIVILEGES ON xtream_db.* TO 'xtream_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Passo 4: Importar Estrutura do Banco

```bash
# Importe o script SQL inicial
mysql -u xtream_user -p xtream_db < "/var/www/html/Banco de dados/estrutura_inicial.sql"

# Execute as atualizações
mysql -u xtream_user -p xtream_db < "/var/www/html/Banco de dados/update_1.0.0.sql"
```

### Passo 5: Configurar Variáveis de Ambiente

```bash
# Copie o arquivo de exemplo
cp /var/www/html/.env.example /var/www/html/.env

# Edite o arquivo .env
nano /var/www/html/.env
```

**Conteúdo do `.env`:**
```env
# Configurações do Banco de Dados
DB_HOST=localhost
DB_NAME=xtream_db
DB_USER=xtream_user
DB_PASS=sua_senha_forte
DB_CHARSET=utf8mb4

# Configurações da Aplicação
APP_NAME="XTream Server"
APP_URL=https://seusite.com
APP_DEBUG=false
APP_ENV=production

# Sessão e Segurança
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true

# Logs
LOG_LEVEL=info
LOG_PATH=/var/www/html/logs

# GitHub (para atualizações)
GITHUB_OWNER=SEU-USUARIO
GITHUB_REPO=xtream-server
GITHUB_BRANCH=main
```

### Passo 6: Configurar Apache

```bash
# Habilite mod_rewrite
a2enmod rewrite

# Configure o VirtualHost (exemplo)
nano /etc/apache2/sites-available/xtream.conf
```

**Conteúdo do VirtualHost:**
```apache
<VirtualHost *:80>
    ServerName seusite.com
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/xtream_error.log
    CustomLog ${APACHE_LOG_DIR}/xtream_access.log combined
</VirtualHost>
```

```bash
# Ative o site e reinicie Apache
a2ensite xtream.conf
systemctl restart apache2
```

### Passo 7: Instalar Dependências (se houver Composer)

```bash
cd /var/www/html
composer install --no-dev --optimize-autoloader
```

### Passo 8: Acessar pela Primeira Vez

1. Acesse: `https://seusite.com/admin/install.php`
2. Preencha os dados do administrador
3. Configure as definições básicas
4. Faça login no painel administrativo

---

## 3. CONFIGURAÇÃO INICIAL

### Painel Administrativo

Após instalar, acesse `https://seusite.com/admin/` e configure:

1. **Configurações Gerais:**
   - Nome do servidor
   - URL base
   - Timezone
   - Idioma padrão

2. **Configurações de Stream:**
   - Portas HTTP/HTTPS
   - Portas RTMP/RTSP
   - Limite de banda por usuário
   - Codecs suportados

3. **Configurações de Segurança:**
   - Habilitar 2FA
   - Forçar HTTPS
   - IPs permitidos/bloqueados
   - Rate limiting

4. **Configurações de Logo:**
   - Upload da logotipo
   - Dimensões recomendadas: 200x50px
   - Formato: PNG com fundo transparente

---

## 4. CONFIGURAÇÃO DO SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA

### Como Funciona:

O sistema verifica automaticamente a cada carregamento de página se há uma nova versão disponível no GitHub. Se houver, notifica o administrador e permite atualização com 1 clique.

### Passo 1: Configurar Repositório GitHub

No seu servidor, edite o arquivo de configuração:

```bash
nano /var/www/html/includes/config.php
```

Adicione/edite estas linhas:
```php
define('GITHUB_OWNER', 'SEU-USUARIO-GITHUB');
define('GITHUB_REPO', 'xtream-server');
define('GITHUB_BRANCH', 'main'); // ou 'master'
```

### Passo 2: Criar Arquivo version.json no GitHub

Este é o arquivo **MAIS IMPORTANTE** para o sistema de atualizações.

1. No seu repositório GitHub, crie um arquivo chamado `version.json` na raiz
2. Este arquivo deve conter a versão mais recente

**Exemplo de `version.json`:**
```json
{
    "version": "1.0.0",
    "version_code": 1000,
    "release_date": "2024-01-15",
    "minimum_php": "7.4",
    "minimum_mysql": "5.7",
    "changelog": [
        "✅ Sistema completo de controle de conexões",
        "✅ Área do cliente com histórico",
        "✅ Sistema de tickets",
        "✅ Atualização automática via GitHub",
        "✅ Correção de bugs diversos"
    ],
    "critical": false,
    "download_url": "https://github.com/SEU-USUARIO/xtream-server/archive/main.zip",
    "files_to_exclude": [
        ".env",
        "includes/config.php",
        "uploads/",
        "logs/",
        "backup/"
    ]
}
```

**Campos Explicados:**
- `version`: Versão em formato semântico (ex: 1.0.0, 1.2.3)
- `version_code`: Número inteiro para comparação (1000 = 1.0.0, 1001 = 1.0.1)
- `release_date`: Data de lançamento (YYYY-MM-DD)
- `minimum_php`: Versão mínima do PHP necessária
- `minimum_mysql`: Versão mínima do MySQL necessária
- `changelog`: Lista de mudanças nesta versão
- `critical`: true se for atualização crítica de segurança
- `download_url`: URL direta para download do ZIP
- `files_to_exclude`: Arquivos/pastas que NÃO serão substituídos

### Passo 3: Testar o Sistema de Atualização

1. Acesse o painel administrativo
2. Vá em **Sistema → Atualizações**
3. Clique em **Verificar Atualizações**
4. O sistema comparará automaticamente:
   - Seu `version.json` local (`/var/www/html/version.json`)
   - O `version.json` do GitHub

Se a versão do GitHub for maior, aparecerá a opção **"Atualizar Agora"**.

### Passo 4: O Que Acontece Durante a Atualização

Quando você clicar em "Atualizar":

1. ✅ **Backup Automático:** Todo o sistema é copiado para `backup/`
2. ✅ **Download:** Baixa o ZIP mais recente do GitHub
3. ✅ **Extração:** Extrai os novos arquivos
4. ✅ **Proteção:** Mantém seus arquivos de configuração (.env, config.php)
5. ✅ **Migração:** Executa scripts SQL se necessário
6. ✅ **Limpeza:** Remove arquivos temporários
7. ✅ **Logs:** Registra tudo em `logs/update.log`

### Passo 5: Agendar Verificação Automática (Opcional)

Para verificar atualizações automaticamente via cron:

```bash
# Edite o crontab
crontab -e

# Adicione esta linha (verifica uma vez por dia às 3 AM)
0 3 * * * php /var/www/html/cron/check_updates.php >> /var/www/html/logs/cron_updates.log 2>&1
```

---

## 5. COMO PUBLICAR ATUALIZAÇÕES NO GITHUB

Sempre que quiser lançar uma nova versão:

### Passo 1: Preparar Novos Arquivos

```bash
# No seu computador, com as mudanças feitas
cd /caminho/do/seu/projeto

# Teste localmente
php -l classes/*.php
php -l admin/*.php
```

### Passo 2: Atualizar version.json

Edite o arquivo `version.json` na raiz do projeto:

```json
{
    "version": "1.0.1",          // Aumente a versão
    "version_code": 1001,        // Aumente o código (1000 → 1001)
    "release_date": "2024-01-20", // Nova data
    "changelog": [
        "🐛 Correção: Tempo online dos clientes",
        "🐛 Correção: Logo não atualizava nas configurações",
        "✨ Melhoria: Performance do dashboard",
        "🔒 Segurança: Reforço no sistema de login"
    ]
}
```

**Regra de Versionamento:**
- `X.Y.Z` onde:
  - `X` = Mudança major (quebra compatibilidade)
  - `Y` = Nova funcionalidade (mantém compatibilidade)
  - `Z` = Correção de bugs (patch)

**Exemplos:**
- `1.0.0` → `1.0.1` (correção de bug)
- `1.0.1` → `1.1.0` (nova feature)
- `1.1.0` → `2.0.0` (mudança grande)

### Passo 3: Commit e Push

```bash
# Adicione todos os arquivos
git add .

# Commit com mensagem descritiva
git commit -m "v1.0.1: Correções de tempo online e logo"

# Push para o GitHub
git push origin main
```

### Passo 4: Criar Tag (Opcional mas Recomendado)

```bash
# Crie uma tag
git tag -a v1.0.1 -m "Versão 1.0.1 - Correções importantes"

# Envie a tag
git push origin v1.0.1
```

### Passo 5: Verificar no GitHub

1. Acesse: `https://github.com/SEU-USUARIO/xtream-server`
2. Confirme que `version.json` foi atualizado
3. Verifique se todos os arquivos estão lá

### Passo 6: Testar Atualização

Em seu servidor de produção:

1. Acesse o painel admin
2. Vá em **Sistema → Atualizações**
3. Clique em **Verificar Agora**
4. Deve aparecer: "Nova versão disponível: 1.0.1"
5. Clique em **Atualizar**

---

## 6. PRIMEIROS PASSOS NO SISTEMA

### Após Instalação:

1. **Criar Primeiro Pacote:**
   - Admin → Pacotes → Novo Pacote
   - Defina nome, preço, duração, limites

2. **Criar Primeiro Cliente:**
   - Admin → Clientes → Novo Cliente
   - Preencha dados, selecione pacote
   - Defina limite de conexões (ex: 2)

3. **Adicionar Canais:**
   - Admin → Canais → Importar Lista
   - Ou adicione manualmente

4. **Configurar DNS/URLs:**
   - Admin → Configurações → URLs
   - Defina URL do player, API, etc.

5. **Testar Conexão:**
   - Use um player (VLC, Smarters, etc.)
   - URL: `http://seusite.com:porta`
   - Username/Senha do cliente criado

### Área do Cliente:

Os clientes podem acessar:
- URL: `https://seusite.com/Clientes/login.php`
- Funcionalidades:
  - ✅ Ver histórico de conexões
  - ✅ Acompanhar pagamentos
  - ✅ Abrir tickets de suporte
  - ✅ Cancelar assinatura
  - ✅ Atualizar dados

---

## 7. SOLUÇÃO DE PROBLEMAS

### Problema: Atualização Não Aparece

**Solução:**
```bash
# Verifique o version.json local
cat /var/www/html/version.json

# Verifique permissões
chmod 644 /var/www/html/version.json

# Teste conexão com GitHub
curl -I https://raw.githubusercontent.com/SEU-USUARIO/xtream-server/main/version.json

# Verifique logs
tail -f /var/www/html/logs/update.log
```

### Problema: Erro de Permissão Durante Atualização

**Solução:**
```bash
# Corrija proprietário
chown -R www-data:www-data /var/www/html

# Corrija permissões
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Pastas específicas
chmod 777 /var/www/html/logs
chmod 777 /var/www/html/backup
chmod 777 /var/www/html/uploads
```

### Problema: Versão Não Compara Corretamente

**Solução:**
- Verifique se `version_code` está correto
- Deve ser número inteiro sequencial
- Ex: 1.0.0 = 1000, 1.0.1 = 1001, 1.1.0 = 1100

### Problema: Arquivos de Configuração Foram Substituídos

**Solução:**
- Isso não deveria acontecer!
- Verifique se `files_to_exclude` no `version.json` está correto
- Restaure do backup em `backup/`

### Problema: Timeout Durante Atualização

**Solução:**
```bash
# Aumente timeout do PHP
nano /etc/php/8.0/apache2/php.ini

# Altere:
max_execution_time = 300
max_input_time = 300
memory_limit = 512M

# Reinicie Apache
systemctl restart apache2
```

---

## 📞 SUPORTE

Se encontrar problemas:

1. **Verifique os Logs:**
   - `/var/www/html/logs/error.log`
   - `/var/www/html/logs/update.log`
   - `/var/log/apache2/error.log`

2. **Abra um Ticket:**
   - Acesse sua área do cliente
   - Vá em "Suporte → Abrir Ticket"

3. **GitHub Issues:**
   - https://github.com/SEU-USUARIO/xtream-server/issues

---

## 🎯 CHECKLIST FINAL

Antes de colocar em produção:

- [ ] Banco de dados configurado e importado
- [ ] Arquivo `.env` preenchido corretamente
- [ ] Permissões de pastas definidas
- [ ] SSL instalado e funcionando
- [ ] `version.json` criado no GitHub
- [ ] Configurações do GitHub no `config.php`
- [ ] Primeiro teste de atualização realizado
- [ ] Backup inicial criado manualmente
- [ ] Cliente de teste criado e testado
- [ ] Sistema de tickets testado
- [ ] Área do cliente acessível

---

## 🚀 DICAS PROFISSIONAIS

1. **Sempre faça backup antes de atualizar:**
   ```bash
   mysqldump -u xtream_user -p xtream_db > backup_$(date +%Y%m%d).sql
   ```

2. **Teste em ambiente de homologação primeiro**

3. **Mantenha changelog detalhado** para seus clientes

4. **Use tags semânticas** no Git para versionamento

5. **Monitore logs regularmente** para detectar problemas

6. **Configure alertas** para atualizações críticas

---

**Parabéns!** Seu XTream Server está pronto para produção! 🎉

**Versão deste documento:** 1.0.0  
**Última atualização:** Janeiro 2024
