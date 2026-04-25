<?php

/**
 * Classe de criptografia
 */
class Encode
{
	
	public function criptografar($array){


		$array = (object)$array;
		$string = stripslashes(json_encode($array));


		$substituicao = array(
			'%' => 'cp',
			'+' => 'im',
			'a' => 'ze',
			'b' => 'IG',
			'c' => 'gp',
			'd' => 'tS',
			'e' => 'tK',
			'f' => 'eD',
			'g' => 'wG',
			'h' => 'Hd',
			'i' => 'Ta',
			'j' => 'Mz',
			'k' => 'NV',
			'l' => 'zy',
			'm' => 'Xg',
			'n' => 'ZP',
			'o' => 'Jw',
			'p' => 'Ab',
			'q' => 'vS',
			'r' => 'mo',
			's' => 'jR',
			't' => 'Em',
			'u' => 'Dr',
			'v' => 'lr',
			'w' => 'cW',
			'x' => 'JW',
			'y' => 'yw',
			'z' => 'yL',
			'"' => 'Ns',
			'\'' => 'gh',
			'{' => 'ia',
			'}' => 'rW',
			'[' => 'fw',
			']' => 'Wi',
			':' => 'Nu',
			',' => 'nX',
			'=' => 'ml',
			'0' => 'kf',
			'1' => 'AB',
			'2' => 'tz',
			'3' => 'tC',
			'4' => 'fc',
			'5' => 'Et',
			'6' => 'BH',
			'7' => 'pC',
			'8' => 'Yx',
			'9' => 'vv',
			' ' => 'Zt',
			'!' => 'Rl',
			'?' => 'Pk',
			'.' => 'pV',
			'/' => 'Er',
			'\\' =>'gg',
			';' => 'fV',
			'&' => 'xZ',
			'@' => 'ms',
			'-' => 'jj',
			'_' => 'Ik',
			'#' => 'rj',
			'A' => 'jS',
			'B' => 'rR',
			'C' => 'MK',
			'D' => 'Kd',
			'E' => 'SU',
			'F' => 'gN',
			'G' => 'BX',
			'H' => 'LR',
			'I' => 'Vd',
			'J' => 'BA',
			'K' => 'QL',
			'L' => 'Ma',
			'M' => 'Nw',
			'N' => 'RV',
			'O' => 'VM',
			'P' => 'ba',
			'Q' => 'BD',
			'R' => 'rO',
			'S' => 'Rw',
			'T' => 'qI',
			'U' => 'Ty',
			'V' => 'fu',
			'W' => 'VS',
			'X' => 'fd',
			'Y' => 'Uf',
			'Z' => 'mn',
		);
		$api = strtr($string, $substituicao);
		return $api;
	}

	public function criptografar_dominio($string){

		$string = stripslashes($string);

		$substituicao = array(
			'a' => 'ze',
			'b' => 'IG',
			'c' => 'gp',
			'd' => 'tS',
			'e' => 'tK',
			'f' => 'eD',
			'g' => 'wG',
			'h' => 'Hd',
			'i' => 'Ta',
			'j' => 'Mz',
			'k' => 'NV',
			'l' => 'zy',
			'm' => 'Xg',
			'n' => 'ZP',
			'o' => 'Jw',
			'p' => 'Ab',
			'q' => 'vS',
			'r' => 'mo',
			's' => 'jR',
			't' => 'Em',
			'u' => 'Dr',
			'v' => 'lr',
			'w' => 'cW',
			'x' => 'JW',
			'y' => 'yw',
			'z' => 'yL',
			':' => 'Ns',
			'/' => 'gh',
			'.' => 'ia'
		);
		$dom = strtr($string, $substituicao);
		return $dom;
	}
}