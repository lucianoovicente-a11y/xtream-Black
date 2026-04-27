# 📋 GUIA DE INSTALAÇÃO - XTREAM SERVER OPENSOURCE

## ✅ PRÉ-REQUISITOS

- **PHP 8.0 ou superior**
- **MySQL 5.7+ ou MariaDB 10.3+**
- **Apache com mod_rewrite habilitado**
- **Extensões PHP necessárias:**
  - pdo_mysql
  - curl
  - mbstring
  - json
  - zip
  - gd
  - openssl

---

## 🚀 PASSO A PASSO DA INSTALAÇÃO

### 1. Upload dos Arquivos

Faça upload de todos os arquivos para o seu servidor via FTP ou SSH:

```bash
# Via SSH (recomendado)
cd /var/www/html
git clone https://github.com/seu-repositorio/xtream-server.git .
# OU faça upload manual dos arquivos
```

### 2. Configurar Permissões

```bash
# Definir proprietário correto (ajuste conforme seu servidor)
chown -R www-data:www-data /var/www/html

# Permissões para diretórios importantes
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/uploads
chmod -R 777 /var/www/html/logs
chmod -R 777 /var/www/html/backup
```

### 3. Importar Banco de Dados

1. Acesse o phpMyAdmin ou cliente MySQL
2. Crie um novo banco de dados:
   ```sql
   CREATE DATABASE xtream_server CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```
3. Importe o arquivo SQL localizado em `Banco de dados/SQL 18-10.sql`

### 4. Configurar Conexão com Banco de Dados

Edite o arquivo `/api/controles/db.php`:

```php
$endereco = 'localhost';
$banco = 'xtream_server';
$dbusuario = 'seu_usuario';
$dbsenha = 'sua_senha';
```

### 5. Configurar Ambiente (Opcional mas Recomendado)

Copie o arquivo `.env.example` para `.env` e ajuste as configurações:

```bash
cp .env.example .env
nano .env
```

### 6. Configurar Apache

Crie ou edite o arquivo `.htaccess` na raiz:

```apache
RewriteEngine On
RewriteBase /

# Redirecionar para HTTPS (opcional)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevenir acesso a arquivos sensíveis
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger diretórios
<IfModule mod_autoindex.c>
    Options -Indexes
</IfModule>
```

### 7. Acessar o Sistema

Acesse pelo navegador:
```
http://seu-dominio.com
```

**Credenciais padrão:**
- **Usuário:** admin
- **Senha:** admin

⚠️ **IMPORTANTE:** Altere a senha imediatamente após o primeiro login!

---

## 🔧 CONFIGURAÇÕES ADICIONAIS

### Otimizações do PHP (php.ini)

```ini
upload_max_filesize = 2G
post_max_size = 2G
max_execution_time = 3600
max_input_time = 3600
memory_limit = 512M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### Configurar Cron Jobs (Opcional)

Para atualizações automáticas de EPG e limpeza de logs:

```bash
crontab -e

# Atualizar EPG diariamente às 3 AM
0 3 * * * php /var/www/html/atualizar_epg.php

# Limpar logs antigos semanalmente
0 4 * * 0 php /var/www/html/api/limpar-cache.php
```

---

## 🛡️ MEDIDAS DE SEGURANÇA RECOMENDADAS

1. **Alterar credenciais padrão imediatamente**
2. **Habilitar HTTPS/SSL**
3. **Proteger diretórios sensíveis:**
   ```apache
   # No .htaccess
   RedirectMatch 403 /\.
   RedirectMatch 403 /logs
   RedirectMatch 403 /backup
   ```

4. **Desabilitar exibição de erros em produção**
5. **Usar senhas fortes**
6. **Manter backups regulares**
7. **Atualizar o sistema regularmente**

---

## 📁 ESTRUTURA DE DIRETÓRIOS

```
/workspace
├── api/                    # APIs do sistema
│   └── controles/          # Controllers e conexão DB
├── classes/                # Classes PHP (Models, Auth, etc.)
├── includes/               # Includes globais
│   ├── config.php          # Configuração principal
│   └── inc.php             # Funções utilitárias
├── uploads/                # Arquivos enviados
├── logs/                   # Logs do sistema
├── backup/                 # Backups
├── js/                     # JavaScripts
├── css/                    # Stylesheets
├── img/                    # Imagens
├── admin/                  # Área administrativa
├── cliente/                # Área do cliente
└── Banco de dados/         # Scripts SQL
```

---

## 🐛 SOLUÇÃO DE PROBLEMAS

### Erro de Conexão com Banco de Dados
- Verifique as credenciais em `/api/controles/db.php`
- Confirme que o banco de dados foi importado corretamente
- Verifique se o usuário tem permissões adequadas

### Erro 500 Internal Server Error
- Verifique os logs de erro do PHP
- Confirme que todas as extensões necessárias estão instaladas
- Verifique as permissões dos arquivos

### Upload não funciona
- Verifique `upload_max_filesize` e `post_max_size` no php.ini
- Confirme permissões de escrita na pasta `uploads/`
- Verifique o espaço em disco disponível

### Sessão expira muito rápido
- Aumente `session.gc_maxlifetime` no php.ini
- Verifique a configuração `SESSION_TIMEOUT` no config.php

---

## 📞 SUPORTE

- **Grupo Telegram:** [@xtreamserveropengrupo](https://t.me/xtreamserveropengrupo)
- **Canal Telegram:** [@xtreamserveropen](https://t.me/xtreamserveropen)
- **Dev:** Luciano Vicente - 21971877485

---

## 💝 COMO APOIAR

- **PIX:** `877eac58-cedc-400b-b91f-db8681ac8923`
- **Mercado Pago:** [Link de Doação](https://link.mercadopago.com.br/lucianovicente)

---

## 📝 LICENÇA

Este é um projeto opensource. Sinta-se livre para usar, modificar e contribuir!

**Versão:** 1.0.0  
**Última atualização:** 2025

---

🎉 **Instalação concluída! Aproveite o XTREAM SERVER OPENSOURCE!** 🚀
