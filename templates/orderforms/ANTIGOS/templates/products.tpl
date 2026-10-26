<link rel="stylesheet" type="text/css" href="templates/orderforms/{$carttpl}/style.css" />
{literal}
<style>
.tih-templates{
	width:205px;
	float:left;
}
.elevadasImgs:hover {
-webkit-transform: scale(4);
-moz-transform: scale(4);
-o-transform: scale(4);
transform: scale(4);
-webkit-transform-origin: center ;
-moz-transform-origin: center ;
-o-transform-origin: center ;
transform-origin: center ;

}
.elevadasImgs{
border-radius: 3px 3px 3px 3px;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 8px rgba(82, 168, 236, 0.6);
-moz-transition: all 0.5s ease-in;
-o-transition: all 0.5s ease-in;
-webkit-transition: all 0.5s ease-in;
-ms-transition: all 0.5s ease-in;
transition: all 0.5s ease-in;
max-width: 170px;
max-height: 190px;
}
.tih-templates-precos{
	text-align:center;
}
.tih-templates-precos strong{
	font-size:15px;
	vertical-align:top;
	color:#BF0101;
}

.tih-templates-precos span{
	line-height:90px;
	font-size:25px;
	color:#666;
	font-weight:bold;
}
.bodyRight{
	display:none;
}
.blankBoxContent{
width:915px;
background-color:#FFF;
}
#order-verticalsteps .maincontent{
	margin:0 auto;
}
#order-verticalsteps .maincontent .orderbox {
	  margin: 15px 7px;
	  float: left;
	  width: 420px;
	  height: 230px;
	  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 8px rgba(191, 1, 1, 1);
	  background-color: transparent;
}
	  
#order-verticalsteps .maincontent table tr td {
  padding: 0;
 
}

.thumbsTi{
	width: 30px;
	height: 205px;
	float: left;
	z-index:-999;
	cursor:pointer;
}
.thumbsTi img{
	max-height:25px !important;
	max-width:25px !important;
	float:left;
	margin:2px;
	clear:none;
	z-index:-999;
	
}
.screenshots{
	float: left;
	width: 200px;
	height: 230px;
}
.imagensTemplates{
	float: left;
	width: 30px;
	height: 200px;
}
.imagensTemplates img{
	max-height:25px !important;
	max-width:25px !important;
	float:left;
	margin:2px;
	clear:none;
	z-index:-999;
}
.imagemTemplates{
	float: left;
	width: 200px;
	height: 200px;
	text-align:center;
}
.imagemTemplates a{
	font-size:18px;
	color:#F00;
	line-height:30px;
	font-weight: bold;
}
.imagemTemplates div{
	height:200px;
}
.botoesTemplates{
	float: right;
	width: 210px;
	height: 175px;
}
.descricao{
	height:170px;
	width:100%;
	float:left;
	overflow: hidden;
	padding-bottom: 5px;
}
.descricao h1{
	font-size: 15px !important;
	color: #BF0101;
	text-align: center;
 }
.botoes span{
	font-size: 30px;
	color: #BF0101;
	text-shadow: 0px 0px 6px rgba(191, 1, 1, 0.22);
	font-weight: 600;
}
.botoes{
	height:50px;
	width:100%;
	float:left;
	text-align: center;
}
.botoes input[type=submit]
{
	margin-top:10px;
}
.ver_mais{
	margin: 5px 0 0 -10px;
  border-bottom-left-radius: 5px;
}
.headingBlank:before{
    content: "Inclua um de nossos sites prontos e completos em seu ";
}
.comprar{
  margin: 10px;
 }
</style>
{/literal}

<div id="order-verticalsteps">



<div class="maincontent">

{php}
	$command = "getproducts";
	$adminuser = "deljdl";
	$values["gid"] = 5;
 
function url_amigavel($string)
{
    
    $url_limpa=tiracento($string);
    
     $proibidos=array('/','&','%','(','?','.','\\','#',')','°' );
    
     $url_limpa=str_replace($proibidos,'',$url_limpa);



	$url_limpa = preg_replace("/[^a-zA-Z0-9][-]/", "-",$url_limpa);




	return $url_limpa;
}

function tiracento($texto)
{
        $trocarIsso = array('à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ü','ú','ÿ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ñ','Ò','Ó','Ô','Õ','Ö','O','Ù','Ü','Ú','Ÿ',);
        $porIsso = array('a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','y','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','N','O','O','O','O','O','o','U','U','U','Y',);
        $titletext = str_replace($trocarIsso, $porIsso, $texto);
        return $titletext;
}

 
 $result = localAPI($command,$values,$adminuser);

 foreach ($result['products'] as $prods)
 foreach ($prods as $prod)
 {
		echo
		'
			<div class="orderbox">
				<div class="screenshots">
					<div class="imagemTemplates">
						<div>
							<img class="elevadasImgs"  title="Template: '.$prod['name'].'" src="imagens/templates/getcapa.php?template='.url_amigavel($prod['name']).'" />
						</div>
						<a href="template/'.url_amigavel($prod['name']).'/'.$prod['pid'].'"><img class="ver_mais" src="imagens/templates/ver_mais.png" /></a>
					</div>
				</div>
				<div class="botoesTemplates">
					<div class="descricao">
						<h1>'.$prod['name'].'</h1>
						<div>
							'.$prod['description'].'
						</div>
					</div>
					<div class="botoes">
						<span> R$ 29.99
						</span>
						<a href="cart.php?a=add&pid='.$prod['pid'].'"><img class="comprar" src="imagens/templates/quero_comprar_bg.png" /></a>
					</div>
				</div>
			</div>
		
		';
}
{/php}




<div style="float:left"><img align="left" src="images/padlock.gif" border="0" alt="Secure Transaction" style="padding-right: 10px;" /> {$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}</div>

</div>

<div class="clear"></div>

</div>