#!/usr/bin/php
<?php
set_time_limit (90);

    include_once('funcionesCliente.php');
    $agentBrowser = CargaBrowserUserAgent(); //funcion carga un browser user agent valido
    $URL = 'http://www.noticiascaracol.com/';
    $BOT_BROWSER_COOKIES = dirname(__FILE__).'/BotCaracol.txt';
    for ($w=1; $w<7; $w++)
    {
        if ($w > 5)
            exit ('No pude cargar la URL : ' . $URL . ' puede ser un cambio en el diseño o errores de servidor ' . $htmlResultPage);
            
        $htmlResultPage = EjecutaCurl($URL, $agentBrowser, $BOT_BROWSER_COOKIES, 'http://www.noticiascaracol.com/');
        
        $pattern = '/data\-rel\=\"publisher\"\>\<\/div\>(.*?)\<div class\=\"region region\-b\"\>/si';
        preg_match($pattern, $htmlResultPage, $noticias);
        
        if ($noticias)
            break;
    }
    
    $patron = '/(?<=\<h2 class\=\"note\"\>\<a href\=\")[^\"]+/si';
    preg_match_all($patron, $noticias[0], $urlNoticias);
            
    $cont = 1;
    $max = 4;

    foreach ($urlNoticias[0] as $k=>$urlNoticia) {
        if ($cont > $max)
            break;
        $urlNoticia = 'http://www.noticiascaracol.com' . $urlNoticia;
        
        if (noticiaDuplicada($urlNoticia, 19))
        {
            agregaNoticia($urlNoticia, '', $agentBrowser, $BOT_BROWSER_COOKIES, $URL);
            // break;
        }
        
        $cont ++;
    }

    function agregaNoticia($urlNoticia, $categoriaNoticia, $agentBrowser, $BOT_BROWSER_COOKIES, $URL)
    {
        for ($w=1; $w<7; $w++)
        {
            if ($w > 5)
                exit ('No pude cargar la URL : ' . $urlNoticia . ' puede ser un cambio en el diseño o errores de servidor ' . $htmlResultPage);
    
            $htmlResultPage = EjecutaCurl($urlNoticia, $agentBrowser, $BOT_BROWSER_COOKIES, $URL);
            $patron = '/(?<=\<title\>)[^\<]+/';
            preg_match($patron, $htmlResultPage, $tituloNoticia);
            if ($tituloNoticia)
                break;
        }
        
        $patron = '/\<div class\=\"body\"\>(.*?)\<h2 class\=\"field\-label\"\>/si';
        preg_match($patron, $htmlResultPage, $contenidoNoticia);

        if (!$contenidoNoticia)
            exit;
        
        $fechaNoticia = date("Y-m-d");
        $horaNoticia = date("h:i A");
        $horaNoticiaGmt = date("H:i:s");
        
        $patron = '/class\=\"active\"\>(.*?)\<\/a\>/si';
        preg_match($patron, $htmlResultPage, $categoriaNoticia);

        $categoriaNoticia = asignaCategoria($categoriaNoticia[1]);
        
        //$categoriaNoticia = 1;
        
        $tituloNoticia = str_replace('| Noticias Caracol', '', trim(htmlspecialchars_decode($tituloNoticia[0])));
        
        $nombFile = preg_replace('/[^a-z0-9 _-]/', '', sanitize(sanear_string(limpiaHtml($tituloNoticia))));
        
        // $patron = '/contenidoVnota\" class\=\"txtVitrina\"\>(.*?)\<div id\=\"clearposition\"/si';
        // preg_match($patron, $htmlResultPage, $contenidoNoticia2);
        
        $patron = '/Internal Server/si';
        preg_match($patron, $tituloNoticia, $chequeo);
        
        if ($chequeo)
            exit;
        
        // if ($contenidoNoticia2)
        // $contenidoNoticia2 = strip_tags($contenidoNoticia2[1], '<br></br></ br><iframe></iframe><p></p><object></object><param></param>');
        $contenidoNoticia = strip_tags($contenidoNoticia[1], '<br></br></ br><iframe></iframe><p></p><object></object><param></param>');
        
        // if($contenidoNoticia2)
        //  $contenidoNoticia = $contenidoNoticia . ' ' . $contenidoNoticia2[0];
        
        if((strlen($contenidoNoticia)<25) || (strpos($contenidoNoticia, 'sexo') !== false) || (strpos($contenidoNoticia, 'sexual') !== false) || (strpos($contenidoNoticia, 'aborto') !== false))
            exit;

        
        $pattern = '/\<div class\=\"image\"\>(.*?)\<\/div\>/si';
        preg_match($pattern, $htmlResultPage, $imagenes);
        
        if($imagenes){  
            $pattern = '/(?<=\<span data\-src\=\")[^\"]+/';
            preg_match($pattern, $imagenes[0], $imagenes);
        }

        
        if ($imagenes) {
            $fecha = date("dmY");
            $rutArchivo = getcwd();
            
            $img = $rutArchivo . '/' .  $nombFile . '.jpg';
            
             $ch = curl_init($imagenes[0]);
             $fp = fopen($img, 'wb');
             curl_setopt($ch, CURLOPT_FILE, $fp);
             //curl_setopt($ch, CURLOPT_HEADER, 0);
             curl_exec($ch);
             curl_close($ch);
             fclose($fp);
                
            $imagenNoticia = getimagesize($img);
            
            if ($imagenNoticia)
            {
                 //UPLOAD IMAGE TO IMGUR
                 $client_id = '60006297dbb03d4';
                 $image = file_get_contents($img); 
                 $ch = curl_init(); 
                 curl_setopt($ch, CURLOPT_URL, 'https://api.imgur.com/3/image.json'); 
                 curl_setopt($ch, CURLOPT_POST, TRUE); 
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
                 curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Authorization: Client-ID ' . $client_id )); 
                 curl_setopt($ch, CURLOPT_POSTFIELDS, array( 'image' => base64_encode($image) )); 
                 $reply = curl_exec($ch); 
                 curl_close($ch); 
                 $reply = json_decode($reply);
                 $imgURL = $reply->data->link;
            }
            
        }

        else {
            $imgURL = asignaImagenDefault($categoriaNoticia);
            $img = asignaImagenDefaultFile($categoriaNoticia);
            $rutArchivo = '';
        }
        
        if ($imgURL)
            $contenidoNoticia = '<div class="post-image"><img src="' . $imgURL . '" class="attachment-post-thumb wp-post-image" alt="' . $tituloNoticia . '"/></div> ' . $contenidoNoticia;
    
        $data = array(
           "urlNoticia" => urlencode(utf8_decode($urlNoticia)),
           "tituloNoticia" => urlencode(utf8_decode($tituloNoticia)),
           "contenidoNoticia" => urlencode($contenidoNoticia),
           "categoriaNoticia" => $categoriaNoticia,
           "fechaNoticia" => $fechaNoticia,
           "horaNoticia" => $horaNoticia,
           "nombFile" => $nombFile,
           "horaNoticiaGmt" => $horaNoticiaGmt,
           "imgURL" => $imgURL,
           "img" => $img,
           "rutArchivo" => $rutArchivo,
           "botId" => "19"
        );

        var_dump($data);
        exit;
        
        $fields = '';
        foreach($data as $key => $value) {
            $fields .= $key . '=' . $value . '&'; 
        }
        rtrim($fields, '&');
        /*
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, 'http://noticiasvenezolanas.co.ve/phpBots/botServer.php'); 
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $idNoticia = curl_exec($ch); 
        curl_close($ch);
        
        if ($idNoticia)
        {
            addProcesada($urlNoticia, 19);
            
            if($rutArchivo)
                unlink($img);
            
            $urlNoticia = 'http://noticiasvenezolanas.co.ve/index.php/' . $idNoticia . '/' . $nombFile . '/';
            sendTweet($tituloNoticia, $urlNoticia);
            //poster($tituloNoticia, $contenidoNoticia, $urlNoticia, $img);
        }*/
    }
    
    function asignaCategoria($stringCategoria)
    {
        $patron = '/NACIÓN|NACION|POL&Iacute;TICA|La naci|LA NACI|La Naci|Editorial|Opini|Sucesos|SUCESOS|OPINION|Educaci|EDUCACI|En Campa|EN CAMPA|Ambiente|AMBIENTE|Poder|Popular|Poder Popular|Política|Politica|Gesti|Social|Gestión|Regiones|Region|bogot&Aacute;|COLOMBIA/';
        preg_match($patron, $stringCategoria, $categoria);
        if($categoria)
            $categoria = '1';
        else 
        {
            $patron = '/econom|Econom|ECONOM/';
            preg_match($patron, $stringCategoria, $categoria);
            if($categoria)
                $categoria = '3';
            else
            {
                $patron = '/EUROPA|internacional|mundo|Mundo|MUNDO/';
                preg_match($patron, $stringCategoria, $categoria);
                if($categoria)
                    $categoria = '4';
                else
                {
                    $patron = '/deportes|Deportes|DEPORTES/';
                    preg_match($patron, $stringCategoria, $categoria);
                    if($categoria)
                        $categoria = '5';
                    else
                    {
                        $patron = '/TECN&Oacute;SFERA|TECNOLOG|Tecnolog|tecnolog/';
                        preg_match($patron, $stringCategoria, $categoria);
                        if($categoria)
                            $categoria = '7';
                        else
                        {
                            $patron = '/VIDA|vida|SALUD|Salud|salud/';
                            preg_match($patron, $stringCategoria, $categoria);
                            if($categoria)
                                $categoria = '6';
                            else
                                $categoria = '18';
                        }
                    }
                }
            }
        }
        return $categoria;
    }
    
    function poster($tituloNoticia, $contenidoNoticia, $s_urlNoticia, $img)
    {
        $headers = "MIME-Version: 1.0\n" ;
        $headers .= "Content-Type: text/html; charset=\"UTF-8\"\n";
        $headers .= 'From: noticierosdevenezuela@hotmail.com' . "\r\n" .
        'Reply-To: noticierosdevenezuela@hotmail.com' . "\r\n";

        $contenidoNoticia = '<i>Fuente de la noticia: <a href="' . $s_urlNoticia . '">Noticias de Venezuela</a></i> <br /> ' . $contenidoNoticia . ' <br /><i>Fuente de la noticia: <a href="' . $s_urlNoticia . '" title="Noticias de Venezuela">Noticias de Venezuela</a></i>';
        mail('fime555deju@post.wordpress.com', $tituloNoticia, $contenidoNoticia, $headers);
    }
?>
