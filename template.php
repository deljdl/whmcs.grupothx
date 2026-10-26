<?php
 //Array ( [amigavel] => Vivid-Photo [template_id] => 56 ) 1
 if(!isset($_GET['template_id']) || !is_numeric($_GET['template_id']))
 	die('<meta http-equiv="refresh" content="0;url=https://tihospedagem.com.br/sites-prontos">');
 define("CLIENTAREA",true);
 define("FORCESSL",true); // Uncomment to force the page to use https://
 
 require("init.php");
 
 $ca = new WHMCS_ClientArea();
 
 

 $ca->initPage();
 
 $ca->setTemplate('/templates/six-THX/template.tpl');
 
 $ca->output();
 
 ?>