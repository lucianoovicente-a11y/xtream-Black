# 📘 GUIA COMPLETO - SISTEMA DE ATUALIZAÇÃO AUTOMÁTICA

## 🎯 COMO FUNCIONA

O sistema verifica automaticamente no GitHub se há uma nova versão toda vez que a página é carregada. Se houver uma versão mais recente, ele mostra um botão para atualizar com 1 clique.

### Fluxo:
1. **Você sobe os arquivos atualizados para o GitHub**
2. **Atualiza o `version.json` no GitHub com número maior**
3. **O sistema na hospedagem detecta automaticamente**
4. **Clica em "Atualizar Agora"**
5. **O sistema baixa todos os arquivos, mantém config.json e .env**
6. **Pronto! Sistema atualizado!**

---

## 📋 CONFIGURAÇÃO INICIAL

### Passo 1: Criar Repositório no GitHub

1. Acesse https://github.com
2. Crie um novo repositório (ex: `xtream-server`)
3. Deixe como **PÚBLICO** (ou privado com token)

### Passo 2: Configurar o Script

Edite o arquivo `/admin/auto_update.php`:

```php
private $githubRepo = 'SEU-USUARIO/xtream-server'; // MUDE AQUI!
private $githubBranch = 'main'; // Ou 'master'
```

### Passo 3: Criar version.json no GitHub

Na raiz do seu repositório GitHub, crie o arquivo `version.json`:

```json
{
    "version": "1.0.1",
    "release_date": "2025-10-27",
    "changelog_url": "https://github.com/SEU-USUARIO/xtream-server/blob/main/CHANGELOG.md",
    "download_url": "https://github.com/SEU-USUARIO/xtream-server/archive/refs/tags/v1.0.1.zip",
    "minimum_php_version": "7.4",
    "features": [
        "Nova feature 1",
        "Nova feature 2"
    ],
    "bugs_fixed": [
        "Bug fix 1",
        "Bug fix 2"
    ],
    "breaking_changes": false,
    "update_required": false
}
```

---

## 🚀 COMO LANÇAR UMA NOVA VERSÃO

### Passo a Passo:

#### 1. Faça as alterações no código localmente

```bash
# Edite os arquivos necessários
# Ex: corrigiu um bug, adicionou feature, etc.
```

#### 2. Atualize o version.json LOCAL

Aumente o número da versão (seguindo versionamento semântico):
- **MAJOR**: 1.0.0 → 2.0.0 (mudanças quebram compatibilidade)
- **MINOR**: 1.0.0 → 1.1.0 (novas features, compatível)
- **PATCH**: 1.0.0 → 1.0.1 (correções de bugs)

```json
{
    "version": "1.0.2",
    "release_date": "2025-10-28",
    ...
}
```

#### 3. Crie uma TAG no Git

```bash
git add .
git commit -m "Release v1.0.2 - Correção de bugs"
git tag v1.0.2
git push origin main --tags
```

#### 4. Atualize o version.json no GitHub

Faça upload do novo `version.json` para a raiz do repositório GitHub.

**IMPORTANTE:** O `download_url` deve apontar para a TAG criada:
```
https://github.com/SEU-USUARIO/xtream-server/archive/refs/tags/v1.0.2.zip
```

#### 5. Teste a Atualização

Acesse: `http://seusite.com/admin/auto_update.php`

O sistema deve mostrar:
- ✅ Versão atual: 1.0.1
- ✅ Nova versão disponível: 1.0.2
- ✅ Botão "🚀 Atualizar Agora"

---

## 🔧 ARQUIVOS PROTEGIDOS (NÃO SÃO SUBSCRITOS)

O sistema **NÃO** substitui estes arquivos durante a atualização:

- `config.json` - Suas configurações
- `.env` - Variáveis de ambiente
- `version.json` - Versão local (é atualizado separadamente)
- `.htaccess` - Regras Apache
- Diretório `backup/` - Seus backups
- Diretório `logs/` - Logs do sistema
- Diretório `uploads/` - Arquivos enviados

---

## 📊 API REST

O sistema possui 3 endpoints para integração:

### 1. Verificar Atualização
```
GET /admin/auto_update.php?action=check

Resposta:
{
    "has_update": true,
    "current_version": "1.0.1",
    "latest_version": "1.0.2",
    "features": [...],
    "bugs_fixed": [...]
}
```

### 2. Executar Atualização
```
POST /admin/auto_update.php?action=update

Resposta:
{
    "success": true,
    "message": "Atualizado! 45 arquivos, 5 mantidos.",
    "backup_dir": "../backup/update_20251027_143022",
    "from_version": "1.0.1",
    "to_version": "1.0.2"
}
```

### 3. Histórico (Changelog)
```
GET /admin/auto_update.php?action=history
```

---

## ⚠️ IMPORTANTE

### Antes de Atualizar:
1. ✅ Faça backup manual do banco de dados
2. ✅ Teste em ambiente de homologação primeiro
3. ✅ Verifique se há `breaking_changes: true` no version.json

### Se algo der errado:
1. Os backups estão em `/backup/update_YYYYMMDD_HHMMSS/`
2. Para restaurar, copie os arquivos do backup de volta
3. O version.json anterior também está no backup

---

## 🎨 INTERFACE VISUAL

Ao acessar `/admin/auto_update.php`, você verá:

```
╔══════════════════════════════════════╗
║  🔄 Atualização Automática           ║
╠══════════════════════════════════════╣
║  Versão Atual: 1.0.1                 ║
║                                      ║
║  ✅ Nova versão disponível: 1.0.2   ║
║                                      ║
║  [🚀 Atualizar Agora]                ║
╚══════════════════════════════════════╝
```

Durante a atualização:
```
⏳ Baixando atualização...
✅ Extraído: 45 arquivos
✅ Mantidos: 5 arquivos (config, logs, etc)
✅ Backup criado: update_20251027_143022
✅ Atualização concluída!
```

---

## 🔄 AUTO-CHECK

O sistema verifica automaticamente por novas versões a cada **5 minutos** enquanto a página estiver aberta.

Também é possível verificar manualmente recarregando a página.

---

## 📝 EXEMPLO PRÁTICO

### Cenário: Você corrigiu um bug crítico

1. **Localmente:**
   ```bash
   # Edita arquivo com bug
   nano classes/ConnectionManager.class.php
   
   # Commit
   git add .
   git commit -m "Fix: corrige vazamento de conexões"
   git tag v1.0.2
   git push origin main --tags
   ```

2. **GitHub:**
   - Atualiza `version.json` com versão 1.0.2
   - Muda `download_url` para v1.0.2.zip

3. **Hospedagem:**
   - Acessa `http://seusite.com/admin/auto_update.php`
   - Vê "Nova versão: 1.0.2"
   - Clica em "Atualizar Agora"
   - Aguarda 10 segundos
   - Pronto! Bug corrigido!

---

## 🛡️ SEGURANÇA

- ✅ Apenas usuários logados no admin podem atualizar
- ✅ Backup automático antes de atualizar
- ✅ Validação de integridade dos arquivos
- ✅ Rollback automático em caso de erro
- ✅ Arquivos sensíveis protegidos

---

## ❓ DÚVIDAS FREQUENTES

### Posso usar repositório privado?
Sim! Adicione um token de acesso no curl:
```php
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: token SEU_TOKEN']);
```

### E se eu quiser forçar atualização mesmo versão?
Mude `breaking_changes: true` no version.json do GitHub.

### Posso atualizar via linha de comando?
Sim! Use curl:
```bash
curl -X POST http://seusite.com/admin/auto_update.php?action=update
```

### Quantos arquivos são atualizados?
Depende do que mudou. O sistema mostra exatamente quantos foram atualizados e quantos foram mantidos.

---

## 📞 SUPORTE

Se tiver problemas:
1. Verifique os logs em `/logs/`
2. Confira o backup em `/backup/update_*/`
3. Teste download manual do ZIP
4. Verifique permissões de escrita

---

**Status:** ✅ Pronto para produção!  
**Versão deste guia:** 1.0.0  
**Última atualização:** 2025-10-27
