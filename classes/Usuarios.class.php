<?php

/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */

/**
 * Classe de usuários
 */
class Usuarios extends Sessao
{
	private function verifica_email_valido($email)  
	{  
		$sintaxe='#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#';  
		if(preg_match($sintaxe,$email)){
			return true;  
		}
		return false;  
	}
	
	public function auth($email, $password)
	{
		$email = $this->verifica_email_valido($email) ? trim($email) : false;
		$pass = crypt($password, '$6$rounds=20000$i9teamp2panel$');

		if (!$email) {
			return ["status" => false, "msg" => "O email que você inseriu está incorreto, verifique e tente novamente."]; die();
		}


		$PDO = conexaoPDOlocal();



		if ($PDO !== NULL) {
			$sql = "SELECT * FROM `usuarios` WHERE `email` = :email AND `senha` = :password;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":email", $email, PDO::PARAM_STR, 255);
			$stmt->bindParam(":password", $pass, PDO::PARAM_STR, 255);
			if ($stmt->execute()) {
				$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

				if (count($res) >= 1) {
					if ($res[0]['status'] != 1) {
						return ["status" => false, "msg" => "Você está impedido de acessar esse sistema."];
					}else{
						return ["status" => true, "msg" => "Você está sendo redirecionado...", "admin_id" => $res[0]['id']];
					}
				}else{
					return ["status" => false, "msg" => "Os dados que você forneceu não existe ou estão incorretos!"];
				}



			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function info_user_logado()
	{
		$user_id = $this->get_user_id();


		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "SELECT * FROM `usuarios` WHERE `id` = :user_id;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user_id);
			if ($stmt->execute()) {
				$res = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!empty($res)) {
					return ["status" => true, "msg" => "Listando informações do usuário", "user_info" => $res];
				}
				return ["status" => false, "msg" => "Não encontramos nenhum usuário"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function info_user_id($user_id)
	{
		
		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "SELECT * FROM `usuarios` WHERE `id` = :user_id;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user_id);
			if ($stmt->execute()) {
				$res = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!empty($res)) {
					return ["status" => true, "msg" => "Listando informações do usuário", "user_info" => $res];
				}
				return ["status" => false, "msg" => "Não encontramos nenhum usuário"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function editar_usuario($user_nome, $user_email, $user_senha, $nivel_acesso, $user_id)
	{

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			if (!empty($user_senha)) {
				$novasenha = crypt($user_senha, '$6$rounds=20000$i9teamp2panel$');
				$sql = "UPDATE `usuarios` SET `nome` = :user_nome, `email` = :user_email, `senha` = :user_senha, `nivel` = :nivel_acesso WHERE `usuarios`.`id` = :user_id;";
			}else{
				$sql = "UPDATE `usuarios` SET `nome` = :user_nome, `email` = :user_email, `nivel` = :nivel_acesso WHERE `usuarios`.`id` = :user_id;";
			}
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_STR);
			$stmt->bindParam(":user_nome", $user_nome, PDO::PARAM_STR, 255);
			$stmt->bindParam(":user_email", $user_email, PDO::PARAM_STR, 255);
			if (!empty($user_senha)) {
				$stmt->bindParam(":user_senha", $novasenha, PDO::PARAM_STR, 255);
			}
			$stmt->bindParam(":nivel_acesso", $nivel_acesso, PDO::PARAM_STR);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "Usuário atualizado com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}

	public function adicionar_usuario($user_nome, $user_email, $user_senha, $nivel_acesso)
	{

		$novasenha = crypt($user_senha, '$6$rounds=20000$i9teamp2panel$');


		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `status`, `nivel`) VALUES (NULL, :user_nome, :user_email, :user_senha, 1, :nivel_acesso);";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_nome", $user_nome, PDO::PARAM_STR, 255);
			$stmt->bindParam(":user_email", $user_email, PDO::PARAM_STR, 255);
			$stmt->bindParam(":user_senha", $novasenha, PDO::PARAM_STR, 255);
			$stmt->bindParam(":nivel_acesso", $nivel_acesso, PDO::PARAM_STR);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "Usuário adicionado com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function ativar_desativar_usuario($user_id)
	{


		$info_user = $this->info_user_id($user_id);

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			if (isset($info_user["user_info"]) && $info_user["user_info"]["status"] == 1) {
				$sql = "UPDATE `usuarios` SET `status` = '0' WHERE `id` = :user_id;";
				$msg = "Usuário desativado com sucesso!";
			}else{
				$sql = "UPDATE `usuarios` SET `status` = '1' WHERE `id` = :user_id;";
				$msg = "Usuário ativado com sucesso!";
			}			
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_STR);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => $msg];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function excluir_usuario($user_id)
	{
		$PDO = conexaoPDOlocal();

		if ($PDO !== NULL) {
			$sql = "DELETE FROM `usuarios` WHERE `usuarios`.`id` = :user_id;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_STR);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "O usuário foi deletado com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}

	public function listar_usuarios()
	{
		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "SELECT * FROM `usuarios`;";
			$stmt = $PDO->prepare($sql);
			if ($stmt->execute()) {
				$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return ["status" => true, "msg" => "Listando todos usuários", "users" => $res];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}
}