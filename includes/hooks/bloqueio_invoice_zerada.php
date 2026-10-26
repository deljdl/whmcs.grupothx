<?

if (!defined("WHMCS"))
   die("This file cannot be accessed directly");

add_hook('EmailPreSend', 1, function($vars) {

    #REQUIRE TARIFAS OBRIGATÓRIO PARA NÃO DAR ERRO NO CRON
    if(empty($_SERVER['DOCUMENT_ROOT']))
    {
        $_SERVER['DOCUMENT_ROOT'] = '/home/thxcom/public_html';
    }
    require_once($_SERVER['DOCUMENT_ROOT'].'/tarifasTHX.php');

    // WHMCS admin user for API
    $adminuser = 'deljdl';

    // Get name of the email template
    $email_template_name = trim($vars['messagename']);

    // Get invoice ID
    $relid = $vars['relid'];

    // Create array to abort messages
    $merge_fields = array();

    // Abort invoice messages and cancel invoice
    if ($email_template_name == 'Invoice Payment Confirmation' || $email_template_name == 'Invoice Created')
    {

        $invoice = localAPI( 'GetInvoice', array('invoiceid' => $relid), false);

        if($invoice['total'] <= 0)
        {
            $merge_fields['abortsend'] = true;

            ##### Start API Update #####
            $command = "updateinvoice";
            $values["invoiceid"] = $relid;

            $values["notes"] = "Fatura {$relid} criada com valor {$invoice['total']} foi cancelada pelo HOOK bloqueio_invoice_zerada ";
            $values["status"] = "Cancelled";

            $results = localAPI($command,$values,$adminuser);
            ##### End Admin Password API Call #####

            enviaLogTHXJdl('Fatura '.$relid.' criada com valor '.$invoice['total'].' foi cancelada!');

            return $merge_fields;
        }

    }
});