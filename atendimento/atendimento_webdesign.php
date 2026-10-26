<style>

*{
	margin: 0;
	padding: 0;
}

#barra_mother{
	width: 100%;
	height: auto;
	position: fixed;
	bottom: 0; 
 	left:0;
	transition: all 2s;

}

.barra_mother_open{
	transition: all 1s;	
	bottom: 355px;
}

.barra_aba{
	height: 35px;
	width: 280px;
	background-image: url(../atendimento/imagens/fundo-webdesign.png);
	-webkit-border-top-left-radius: 7px;
	-webkit-border-top-right-radius: 7px;
	-moz-border-radius-topleft: 7px;
	-moz-border-radius-topright: 7px;
	border-top-left-radius: 7px;
	border-top-right-radius: 7px;
	float: right;
	cursor: help;
	
}

.barra_aba h3{
	color: #ffffff;
	font-size: 15px;
	font-weight: 600;
	letter-spacing: 0.5px;
	text-align: center;
	text-transform: none !important;
	margin: 5px 0 0 0;
}

.container_barra{
	width: 100%;
	height: 125px;
	float: left;
	background-image: url(../atendimento/imagens/fundo-webdesign.png);
	font-family: "Myriad Pro";
	display: none;

}


.container_barra a{
	text-decoration: none;
}


.content{
	width: 1024px;
	height: 112px;
	margin: 0 auto;
	position: relative;
}

.fecha{
	width: 26px;
	height: 26px;
	position: absolute;
	top:  5px;
	right: 12px;
	cursor: pointer;
}

.foto{
	width: 100px;
	height: 100px;
	float: left;
	margin: 6px 0 0 10px;
}

.content_left{
	width: 490px;
	height: 95px;
	float: left;
	margin: 20px 0 0 20px;
	border-left: solid #fff 1px;
	line-height: 22px;

}

.content_left h4{
	color: #ffffff;
	font-size: 14px;
	font-weight: 500;
	letter-spacing: 0.5px;
	margin: 5px 0 0 20px;
	text-transform: none !important;
}

.content_left h3{
	color: #ffffff;
	font-size: 15px;
	font-weight: bold;
	letter-spacing: 0.5px;
	margin: 5px 0 0 20px;
	text-transform: none !important;

}


.content_left p{
	color: #ffffff;
	font-size: 12px;
	font-weight: 100;
	letter-spacing: 0.5px;
	margin: 5px 0 0 20px;
}

.content_right{
	width: 370px;
	height: 95px;
	float: left;
	margin: 20px 0 0 12px;
	border-left: solid #fff 1px;	
	line-height: 25px;
	
	
}

.content_right h4{
	color: #ffffff;
	font-size: 14px;
	font-weight: 600;
	letter-spacing: 0.8px;
	margin: 5px 0 0 15px;
	text-transform: none !important;
}


.content_right a{
	margin-left: 16px;
	color: #C80404;
	font-weight: 900;
	text-decoration: none;
	font-size: 18px;
}

.content_right a:hover{
	color: #cccccc;
}

#ligue_ja{
	margin: 5px 0 0 15px;
	font-weight: 600;
	text-decoration: none;
	
	display:none;
}

#ligue_ja input{
	height: 30px;
	 
}

.campo_nome{
	width: 120px;
	color: #666;
	font-size: 14px;
	padding: 4px 0 0 5px;
	
}

.campo_tel{
	width: 110px;
	margin: 0 0 0 3px;
	padding: 4px 0 0 5px;
	color: #666;
	font-size: 14px;
}


.content_right form input[type=submit]{
	width: 95px;
	background-color: #9B0303;
	padding: 3px 0 0 0;
	margin: 0 0 0 5px;
	border: none;
	color: #ffffff;
	font-size: 18px;
}
#open_cel{
	display:none;
}
#getContact{
	display:none;
}
@media screen and (max-width: 1024px) {
#barra_mother > * {
	transition:all 1s ease-in-out;
}
.content_left{
	width:0;
	border:none;
	overflow:hidden;	
}
.fecha{
	right: 60px;
	bottom: 20px;
}

.content{
	width: 100%;
}

.content_right{
	height:auto;
	border: none;
	width: 85%;
	
}
#ligue_ja input{
	padding: 5px 0 0 15px;
}
.content_right form input[type=submit]{
	width: 100%;
	margin: 0 auto;
	margin-top: 5px;
	margin-bottom: 5px;
	padding: 3px 0 0 5px;;

}

.foto{
	display: none;
}

.campo_nome{
	width: 100%;
}

.campo_tel{
	width: 100%;
	margin: 5px 0 0 0;
	
}

#ligue_ja input{
	height: 40px;
}

.container_barra{
	height:auto;
}
}
@media screen and (max-width: 600px) {
#barra_mother > * {
	transition:all 1s ease-in-out;
}
.foto{
	width:0;
	overflow:hidden;.
	border:none;
	display: none !important;
	margin: 0;	
}

.content_left{
	display: none !important;
	margin: 0;
}


.container_barra{
	height:auto;
}


.barra_aba{
	width:100%;
}

.fecha{
	left:10px;
	top: -20px;
}

.content{
	width: 100%;
}

.content_right{
	margin: 0 auto !important;
	text-align: center;
	height:auto;
	width: 100%;
}

.content_right a{
	text-align: center;
	font-size:15px;
	margin-left: 5px;	
}

.content_right h4{
	margin-left: 1px;
}

#open_chat{
	display:none;
}
#open_cel{
	display:inline;
}

.content_right form{
	margin: 0 auto;
}
.content_right form input[type=submit]{
	width: 100%;
	margin: 0 auto;
	margin-bottom: 5px;

}

#ligue_ja{
	margin: 0 auto;
}
#ligue_ja input{
	padding: 0 0 0 15px;
	height: 40px;
	margin: 0 auto;
	margin-top: 5px;
	 
}

.campo_nome{
	width: 100%;
}

.campo_tel{
	width: 100%;
}


</style>

<section id="barra_mother">

		<section class="barra_aba" onClick="atendimentoAberto()">
        	
            <h3>Suporte e Atendimento</h3>
                    
        </section>
        <section class="container_barra">
        
            <section class="content">
                
                <article class="fecha">
                    <img src="atendimento/imagens/fecha-webdesign.png" onClick="atendimentoAberto()" title="fechar" />
                </article>
                
                <article class="foto">
                    <img src="atendimento/imagens/atendimento.png" title="Atendimento">
                </article>
                
                <article class="content_left">
                    <h4>Alguma  dúvida? Será um prazer atendê-lo em nosso <span>chat online!</span></h4>
                    <h3>(11) 4653 7002 | contato@grupothx.com.br</h3>
                    <p>*Horário de atendimento: 8h às 18h de segunda a sexta</p>
                
                </article>
                
                
                <article class="content_right">
                    <h4>Qual atendimento desejado?</h4>
                        
                            <a href="mailto:contato@grupothx.com.br">E-mail </a>                    
                            <a id="open_chat" href="javascript:window.open('http://xpainel.com/chat/client.php?locale=pt-br&xpainel_acount=39','chat','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=640, height=480');" > Chat </a>
                            <a id="abrechat" href="skype:deljdl?chat">	Skype </a>
                            <a id="open_cel" href="tel:46537002">Ligue Pra Nós </a>
                            <a id="open_lig" href="javascript:openLigue()"> Ligamos pra Você </a>
                        
                        <form id="ligue_ja" method="post" target="getContact" action="sms.php">
                        	<input type="text" class="campo_nome" name="nome" placeholder="Seu Nome" max="25" required />
                            <input type="text" class="campo_tel" name="celular" maxlength="15" placeholder="(11) 98888-8888" required />
                            <input type="submit" name="enviar" value="Enviar" />
                            
                        </form>        
                <iframe id="getContact" name="getContact" src="about:blank"></iframe>
                
                </article>
            
            
            </section><!-- content -->
        
        </section><!-- container_barra -->
</section>
<script>
	setTimeout(function() {
    	atendimentoAberto();
	}, 5000);
</script>


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script>
function atendimentoAberto()
{	
	$(".container_barra").slideToggle("slow");
}
function openLigue()
{
	$('#ligue_ja').fadeIn('slow');	
}
</script>
