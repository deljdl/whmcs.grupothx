{literal}
<style>
.bodyRight{
	display:none;
}
.blankBoxContent{
	width:915px;
	background-color:#FFF;
}
.imgLimits{
	max-width:500px;
	max-height:500px;
	margin: 15px auto;
}

.tpl{
	width:637px;
	height:auto;
	margin:0 auto;
	display:table;
	
}
.nome_template{
	max-width:470px;
	float:left;
	border:solid 1px #bf0c0c;
	height:15px;
	background-color:#bf0c0c;
	border-radius: 10px 10px 0 0;
	-webkit-box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
	-moz-box-shadow:    12px 2px 20px 0px rgba(50, 50, 50, 1);
	box-shadow:         12px 2px 20px 0px rgba(50, 50, 50, 1);
	color:#FFF;
	font-size:27px;
	font-family:Myriad Pro;
	padding:15px 5px;
	margin: 0 0 0 10px;
	overflow:hidden;
	white-space: nowrap;	
}
.engloba_template{
	width:915px;
	height:auto;
	float:left;
	border:solid #bf0c0c 4px;
	border-radius:10px;
	background-color:#f0e7e7;	
}
.miniaturas{
	width:100px;
	height:100%;
	float:left;
	border-radius:10px;
}
.hvr-wobble-top{
	width:64px;
	height:70px;
	float:left;
	border:solid #bf0c0c 2px;
	margin:10px 0px 0px 16px;
	border-radius:5px;
	cursor:pointer;
	 text-align: center;
}


/* Wobble Top */
@-webkit-keyframes hvr-wobble-top {
  16.65% {
    -webkit-transform: skew(-12deg);
    transform: skew(-12deg);
  }

  33.3% {
    -webkit-transform: skew(10deg);
    transform: skew(10deg);
  }

  49.95% {
    -webkit-transform: skew(-6deg);
    transform: skew(-6deg);
  }

  66.6% {
    -webkit-transform: skew(4deg);
    transform: skew(4deg);
  }

  83.25% {
    -webkit-transform: skew(-2deg);
    transform: skew(-2deg);
  }

  100% {
    -webkit-transform: skew(0);
    transform: skew(0);
  }
}

@keyframes hvr-wobble-top {
  16.65% {
    -webkit-transform: skew(-12deg);
    transform: skew(-12deg);
  }

  33.3% {
    -webkit-transform: skew(10deg);
    transform: skew(10deg);
  }

  49.95% {
    -webkit-transform: skew(-6deg);
    transform: skew(-6deg);
  }

  66.6% {
    -webkit-transform: skew(4deg);
    transform: skew(4deg);
  }

  83.25% {
    -webkit-transform: skew(-2deg);
    transform: skew(-2deg);
  }

  100% {
    -webkit-transform: skew(0);
    transform: skew(0);
  }
}

.hvr-wobble-top {
  display: inline-block;
  vertical-align: middle;
  -webkit-transform: translateZ(0);
  transform: translateZ(0);
  box-shadow: 0 0 1px rgba(0, 0, 0, 0);
  -webkit-backface-visibility: hidden;
  backface-visibility: hidden;
  -moz-osx-font-smoothing: grayscale;
  -webkit-transform-origin: 0 100%;
  transform-origin: 0 100%;
}
.hvr-wobble-top:hover, .hvr-wobble-top:focus, .hvr-wobble-top:active {
  -webkit-animation-name: hvr-wobble-top;
  animation-name: hvr-wobble-top;
  -webkit-animation-duration: 1s;
  animation-duration: 1s;
  -webkit-animation-timing-function: ease-in-out;
  animation-timing-function: ease-in-out;
  -webkit-animation-iteration-count: 1;
  animation-iteration-count: 1;
}
.hvr-wobble-top img{
	border-radius:3px;
	max-width:59px;
	max-height:64px;

}

.foto-grande{
	width:783px;
	height:570px;
	text-align:center;
	float:left;
	border:solid #bf0c0c 4px;
	margin:10px 0 0 17px;
	position:relative;
	display:table;
	border-radius:10px 0 10px 0;
	background-color:#FFF;
}
.foto-grande img{
	display:table-cell;
	border-radius:10px;
	max-width:498px;
	max-height:527px;
	
}
.aba_descricao{
	width:785px;
	height:40px;
	float:left;
	margin:-35px 0 0 0;
	position:absolute;
}
.aba_emsi{
	width:100px;
	height:20px;
	float:right;
	border:solid #bf0c0c 1px;
	background-color:#bf0c0c;
	border-radius: 10px 10px 0 0;
	color:#FFF;
	font-size:20px;
	font-family:Myriad Pro;
	padding:5px 5px;
	cursor:pointer;  
	margin:0; 
	line-height: 25px;
	
}
.descricao_template{
	width:100%;
	float:left;
	height:auto;
	background-color:rgba(191, 12, 12, 1);
	font-size:15px;
	font-family:Myriad Pro;
	padding:10px 0;
	color:#FFF;
	position: absolute;
	top: 0;
	text-align:center;
	display:none;
	z-index:9999999;
		
}
#foto_destaque{
	margin:0 auto;
	display:table;	
	
}
.botoes{
	width:100%;
	float:left;
	height:50px;	
}
.preco{
	width: 110px;
  float: left;
  border: solid 1px #bf0c0c;
  height: 30px;
  background-color: #bf0c0c;
  border-radius: 0 0 10px 10px;
  -webkit-box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  -moz-box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  color: #FFF;
  font-size: 27px;
  font-family: Myriad Pro;
  padding: 5px 5px;
  margin: 0 0 0 117px;
  line-height: 35px;
  text-align: center;	
}
.comprar{
	max-width:470px;
	float:left;
	border:solid 1px #000;
	height:30px;
	background-color:#000;
	border-radius:0 0 10px 10px;
	padding:5px 5px;
	margin: 0 0 0 10px;	
}
.comprar a{
	color:#FFF;
	font-size:27px;
	font-family:Myriad Pro;
	text-decoration:none;
	line-height: 35px;

}
.comprar a img{
	  border: none;
  float: left;
  margin: 0 10px 0 0;
 }
 
.ver_mais{
  float: right;
  border: solid 1px #bf0c0c;
  height: 30px;
  background-color: #bf0c0c;
  border-radius: 0 0 10px 10px;
  -webkit-box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  -moz-box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  box-shadow: 12px 2px 20px 0px rgba(50, 50, 50, 1);
  color: #FFF;
  font-size: 27px;
  font-family: Myriad Pro;
  padding: 5px 5px;
  margin: 0 15px 0 0;
  line-height: 35px;
  text-align: center;	
}
.ver_mais a{
	color:#FFF;
}

.carrinho{
  float: left;
  border: solid 1px #bf0c0c;
  height: 30px;
  background-color: #bf0c0c;
  border-radius: 0 0 10px 10px;
  color: #FFF;
  font-size: 27px;
  font-family: Myriad Pro;
  padding: 0px 5px 9px 5px;
  margin: 0 0 0 10px;
  color: #FFF;
  width: 40px;
  white-space: nowrap;
  overflow: hidden;
}
.carrinho_aberto()
{
	width: 370px;
}
.redessociais{
	width: 391px;
  height: auto;
  float: left;
  color: #bf0c0c;
  margin: 11px 0 0 41px;
  font-family: Myriad Pro;
		
}
.redessociais img{margin: 0 10px;}
.hvr-wobble-top table{
	width:100%;
	border-collapse:collapse;
}
.hvr-wobble-top table tr{
    width:100%;
	height:100%;
}
.hvr-wobble-top table tr td{
    width:64px;
	height:70px;
	vertical-align:middle;
}
.foto-grande table{
	width:100%;
	border-collapse:collapse;
}
.foto-grande table tr{
    width:100%;
	height:100%;
}
.foto-grande table tr td{
   width:783px;
	height:570px;
	vertical-align:middle;
}
.elevadasImgs {
  border-radius: 3px 3px 3px 3px;
  -moz-transition: all 0.5s ease-in;
  -o-transition: all 0.5s ease-in;
  -webkit-transition: all 0.5s ease-in;
  -ms-transition: all 0.5s ease-in;
  transition: all 0.5s ease-in;
  max-width: 170px;
  max-height: 190px;
}
.elevadasImgs:hover {
  -webkit-transform: scale(2);
  -moz-transform: scale(2);
  -o-transform: scale(2);
  transform: scale(2);
  -webkit-transform-origin: bottom;
  -moz-transform-origin: bottom;
  -o-transform-origin: bottom;
  transform-origin: bottom;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 0 8px rgba(191, 1, 1, 1);
}
#compraDireta{
position: fixed;
  width: 100%;
  height: 100%;
  left: 0;
  top: 0;
  display: table;
  text-align: center;
  vertical-align: middle;
  background-image: url('imagens-template/5020.gif');
  z-index: 99999999999999;
  background-repeat: no-repeat;
  background-position: 50%;
  background-color: rgba(255, 255, 255, 0.73);
  display:none;
  }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>
	function descricao()
	{
		$('#descricao').fadeIn();	
	}
	function descricaoOff()
	{
		$('#descricao').fadeOut();	
	}
	function trocaFoto(caminho)
	{
		
		if($('#foto_destaque').attr('src') == caminho)
			return false;
		
		
	$('#foto_destaque').attr('src','imagens-template/5020.gif');
	$( "#foto_destaque" ).fadeOut( "slow", function() {
    $('#foto_destaque').attr('src',caminho);
	$( "#foto_destaque" ).fadeIn( "slow");
  });
		
		
	}
function compraEmUmClick()
{
	
	$("#compraDireta").fadeIn( "slow");
	
}

</script>
<script src="https://apis.google.com/js/platform.js" async defer>
  {lang: 'pt-BR'}
</script>
{/literal}


<div id="compraDireta"></div>
{php}
$command = "getproducts";
 $adminuser = "deljdl";
 $values["pid"] = $_GET['template_id'];
 
 

 
 $result = localAPI($command,$values,$adminuser);
	if($result['totalresults'] == 0)
		echo ('<h1 class="headingBlank"><span class="color2">Template não localizado</span></h1>');
	else
	{
		
		
		$imagens = '
		
		<div onmouseover="trocaFoto(\'/imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'\')" class="hvr-wobble-top">
					<table>
						<tr>
							<td>
								<img src="/imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'"/>
							</td>
						</tr>
					</table>
				</div>';
		
		for($i=1;$i<=6;$i++)
		{
		
			if(file_exists('../downloads/'.url_amigavel($result['products']['product'][0]['name']).$i.'.jpg'))
				$imagens.= '
				
				<div onmouseover="trocaFoto(\'imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'&thumb='.$i.'\')" class="hvr-wobble-top">
					<table>
						<tr>
							<td>
								<img  src="imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'&thumb='.$i.'"/>
							</td>
						</tr>
					</table>
				</div>
				';	
			
			
		}
		
		
			$title=urlencode(utf8_encode(strip_tags(stripslashes('Baixe nosso mais novo TEmplate : '.$result['products']['product'][0]['name']))));
			$url=urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			$summary=urlencode(utf8_encode(strip_tags(stripslashes($result['products']['product'][0]['description']))));
			
			$botao='';	
				
			$botao.='
					 <a onclick="window.open(\'http://www.facebook.com/sharer.php?s=100&amp;p[title]='.$title.'&amp;p[summary]='.$summary.
					'&amp;p[url]='.$url.'&amp;&amp;p[images][0]='.$image.'\',\'sharer\',\'toolbar=0,status=0,width=548,height=325\');" href="javascript:void(0)">
					<img src="imagens-template/fb.png" /></a>
					
					';
				
			$botao.="<a href=\"https://twitter.com/share\" class=\"twitter-share-button\" data-via=\"tihospedagem\">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
				
				
				$botao.="<div class=\"g-plusone\" style=\"height:20px;\"></div>";
		
		
		
		
		
		
		echo '<div class="tpl">
		<div class="nome_template">
			'.$result['products']['product'][0]['name'].'
		</div>
		<div class="engloba_template">
			<div class="miniaturas">
				'.$imagens.'
			</div>
			<div class="foto-grande">
				<div class="aba_descricao">
					<div onmouseover="descricao()" onMouseOut="descricaoOff()" class="aba_emsi">
						DESCRIÇÂO
					</div>
				</div>
				<table>
							<tr>
								<td>
									<div id="descricao" class="descricao_template">
										'.$result['products']['product'][0]['description'].'
									</div>
									 <img class="elevadasImgs" id="foto_destaque" src="/imagens/templates/getcapa.php?template='.url_amigavel($result['products']['product'][0]['name']).'"/>
								</td>
							</tr>
						</table>
			   
			</div>
			<div class="botoes">
				<div class="preco">
					R$ '.$result['products']['product'][0]['pricing']['BRL'][monthly].'
				</div>
				<div class="comprar">
					<a href="https://tihospedagem.com.br/cart.php?a=add&pid='.$result['products']['product'][0]['pid'].'" onClick="compraEmUmClick()"><img src="imagens-template/carrinho.png" /> Comprar Agora</a>
				</div>
				<div class="redessociais">
					<table>
						<tr>
							<td>
								Compartilhe:
							</td>
							<td>
								'.$botao.'
							</td>
						</tr>
					</table>
				</div>
			</div>
			
		</div>
		<div class="ver_mais">
					<a href="sites-prontos" onClick="compraEmUmClick()">Clique aqui e Veja outros Templates</a>
				</div>
		</div>';
	}
{/php}

<p> * As imagens e conteúdos dos templates são meramente ilustrativos.</p>

