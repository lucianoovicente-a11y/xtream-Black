# Guia Completo: Sincronização entre GitHub e Hospedagem

## 📋 Visão Geral do Fluxo Ideal de Trabalho

O fluxo recomendado para desenvolvimento e deploy é:

1. **Você desenvolve no seu computador** (ambiente local)
2. **Envia para o GitHub** (`git push`)
3. **Acessa a Hospedagem e puxa do GitHub** (`git pull` ou `git clone`)

```
[Seu Computador] → git push → [GitHub] → git pull → [Hospedagem]
      (Dev)                    (Repositório)         (Produção)
```

---

## 🚀 Passo 1: Preparar o Repositório no GitHub

### 1.1 Criar um repositório no GitHub
1. Acesse https://github.com
2. Clique em **"New"** ou **"+"** → **"New repository"**
3. Dê um nome (ex: `utrafix-qualidade`)
4. Marque como **Public** ou **Private**
5. **NÃO** marque "Initialize this repository with a README" (se já tiver código local)
6. Clique em **"Create repository"**

### 1.2 Configurar Git no seu computador (primeira vez)
```bash
# Configure seu nome e email
git config --global user.name "Seu Nome"
git config --global user.email "seuemail@exemplo.com"
```

### 1.3 Enviar seu código local para o GitHub
No seu computador, na pasta do projeto:

```bash
# Inicializar repositório git (se ainda não existir)
git init

# Adicionar todos os arquivos
git add .

# Fazer o primeiro commit
git commit -m "Primeiro commit - versão inicial"

# Conectar com o repositório remoto (substitua SEU_USUARIO e SEU_REPOSITORIO)
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git

# Enviar para o GitHub
git branch -M main
git push -u origin main
```

---

## 📥 Passo 2: Puxar do GitHub para a Hospedagem

### Opção A: Primeira vez (usando `git clone`)

Acesse sua hospedagem via SSH:

```bash
ssh usuario@seu_servidor.com
```

Navegue até a pasta do subdomínio:

```bash
cd /home/qualidad/utrafix.qualidade.cloud
```

**Importante:** Se já houver arquivos na pasta, faça backup antes:

```bash
# Criar backup
cd /home/qualidad
mv utrafix.qualidade.cloud utrafix.qualidade.cloud.backup
mkdir utrafix.qualidade.cloud
cd utrafix.qualidade.cloud
```

Agora clone o repositório:

```bash
# Substitua SEU_USUARIO e SEU_REPOSITORIO pelos seus dados
git clone https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git .
```

> O ponto `.` no final significa "clonar na pasta atual"

### Opção B: Atualizações futuras (usando `git pull`)

Para atualizações posteriores, basta:

```bash
cd /home/qualidad/utrafix.qualidade.cloud
git pull origin main
```

---

## 📤 Passo 3: Enviar Alterações da Hospedagem para o GitHub (Opcional)

⚠️ **Atenção:** O ideal é fazer alterações apenas no computador local e subir para GitHub. 
Edições diretas na hospedagem devem ser evitadas, mas se necessário:

```bash
cd /home/qualidad/utrafix.qualidade.cloud

# Verificar mudanças
git status

# Adicionar mudanças
git add .

# Commitar mudanças
git commit -m "Alteração feita na hospedagem"

# Enviar para GitHub
git pull origin main  # Primeiro puxe mudanças remotas
git push origin main
```

---

## 🔑 Autenticação no GitHub via SSH (Recomendado)

Para não precisar digitar senha toda vez:

### Na sua hospedagem:

```bash
# Gerar chave SSH
ssh-keygen -t ed25519 -C "seuemail@exemplo.com"
# Pressione Enter para aceitar o padrão

# Ver a chave pública
cat ~/.ssh/id_ed25519.pub
```

Copie TODO o conteúdo mostrado (começa com `ssh-ed25519`...)

### No GitHub:
1. Acesse https://github.com/settings/keys
2. Clique em **"New SSH key"**
3. Dê um título (ex: "Servidor Hospedagem")
4. Cole a chave pública
5. Clique em **"Add SSH key"**

### Testar conexão:
```bash
ssh -T git@github.com
```

Deve aparecer: `Hi SEU_USUARIO! You've successfully authenticated...`

### Usar SSH ao invés de HTTPS:
```bash
# Remover remote HTTPS
git remote remove origin

# Adicionar remote SSH
git remote add origin git@github.com:SEU_USUARIO/SEU_REPOSITORIO.git

# Testar
git pull origin main
```

---

## 🛠️ Comandos Úteis

| Comando | Descrição |
|---------|-----------|
| `git status` | Verifica status dos arquivos |
| `git log` | Histórico de commits |
| `git branch` | Lista branches |
| `git checkout main` | Muda para branch main |
| `git diff` | Ver diferenças não commitadas |
| `git reset --hard HEAD` | Descarta mudanças locais (cuidado!) |

---

## 🔄 Fluxo Completo de Atualização

### No seu computador (desenvolvimento):
```bash
cd /caminho/do/seu/projeto

# Fazer alterações nos arquivos...

git add .
git commit -m "Descrição das mudanças"
git push origin main
```

### Na hospedagem (produção):
```bash
cd /home/qualidad/utrafix.qualidade.cloud
git pull origin main
```

---

## ⚠️ Problemas Comuns e Soluções

### Erro: "already exists and is not an empty directory"
**Solução:** Use `git clone` em pasta vazia ou faça:
```bash
git init
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
git fetch
git reset --hard origin/main
```

### Erro: "Permission denied (publickey)"
**Solução:** Configure a chave SSH conforme seção acima.

### Erro: "conflict" no git pull
**Solução:** 
```bash
# Ver conflitos
git status

# Editar arquivos conflitantes manualmente
# Depois:
git add .
git commit -m "Resolver conflitos"
git pull origin main
```

### Arquivos sendo ignorados
Crie um arquivo `.gitignore` na raiz do projeto com padrões a ignorar:
```
node_modules/
.env
*.log
.DS_Store
```

---

## 📞 Resumo Rápido

### Primeira configuração:
```bash
# Hospedagem
cd /home/qualidad/utrafix.qualidade.cloud
git clone https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git .
```

### Atualizações rotineiras:
```bash
# Computador local
git add . && git commit -m "mudanças" && git push

# Hospedagem
cd /home/qualidad/utrafix.qualidade.cloud && git pull
```

---

## 🎯 Próximos Passos

1. Crie sua conta no GitHub (se não tiver)
2. Crie um repositório para seu projeto
3. Configure Git no seu computador
4. Faça push do código atual
5. Acesse hospedagem via SSH
6. Faça clone/pull na pasta do subdomínio
7. Teste o site no navegador

**Dúvidas?** Consulte a documentação oficial:
- Git: https://git-scm.com/doc
- GitHub: https://docs.github.com
