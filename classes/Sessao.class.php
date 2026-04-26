<?php  
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */

/**
 * Sessões do usuário
 */
class Sessao
{
	
	public function verificar_sessao()
	{
		if (isset($_SESSION['admin_id']) && $_SESSION['admin_logged']) {
			return true;
		}elseif (isset($_SESSION['admin_id']) && !$_SESSION['admin_logged']) {
			return false;
		}else{
			return false;
		}
	}


	public function get_user_id()
	{
		return intval($_SESSION['admin_id']);
	}
	
}