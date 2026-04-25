<?php
// Arquivo: api/controles/revendedores.php - VERSÃO SIMPLES

require_once('db.php');

function edite_admin() {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    if ($admin_id <= 0) {
        return ['title' => 'Erro!', 'msg' => 'Sessão não encontrada. Faça login novamente.', 'icon' => 'error'];
    }
    
    $stmt = $conexao->prepare("SELECT user, pass FROM admin WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin_data) {
        return ['title' => 'Erro!', 'msg' => 'Admin não encontrado.', 'icon' => 'error'];
    }
    
    $modal_body = '
        <input type="hidden" name="confirme_edite_admin" value="1">
        <div class="form-group mb-3">
            <label style="font-size: 18px; color: #fff;"><strong>Usuário Admin:</strong></label>
            <input type="text" class="form-control" name="usuario" value="' . htmlspecialchars($admin_data['user']) . '" required style="font-size: 18px; padding: 15px;">
        </div>
        <div class="form-group mb-3">
            <label style="font-size: 18px; color: #fff;"><strong>Nova Senha (deixe em branco para não alterar):</strong></label>
            <input type="password" class="form-control" name="senha" placeholder="••••••••" style="font-size: 18px; padding: 15px;">
        </div>';
    
    $modal_footer = '<button type="button" onclick="salvarAdmin()" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px;">Salvar Alterações</button>';
    
    $modal_scripts = '
    <script>
    function salvarAdmin() {
        var form = document.getElementById("modal_master_form");
        var formData = new FormData(form);
        
        fetch("api/revendedores.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.icon === "success") {
                Swal.fire(data.title, data.msg, "success").then(() => location.reload());
            } else {
                Swal.fire(data.title, data.msg, "error");
            }
        })
        .catch(err => {
            Swal.fire("Erro", "Erro de comunicação", "error");
        });
    }
    </script>';
    
    return [
        'modal_header_class' => "modal-header bg-primary text-white",
        'modal_titulo' => "✏️ EDITAR ADMIN",
        'modal_body' => $modal_body,
        'modal_footer' => $modal_footer,
        'modal_scripts' => $modal_scripts
    ];
}

function confirme_edite_admin($usuario, $senha) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    if (empty($usuario)) {
        return ['title' => 'Erro!', 'msg' => 'O usuário não pode estar vazio.', 'icon' => 'error'];
    }
    
    try {
        if (!empty($senha)) {
            $stmt = $conexao->prepare("UPDATE admin SET user = :user, pass = :pass WHERE id = :id");
            $stmt->bindParam(':pass', $senha);
        } else {
            $stmt = $conexao->prepare("UPDATE admin SET user = :user WHERE id = :id");
        }
        
        $stmt->bindParam(':user', $usuario);
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $_SESSION['username'] = $usuario;
        
        return ['title' => '✅ Sucesso!', 'msg' => 'Dados atualizados com sucesso!', 'icon' => 'success'];
    } catch (Exception $e) {
        return ['title' => '❌ Erro!', 'msg' => 'Erro ao atualizar: ' . $e->getMessage(), 'icon' => 'error'];
    }
}

function edite_admin_revenda() {
    $modal_body = '
        <input type="hidden" name="confirme_edite_admin_revenda" value="1">
        <div class="form-group mb-3">
            <label style="font-size: 18px; color: #fff;"><strong>Senha Atual:</strong></label>
            <input type="password" class="form-control" name="senha_atual" required style="font-size: 18px; padding: 15px;">
        </div>
        <div class="form-group mb-3">
            <label style="font-size: 18px; color: #fff;"><strong>Nova Senha:</strong></label>
            <input type="password" class="form-control" name="nova_senha" required style="font-size: 18px; padding: 15px;">
        </div>
        <div class="form-group mb-3">
            <label style="font-size: 18px; color: #fff;"><strong>Confirmar Senha:</strong></label>
            <input type="password" class="form-control" name="confirme_senha" required style="font-size: 18px; padding: 15px;">
        </div>';
    
    $modal_footer = '<button type="button" onclick="salvarSenha()" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px;">Alterar Senha</button>';
    
    $modal_scripts = '
    <script>
    function salvarSenha() {
        var nova = document.querySelector("input[name=nova_senha]").value;
        var confirme = document.querySelector("input[name=confirme_senha]").value;
        
        if (nova !== confirme) {
            Swal.fire("Erro", "As senhas não conferem!", "error");
            return;
        }
        
        var form = document.getElementById("modal_master_form");
        var formData = new FormData(form);
        
        fetch("api/revendedores.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            Swal.fire(data.title, data.msg, data.icon).then(() => {
                if (data.icon === "success") {
                    var modal = bootstrap.Modal.getInstance(document.getElementById("modal_master"));
                    modal.hide();
                }
            });
        });
    }
    </script>';
    
    return [
        'modal_header_class' => "modal-header bg-warning text-dark",
        'modal_titulo' => "✏️ ALTERAR SENHA",
        'modal_body' => $modal_body,
        'modal_footer' => $modal_footer,
        'modal_scripts' => $modal_scripts
    ];
}

function confirme_edite_admin_revenda($senha_atual, $nova_senha) {
    $conexao = conectar_bd();
    $admin_id = $_SESSION['admin_id'] ?? 0;
    
    $stmt = $conexao->prepare("SELECT pass FROM admin WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin['pass'] != $senha_atual) {
        return ['title' => 'Erro!', 'msg' => 'Senha atual incorreta.', 'icon' => 'error'];
    }
    
    try {
        $stmt = $conexao->prepare("UPDATE admin SET pass = :pass WHERE id = :id");
        $stmt->bindParam(':pass', $nova_senha);
        $stmt->bindParam(':id', $admin_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return ['title' => '✅ Sucesso!', 'msg' => 'Senha alterada com sucesso!', 'icon' => 'success'];
    } catch (Exception $e) {
        return ['title' => '❌ Erro!', 'msg' => 'Erro ao alterar senha.', 'icon' => 'error'];
    }
}
?>