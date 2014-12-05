<?php
	date_default_timezone_set('America/Caracas');
	
	function sendTweet($tituloNoticia, $urlNoticia, $generaSiteMap = 0)
	{
		//generaSiteMap();
		if (puedoTwittear())
			ejecutaCurl('http://65.111.181.110/sendTweetNoticiasVenezolanas.php?titulo=' . urlencode($tituloNoticia) . '&url=' . urlencode($urlNoticia), '', '');
		
	}
	
///////////////////////////////////////////////////////////////////////////////////////////
	function generaSiteMap() {
		$link = conectaBD();
		$query = 'SELECT semaforo FROM generaSiteMap WHERE semaforo = 1';
		$result = mysql_query($query);
		
		if ($result) {
			$queryUpdate = 'UPDATE generaSiteMap SET semaforo = 0 where id = 1';
			$result = mysql_query($queryUpdate);

			EjecutaCurl('http://www.noticiasvenezolanas.com.ve/?sm_command=build&sm_key=fdf833f1d1bb870eb38b03f110c912f6', '', '', 'http://www.noticiasvenezolanas.com.ve'); //Genera el cambio en el sitemap
			EjecutaCurl('http://www.google.com/webmasters/tools/ping?sitemap=http://www.noticiasvenezolanas.com.ve/sitemap.xml.gz', '', '', 'http://www.noticiasvenezolanas.com.ve'); //notifica a google webmaster tool
			EjecutaCurl('http://www.bing.com/webmaster/ping.aspx?sitemap=www.noticiasvenezolanas.com.ve/sitemap.xml.gz', '', '', 'http://www.noticiasvenezolanas.com.ve'); //notifica a bing webmaster tool	
			$queryUpdate = 'UPDATE generaSiteMap SET semaforo = 1 where id = 1';
			$result = mysql_query($queryUpdate);
			
		}
		mysql_close($link);
	}

	function puedoTwittear()
	{
		$horaDelDia = date("H");
		$link = conectaBD();
		
		$query = 'SELECT i_totalTweets FROM totalTweets WHERE i_horaDelDia = \'' . $horaDelDia . '\'';
		
		$result = mysql_query($query);
		if (!$result) 
				die('Invalid query: ' . mysql_error());
		
		$estaNotBD = mysql_fetch_row($result);
		
		if ($estaNotBD[0] > 39) //No se inserta porque ya tiene mas de 35 Tweets en esta hora
		{	
			mysql_close($link);
			return false;
		}
		else
		{
			if(!$estaNotBD[0])
			{
				$queryUpdate = 'UPDATE totalTweets SET i_totalTweets = \'1\',  i_horaDelDia = \'' . $horaDelDia . '\'';
				$result = mysql_query($queryUpdate);
				if (!$result) 
						die('Invalid query: ' . mysql_error());
				mysql_close($link);
			}
			else
			{
				$mas1Tweet = $estaNotBD[0] + 1;
				$queryUpdate = 'UPDATE totalTweets SET i_totalTweets = \'' . $mas1Tweet . '\'';
				$result = mysql_query($queryUpdate);
				if (!$result) 
						die('Invalid query: ' . mysql_error());
				mysql_close($link);
			}
			return true;
		}
		
	}
////////////////////////////////////////////////////////////////////////////////////////////


	
	function noticiaDuplicada($url, $i_idBot)
	{
		$link = conectaBD();		
		
		$query = 'SELECT i_urlNoticiaProcesada FROM urlnoticiasprocesadas WHERE s_urlNoticiaProcesada = \'' . $url . '\' AND i_bot = \'' . $i_idBot . '\'';
		
		$result = mysql_query($query);
		if (!$result) 
				die('Invalid query: ' . mysql_error());
		
		$estaNotBD = mysql_num_rows($result);
		mysql_close($link);
		
		if ($estaNotBD < 1) //Se inserta porque no existe en BD
			return true;
		else
			return false;
	}
	
	function EjecutaCurl($facebookURL, $agentBrowser, $botBrowserCookies, $refererURL = 0, $postFields = 0)
	{
		//$extraHttpHeaders = array("Accept-Language: en-us,en;q=0.5");
		$ch = curl_init(); //inicia el curl  
		curl_setopt($ch, CURLOPT_URL, $facebookURL); //url inicial acargar o pasar parametros GET o POST
		curl_setopt($ch, CURLOPT_USERAGENT, $agentBrowser); //opcion para configuracion de browser user agent
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 12);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHttpHeaders);
		//curl_setopt($ch, CURLOPT_HEADER, 1); //En caso de necesitar ver los headers de respuesta
/*verifica si esta entrando por parametro la variable $postFields en caso de ser true agrega 
las opciones para envio de variables por metodo POST al curl en ejecucion
*/		if($postFields)
		{
			curl_setopt($ch, CURLOPT_POST, 1);  //true vamos a usar metodo POST para envio de variables
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); //variables a ser enviadas por POST
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //devuelve el resultado de la ejecucion del CURL
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //sigue cualquier redirect o 302 - 301 code 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //opcion para paginas SSL
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //opcion para paginas SSl

/*verifica si esta entrando por parametro la variable $refererURL en caso de ser true agrega 
la opciones para envio de referer al header del curl en ejecucion
*/		
		if($refererURL)
		{
			curl_setopt($ch, CURLOPT_REFERER, $refererURL); //configuracion de referer en el http header enviado 
		}
		
		curl_setopt($ch, CURLOPT_COOKIEFILE, $botBrowserCookies); //archivo para guardar las cookies generadas 
		curl_setopt($ch, CURLOPT_COOKIEJAR, $botBrowserCookies); //archivo para guardar las cookies generadas 
		$result = curl_exec ($ch); //ejecuta el curl y guarda el resultado en la variable $result 
		curl_close ($ch); //cierra el curl una vez ejecutado y libera memoria
		return $result; //retorna como resultado la ejecucion del curl archivado en $result
	}
	
	function CargaBrowserUserAgent(){
		srand ((double)microtime()*10000000);
        $browserAgentListFile = file (str_replace('\\','/',dirname(__FILE__)).'/usragts.txt');
        $line = $browserAgentListFile[array_rand ($browserAgentListFile)];
		$line = trim($line);
		$line = str_replace("\t", "", $line);
		$line = str_replace("\n", "", $line);
        return $line;
	}
	
	function decodeHtmlEnt($str) {
		$ret = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
		$p2 = -1;
		for(;;) {
			$p = strpos($ret, '&#', $p2+1);
			if ($p === FALSE)
				break;
			$p2 = strpos($ret, ';', $p);
			if ($p2 === FALSE)
				break;
			   
			if (substr($ret, $p+2, 1) == 'x')
				$char = hexdec(substr($ret, $p+3, $p2-$p-3));
			else
				$char = intval(substr($ret, $p+2, $p2-$p-2));
			   
			//echo "$char\n";
			$newchar = iconv(
				'UCS-4', 'UTF-8',
				chr(($char>>24)&0xFF).chr(($char>>16)&0xFF).chr(($char>>8)&0xFF).chr($char&0xFF)
			);
			//echo "$newchar<$p<$p2<<\n";
			$ret = substr_replace($ret, $newchar, $p, 1+$p2-$p);
			$p2 = $p + strlen($newchar);
		}
		return $ret;
	}
	
	function sanear_string($string)
{

    $string = trim($string);

    $string = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
        $string
    );

    $string = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $string
    );

    $string = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $string
    );

    $string = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $string
    );

    $string = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $string
    );

    $string = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C',),
        $string
    );

    //Esta parte se encarga de eliminar cualquier caracter extraño
    $string = str_replace(
        array("\\", "¨", "º", "-", "~",
             "#", "@", "|", "!", "\"",
             "·", "$", "%", "&", "/",
             "(", ")", "?", "'", "¡",
             "¿", "[", "^", "`", "]",
             "+", "}", "{", "¨", "´",
             ">", "< ", ";", ",", ":",
             ".", " "),
        '-',
        $string
    );


    return $string;
}
	
	function conectaBD(){
		$link = mysql_connect('localhost', 'root', '123');
		if (!$link) 
				die('Could not connect: ' . mysql_error());
		
		
		$db_selected = mysql_select_db('noticia7_comboco', $link);
		if (!$db_selected) 
				die ('Can\'t use the DB noticia7_comboco : ' . mysql_error());
				
		return $link;
	}
	
	
	
	function generateSlug($phrase, $maxLength)
	{
		$result = strtolower($phrase);
		$result = trim(preg_replace("/[\s-]+/", " ", $result));
		$result = trim(substr($result, 0, $maxLength));
		$result = preg_replace("/\s/", "-", $result);
	
		return $result;
	}
	
	function addProcesada($url, $i_idBot)
	{
		$link = conectaBD();
		$queryInsert = 'INSERT INTO urlnoticiasprocesadas(s_urlNoticiaProcesada, i_bot) VALUES (\'' .  $url . '\', \'' .  $i_idBot . '\')';
		
		$result = mysql_query($queryInsert);
		if (!$result) 
				echo('Error insertando: ' . mysql_error());
		
		mysql_close($link);
	}

	function asignaImagenDefault($categoria)
	{
		$resultado = '';
		switch ($categoria) 
		{
			case 1:
				$resultado = 'http://i.imgur.com/rQwsZzs.jpg';
			break;
			case 3:
				$resultado = 'http://i.imgur.com/R5BTQaq.jpg';
			break;
			case 4:
				$resultado = 'http://i.imgur.com/Du5WGKb.jpg';
			break;
			case 5:
				$resultado = 'http://i.imgur.com/yFUzXPL.jpg';
			break;
			case 6:
				$resultado = 'http://i.imgur.com/LXo1TPm.jpg';
			break;
			case 7:
				$resultado = 'http://i.imgur.com/OmA0crP.jpg';
			break;
			case 18:
				$resultado = 'http://i.imgur.com/N8D3oDY.jpg';
			break;
		}
		return $resultado;
	}
	
	function asignaImagenDefaultFile($categoria) 
	{
		$resultado = '';
		switch ($categoria) 
		{
			case 1:
				$resultado = 'rQwsZzs.jpg';
			break;
			case 3:
				$resultado = 'R5BTQaq.jpg';
			break;
			case 4:
				$resultado = 'Du5WGKb.jpg';
			break;
			case 5:
				$resultado = 'yFUzXPL.jpg';
			break;
			case 6:
				$resultado = 'LXo1TPm.jpg';
			break;
			case 7:
				$resultado = 'OmA0crP.jpg';
			break;
			case 18:
				$resultado = 'N8D3oDY.jpg';
			break;
		}
		return $resultado;
	}
	
	function limpiaHtml($tituloNoticia)
	{
		preg_match_all("/(&#[0-9]+;)/", $tituloNoticia, $chars);
		foreach ($chars[0] as $k=>$char) 
		{
			$cambio = mb_convert_encoding($char, "UTF-8", "HTML-ENTITIES");
			$tituloNoticia = str_replace($char, $cambio, $tituloNoticia);
		}
		return $tituloNoticia;
	}
	
	
	
	
	function slug2($phrase)
	{
		$result = strtolower($phrase);
		
		$result = preg_replace("/[^a-z0-9\s-]/", "~", $result);
		$result = trim(preg_replace("/[\s-]+/", " ", $result));
		$result = preg_replace("/\s/", " ", $result);
	
		return $result;
	}
		
	function utf8_uri_encode( $utf8_string, $length = 0 ) {
    $unicode = '';
    $values = array();
    $num_octets = 1;
    $unicode_length = 0;

    $string_length = strlen( $utf8_string );
    for ($i = 0; $i < $string_length; $i++ ) {

        $value = ord( $utf8_string[ $i ] );

        if ( $value < 128 ) {
            if ( $length && ( $unicode_length >= $length ) )
                break;
            $unicode .= chr($value);
            $unicode_length++;
        } else {
            if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

            $values[] = $value;

            if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                break;
            if ( count( $values ) == $num_octets ) {
                if ($num_octets == 3) {
                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                    $unicode_length += 9;
                } else {
                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                    $unicode_length += 6;
                }

                $values = array();
                $num_octets = 1;
            }
        }
    }

    return $unicode;
}

//taken from wordpress
function seems_utf8($str) {
    $length = strlen($str);
    for ($i=0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) $n = 0; # 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
        elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
        else return false; # Does not match any model
        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                return false;
        }
    }
    return true;
}

//function sanitize_title_with_dashes taken from wordpress
function sanitize($title) {
    $title = strip_tags($title);
    // Preserve escaped octets.
    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
    // Remove percent signs that are not part of an octet.
    $title = str_replace('%', '', $title);
    // Restore octets.
    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

    if (seems_utf8($title)) {
        if (function_exists('mb_strtolower')) {
            $title = mb_strtolower($title, 'UTF-8');
        }
        $title = utf8_uri_encode($title, 200);
    }

    $title = strtolower($title);
    $title = preg_replace('/&.+?;/', '', $title); // kill entities
    $title = str_replace('.', '-', $title);
    $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
    $title = preg_replace('/\s+/', '-', $title);
    $title = preg_replace('|-+|', '-', $title);
    $title = trim($title, '-');

    return $title;
}
?>
