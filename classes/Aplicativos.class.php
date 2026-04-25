<?php

/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */

/**
 * Classe de aplicativos
 */
class Aplicativos extends Usuarios
{
	public function buscar_apps()
	{

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "SELECT `app`.*, `bann`.*, `av`.`texto` AS `aviso` FROM `aplicativos` AS `app` LEFT JOIN `banners` AS `bann` ON `bann`.`app_id` = `app`.`id` LEFT JOIN `avisos` AS `av` ON `av`.`app_id` = `app`.`id` WHERE `app`.`user_id` = 1;";
			$stmt = $PDO->prepare($sql);
			if ($stmt->execute()) {
				$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if (!empty($res)) {
					return ["status" => true, "msg" => "Listando todos os aplicativos", "app_info" => $res];
				}
				return ["status" => false, "msg" => "Não encontramos nenhum aplicativo"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function buscar_apps_por_id($app_id)
	{
		$user = $this->info_user_logado();

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "SELECT `app`.*, `bann`.*, `av`.* FROM `aplicativos` AS `app` LEFT JOIN `banners` AS `bann` ON `bann`.`app_id` = `app`.`id` LEFT JOIN `avisos` AS `av` ON `av`.`app_id` = `app`.`id` WHERE `app`.`user_id` = :user_id AND `app`.`id` = :app_id;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":user_id", $user["user_info"]["id"], PDO::PARAM_INT);
			$stmt->bindParam(":app_id", $app_id, PDO::PARAM_INT);
			if ($stmt->execute()) {
				$res = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!empty($res)) {
					return ["status" => true, "msg" => "Listando todos os aplicativos", "app_info" => $res];
				}
				return ["status" => false, "msg" => "Não encontramos nenhum aplicativo"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function update_aplicativo($array, $app_id)
	{

		$user = $this->info_user_logado();
		
		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {

			if ($user["user_info"]["nivel"] == 1) {
				$sql = "UPDATE `aplicativos` SET `nome` = :nome_app, `binstream_email` = :bin_email, `binstream_psp` = :bin_psp, `binstream_auth` = :bin_auth, `binstream_register` = :bin_register, `app_img_logotipo` = :app_logotipo, `app_img_sidebar` = :app_sidebar, `app_img_background` = :app_background, `app_img_favoritos` = :app_favoritos, `app_msg_suporte` = :app_whatsapp, `app_cor_esquerdo` = :cor_esquerda, `app_cor_centro` = :cor_centro, `app_cor_direito` = :cor_direita WHERE `aplicativos`.`id` = :app_id AND `aplicativos`.`user_id` = :user_id ;";
			}else{
				$sql = "UPDATE `aplicativos` SET `nome` = :nome_app, `app_img_logotipo` = :app_logotipo, `app_img_sidebar` = :app_sidebar, `app_img_background` = :app_background, `app_img_favoritos` = :app_favoritos, `app_msg_suporte` = :app_whatsapp, `app_cor_esquerdo` = :cor_esquerda, `app_cor_centro` = :cor_centro, `app_cor_direito` = :cor_direita WHERE `aplicativos`.`id` = :app_id ;";
			}

			


			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":nome_app", $array["configuracao"][0], PDO::PARAM_STR, 255);

			if ($user["user_info"]["nivel"] == 1) {
				$stmt->bindParam(":bin_email", $array["conexao"][1], PDO::PARAM_STR, 255);
				$stmt->bindParam(":bin_psp", $array["conexao"][0], PDO::PARAM_STR, 255);
				$stmt->bindParam(":bin_auth", $array["conexao"][2], PDO::PARAM_STR, 255);
				$stmt->bindParam(":bin_register", $array["conexao"][3], PDO::PARAM_STR, 255);
			}
			$stmt->bindParam(":app_logotipo", $array["configuracao"][1], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_sidebar", $array["configuracao"][2], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_background", $array["configuracao"][3], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_favoritos", $array["configuracao"][4], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_whatsapp", $array["configuracao"][5], PDO::PARAM_STR, 255);
			$stmt->bindParam(":cor_esquerda", $array["cores"]["esquerdo"], PDO::PARAM_STR, 255);
			$stmt->bindParam(":cor_centro", $array["cores"]["centro"], PDO::PARAM_STR, 255);
			$stmt->bindParam(":cor_direita", $array["cores"]["direita"], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_id", $app_id, PDO::PARAM_INT);
			if ($user["user_info"]["nivel"] == 1) {
				$stmt->bindParam(":user_id", $user["user_info"]["id"], PDO::PARAM_INT);
			}
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "Aplicativo atualizado com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function update_banners($array, $app_id)
	{

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "UPDATE `banners` SET `nome1` = :nome1, `url1` = :url1, `tipo1` = :tipo1, `status1` = :status1, `nome2` = :nome2, `url2` = :url2, `tipo2` = :tipo2, `status2` = :status2, `nome3` = :nome3, `url3` = :url3, `tipo3` = :tipo3, `status3` = :status3, `nome4` = :nome4, `url4` = :url4, `tipo4` = :tipo4, `status4` = :status4 WHERE `banners`.`app_id` = :app_id ;";


			$stmt = $PDO->prepare($sql);

			$stmt->bindParam(":nome1", $array["banners"]["banner1"][0], PDO::PARAM_STR, 255);
			$stmt->bindParam(":url1", $array["banners"]["banner1"][1], PDO::PARAM_STR, 255);
			$stmt->bindParam(":tipo1", $array["banners"]["banner1"][2], PDO::PARAM_INT);
			$stmt->bindParam(":status1", $array["banners"]["banner1"][3], PDO::PARAM_INT);

		


			$stmt->bindParam(":nome2", $array["banners"]["banner2"][0], PDO::PARAM_STR, 255);
			$stmt->bindParam(":url2", $array["banners"]["banner2"][1], PDO::PARAM_STR, 255);
			$stmt->bindParam(":tipo2", $array["banners"]["banner2"][2], PDO::PARAM_INT);
			$stmt->bindParam(":status2", $array["banners"]["banner2"][3], PDO::PARAM_INT);

		


			$stmt->bindParam(":nome3", $array["banners"]["banner3"][0], PDO::PARAM_STR, 255);
			$stmt->bindParam(":url3", $array["banners"]["banner3"][1], PDO::PARAM_STR, 255);
			$stmt->bindParam(":tipo3", $array["banners"]["banner3"][2], PDO::PARAM_INT);
			$stmt->bindParam(":status3", $array["banners"]["banner3"][3], PDO::PARAM_INT);

		


			$stmt->bindParam(":nome4", $array["banners"]["banner4"][0], PDO::PARAM_STR, 255);
			$stmt->bindParam(":url4", $array["banners"]["banner4"][1], PDO::PARAM_STR, 255);
			$stmt->bindParam(":tipo4", $array["banners"]["banner4"][2], PDO::PARAM_INT);
			$stmt->bindParam(":status4", $array["banners"]["banner4"][3], PDO::PARAM_INT);

		



			
			$stmt->bindParam(":app_id", $app_id, PDO::PARAM_INT);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "Banners atualizados com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}


	public function update_avisos($array, $app_id)
	{

		$PDO = conexaoPDOlocal();
		if ($PDO !== NULL) {
			$sql = "UPDATE `avisos` SET `texto` = :aviso WHERE `avisos`.`app_id` = :app_id ;";
			$stmt = $PDO->prepare($sql);
			$stmt->bindParam(":aviso", $array["aviso"], PDO::PARAM_STR, 255);
			$stmt->bindParam(":app_id", $app_id, PDO::PARAM_INT);
			if ($stmt->execute()) {
				return ["status" => true, "msg" => "Aviso atualizado com sucesso!"];
			}
		}
		return ["status" => false, "msg" => "Houve um erro interno - CODIGO: 500"];
	}

	public function editar_aplicativo($array, $app_id)
	{

		$update_app = $this->update_aplicativo($array, $app_id);
		$update_banner = $this->update_banners($array, $app_id);
		$update_aviso = $this->update_avisos($array, $app_id);


		if ($update_app) {
			if ($update_banner) {
				if ($update_aviso) {
					return ["status" => true, "msg" => "Informações atualizadas com sucesso!"];
				}else{
					return $update_aviso;
				}
			}else{
				return $update_banner;
			}
		}else{
			return $update_app;
		}
	}
}