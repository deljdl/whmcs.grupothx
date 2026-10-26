<?php


//Laravel DataBase
use WHMCS\Database\Capsule;

//Bloqueia o acesso direto ao arquivo
if (!defined("WHMCS")){
	die("Acesso restrito!");
}


require_once ROOT_WHMCS . '/tarifasTHX.php';

add_hook('InvoiceCreationPreEmail', 1, function($vars) {

    logModuleCall(
			'/includes/hooks/aviso_envio_nota_fiscal.php',
			__FUNCTION__,
			$vars,
			'',
			$vars
		);

		if(
			empty(
				$vars['invoiceid']
			)
		){
			return;
		}

		$invoice =
			localAPI(
				'GetInvoice',
				array(
					'invoiceid' =>
						$vars['invoiceid']
				),
				false
			);

		logModuleCall(
			'/includes/hooks/aviso_envio_nota_fiscal.php',
			__FUNCTION__,
			$invoice,
			'',
			$invoice
		);

		if(
			empty(
				$invoice['userid']
			)
		){
			return;
		}

		$dadosCliente =
			localAPI(
				'GetClientsDetails',
				array(
					'clientid' =>
						$invoice['userid']
				),
				false
			);

		logModuleCall(
			'/includes/hooks/aviso_envio_nota_fiscal.php',
			__FUNCTION__,
			$dadosCliente,
			'',
			$dadosCliente
		);

		if(
			empty(
				$dadosCliente['customfields7']
			)
			||
			$dadosCliente['customfields7']
				!= 'on'
		){
			return;
		}

   
        $invoiceItens = Capsule::select("SELECT * FROM tblinvoiceitems WHERE invoiceid =  {$vars['invoiceid']}");

        logModuleCall('/includes/hooks/aviso_envio_nota_fiscal.php', __FUNCTION__, $invoiceItens, '', $invoiceItens);

        $linkFatura = getUrlCurta('https://grupothx.com/viewinvoice?i='.base64_encode($vars['invoiceid']));

        $dataFormatoBrasileiro = date('d/m/Y', strtotime($invoice['duedate']));

        $comandoNFSE =
        geraComandoNFSE(
            $vars['invoiceid'],
            $invoice,
            $dadosCliente,
            $invoiceItens
        );


        $html = '
        <h3>Enviar a nota fiscal</h3>
        <strong>Link para gerar:</strong> <a target="_blank" href="https://www.nfse.gov.br/EmissorNacional/DPS/Pessoas">Gerar Nota Fiscal</a>
        <br />
        <strong>Regime de Apuração dos Tributos no Simples Nacional:</strong> Regime de apuração dos tributos federais e municipal pelo Simples Nacional
        <br />
        <strong>Serviços Prestados: </strong> 1.0701.07.01 - Suporte técnico em informática, inclusive instalação, configuração e manutenção de programas de computação e bancos de dados.
        <br />
        <strong>Aliquota: </strong> 4
        <hr />
        <h3>Tomador do Serviço</h3>
        <strong>Cliente:</strong> '.$dadosCliente['firstname'].'
        <br />
        <strong>Empresa:</strong> '.$dadosCliente['companyname'].'
        <br />
        <strong>CPF/CNPJ:</strong> '.$dadosCliente['customfields3'].'
        <br />
        <strong>Telefone:</strong> '.$dadosCliente['phonenumber'].'
        <br />
        <strong>Email:</strong> '.$dadosCliente['email'].'
        <br />
        <h3>Endereço:</h3>
        <strong>CEP:</strong> '.$dadosCliente['postcode'].'
        <br />
        <strong>Endereço:</strong> '.$dadosCliente['address1'].'
        <br />
        <strong>Bairro:</strong> '.$dadosCliente['address2'].'
        <br />
        <strong>Estado:</strong> '.$dadosCliente['state'].'
        <br />
        <strong>Cidade:</strong> '.$dadosCliente['city'].'
        <br />
        <h4>O serviço prestado é um caso de: imunidade, exportação de serviço ou não incidência do ISSQN? Não</h4>

        <strong>Vencimento:</strong> '.$dataFormatoBrasileiro.'
        <br />
        <strong>Link de visualização da fatura:</strong> <a target="_blank" href="'.$linkFatura.'">'.$linkFatura.'</a>
        <hr /><br />
        <strong>Itens da Cobrança '.$vars['invoiceid'].'</strong><br />';

        foreach($invoiceItens as $item)
        {
            $html.= $item->description.': R$ '.$item->amount.'<br />';
        }

        $html.='<br /><strong>Total:</strong> R$ '.$invoice['total'].'<br /><hr /><br />';


$html .= '
<hr />
<h3>Automação NFS-e</h3>

<p>
Copie e cole no console:
</p>

<div style="
background:#f5f5f5;
border:1px solid #ddd;
padding:10px;
font-family:monospace;
font-size:12px;
word-break:break-all;
">
'.$comandoNFSE.'
</div>
';


        require_once ROOT_WHMCS . '/vendor/phpmailer/phpmailer/PHPMailerAutoload.php';
        $email = new PHPMailer();
        $email->WordWrap = 50;               // set word wrap
        $email->Priority = 1;
        $email->IsHTML(true);
        $email->From      = 'financeiro@grupothx.com.br';
        $email->FromName  = 'Grupo THX';
        $email->Subject   = 'Enviar Nota Fiscal '.$dadosCliente['companyname'].' - '.$vars['invoiceid'];
        $email->Body      = $html;

        $email->AddAddress('deljdl@gmail.com', 'Admin');
        $envio = $email->Send();

        alexa('Enviar a nota fiscal para '.$dadosCliente['firstname'].', '.$dadosCliente['companyname']);
        criaTarefaKamban($html, $invoice['duedate'], 'NOTA FISCAL');
    
});