<?php
/**
  * WHMCS Amostra de pagamento módulo de gateway
 *
 * módulos de gateway de pagamento permitem que você integre soluções de pagamento com a
 * Plataforma WHMCS .
 *
 * Este arquivo de exemplo demonstra como um módulo de gateway de pagamento para WHMCS deve
 * Ser estruturado e todas as funcionalidades que podem conter suportado .
 *
 * Dentro do próprio módulo, todas as funções devem ser prefixados com o módulo
 * Nome de arquivo, seguido por um sublinhado , em seguida, o nome da função . Por esta
 * Exemplo de arquivo , o nome do arquivo é " gatewaymodule " e, portanto, todas as funções
 * Começar " gatewaymodule_ " .
 *
 * Se o seu módulo ou terceira API destinatário não suportar uma determinada função,
 * Não deve definir essa função dentro de seu módulo. Apenas o _config
 * A função é necessária.
 *
 * Para mais informações , por favor consulte a documentação on-line .
 *
 * @see Http://docs.whmcs.com/Gateway_Module_Developer_Docs
 *
 * @Copyright Copyright ( c) WHMCS Limitada 2015
 * @license Http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Definir meta dados relacionados módulo.
 *
 * Valores devolvido aqui são usados ​​para determinar as capacidades relacionadas módulo e
 * configurações.
 *
 * @see Http://docs.whmcs.com/Gateway_Module_Meta_Data_Parameters
 *
 * Array @return
 */
function cielorecorrente_MetaData()
{
    return array(
        'DisplayName' => 'Cielo Recorrente',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 
* Definir opções de configuração de gateway.
 *
 * Os campos que definem aqui determinar as opções de configuração
 * Apresentado aos usuários de administrador ao ativar e configurar o
 * Módulo de gateway de pagamento para o uso.
 *
 * Tipos de campos suportados incluem:
 * * Texto
 * * senha
 * * sim não
 * * suspenso
 * * Rádio
 * * Textarea
 *
 * Exemplos de cada tipo de campo e seus parâmetros de configuração possíveis são
 * Fornecido na função de exemplo a seguir.
 *
 * @return array
 */
function cielorecorrente_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Módulo Cielo Recorrente by Grupo THX',
        ),
        // a text field type allows for single line text input
        'accountID' => array(
            'FriendlyName' => 'Account ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your account ID here',
        ),
        // a password field type allows for masked text input
        'secretKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter secret key here',
        ),
        // the yesno field type displays a single checkbox option
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
        // the dropdown field type renders a select menu of options
        'dropdownField' => array(
            'FriendlyName' => 'Dropdown Field',
            'Type' => 'dropdown',
            'Options' => array(
                'option1' => 'Display Value 1',
                'option2' => 'Second Option',
                'option3' => 'Another Option',
            ),
            'Description' => 'Choose one',
        ),
        // the radio field type displays a series of radio button options
        'radioField' => array(
            'FriendlyName' => 'Radio Field',
            'Type' => 'radio',
            'Options' => 'First Option,Second Option,Third Option',
            'Description' => 'Choose your option!',
        ),
        // the textarea field type allows for multi-line text input
        'textareaField' => array(
            'FriendlyName' => 'Textarea Field',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '60',
            'Description' => 'Freeform multi-line text input field',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return string
 */
function cielorecorrente_link($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $url = 'http://tihospedagem.com.br/modules/gateways/callback/cielorecorrente/';

    $postfields = array();
    $postfields['invoiceid'] = $invoiceId;

    $htmlOutput = '<form method="get" action="'.$url.'">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<button class="btn btn-primary" style="font-size: 25px;" type="submit"><i class="fa fa-credit-card" aria-hidden="true"></i> Pagar com Cartão</button>';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return array Transaction response status
 */
function cielorecorrente_refund($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return array(
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fees' => $feeAmount,
    );
}

/**
 * Cancel subscription.
 *
 * If the payment gateway creates subscriptions and stores the subscription
 * ID in tblhosting.subscriptionid, this function is called upon cancellation
 * or request by an admin user.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see http://docs.whmcs.com/Payment_Gateway_Module_Parameters
 *
 * @return array Transaction response status
 */
function cielorecorrente_cancelSubscription($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $dropdownField = $params['dropdownField'];
    $radioField = $params['radioField'];
    $textareaField = $params['textareaField'];

    // Subscription Parameters
    $subscriptionIdToCancel = $params['subscriptionID'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to cancel subscription and interpret result

    return array(
        // 'success' if successful, any other value for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
    );
}
