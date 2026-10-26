<?php 

function url_amigavel($string){
    
    $url_limpa=tiracento($string);
    
     $proibidos=array('/','&','%','(','?','.','\\','#',')','°' );
    
     $url_limpa=str_replace($proibidos,'',$url_limpa);



	$url_limpa = preg_replace("/[^a-zA-Z0-9 ]/", "-",$url_limpa);


    
    
	$url_limpa= strtolower( strip_tags( preg_replace( array( '/[`^~\'"]/', '/([\s]{1,})/', '/[-]{2,}/' ), array( null, '-', '-' ), iconv( 'UTF-8', 'ASCII//TRANSLIT', $url_limpa ) ) ) );



return $url_limpa;
}

function tiracento($texto){
        $trocarIsso = array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','O','Ù','Ü','Ú','Ÿ',);
        $porIsso = array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','o','U','U','U','Y',);
        $titletext = str_replace($trocarIsso, $porIsso, $texto);
        return $titletext;}

//echo ($_GET['template']);
function renderizaImagem($capa,$ext)
{
	$capa = $capa.'.'.$ext;
	if(! file_exists($capa))
		return;
		
		
	header('Content-Description: File Transfer');
	header('Content-type: image/'.$ext);
	header('Content-Disposition: attachment; filename='.basename($capa));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($capa));
	ob_clean();
	flush();
	readfile($capa);
	die(file_get_contents($capa));

}



$imagem = url_amigavel($_GET['template']);

if(isset($_GET['thumb']))
	$imagem.=(int)$_GET['thumb'];

$capa = '/home/tihosped/downloads/'.$imagem;
 


	if(isset($_GET['debug']))
			die($imagem.'.png e '.$imagem.'.jpg. Não enviadas');
	renderizaImagem($capa,'jpg');
	renderizaImagem($capa,'png');
			
	renderizaImagem('/home/tihosped/downloads/no-foto','jpg'); 		

 	
?>