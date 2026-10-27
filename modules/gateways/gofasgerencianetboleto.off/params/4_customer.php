<?php
/**
 * Módulo Gerencinet Boleto para WHMCS
 * @author		Mauricio Gofas | gofas.net
 * @see			https://gofas.net/?p=7893
 * @copyright	2017 https://gofas.net
 * @license		https://gofas.net?p=9340
 * @support		https://gofas.net/?p=7856
 * @version		2.2.1
 */

if(!defined('WHMCS')) { die('Esse arquivo não pode ser acessado diretamente'); }
use WHMCS\Database\Capsule;
// Parametros do Cliente
$user_id					= $params['clientdetails']['id'];
$firstname					= $params['clientdetails']['firstname'];
$lastname					= $params['clientdetails']['lastname'];
//$phone						= preg_replace('/[^0-9]/', '', $params['clientdetails']['phonenumber']);
$phone						= preg_replace('/[^\da-z]/i', '', $params['clientdetails']['phonenumber']);

if ( $params['clientdetails']['companyname'] ) {
	$corporateName			= $params['clientdetails']['companyname'];
} elseif (!$params['clientdetails']['companyname']) {
	$corporateName			= $firstname . ' ' . $lastname;
}

/**
 *
 * Determine custom fields id
 *
 */
//$customfields = array();
foreach( Capsule::table('tblcustomfields') -> where( 'type', '=', 'client' )  -> get( array( 'fieldname', 'id' ) ) as $customfield ) {
	$customfield_id					= $customfield->id;
	$customfield_name				= ' '.strtolower( $customfield->fieldname );
	// cpf
	if ( strpos( $customfield_name, 'cpf') and !strpos( $customfield_name, 'cnpj') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}	
	// cnpj
	if ( strpos( $customfield_name, 'cnpj') and !strpos( $customfield_name, 'cpf') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}
	// cpf + cnpj
	if ( strpos( $customfield_name, 'cpf') and strpos( $customfield_name, 'cnpj') ) {
		foreach( Capsule::table('tblcustomfieldsvalues') -> where( 'fieldid', '=', $customfield_id ) -> where( 'relid', '=', $user_id ) -> get( array( 'value' ) ) as $customfieldvalue ) {
			$cpf_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
			$cnpj_customfield_value = preg_replace("/[^0-9]/", "", $customfieldvalue->value);
		}
	}
}

if (strlen($cpf_customfield_value) === 10) {
	$cpf = '0'.$cpf_customfield_value;
	
	if (strlen($cnpj_customfield_value) === 13) {
		
		$cnpj = '0'.$cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
		
	} elseif (strlen($cnpj_customfield_value) === 14) {
		$cnpj = $cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
		
	} elseif ( !$cnpj_customfield_value || strlen($cnpj_customfield_value) !== 14 || strlen($cnpj_customfield_value) !== 13) {
		$cnpj = false;		
		$customer = [
			'name' => $firstname.' '.$lastname,
			'cpf' => $cpf,
			'phone_number' => $phone,
		];

	}
}
elseif (strlen($cpf_customfield_value) === 11) {
	$cpf = $cpf_customfield_value;
	
	if (strlen($cnpj_customfield_value) === 13) {
		$cnpj = '0'.$cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
		
	} elseif (strlen($cnpj_customfield_value) === 14) {
		$cnpj = $cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
		
	} elseif ( !$cnpj_customfield_value || strlen($cnpj_customfield_value) !== 14 || strlen($cnpj_customfield_value) !== 13) {
		$cnpj = false;
		$customer = [
			'name' => $firstname.' '.$lastname,
			'cpf' => $cpf,
			'phone_number' => $phone,
		];
	}
}
elseif (strlen($cpf_customfield_value) === 13) {
	$cpf = false; 
	$cnpj = '0'.$cpf_customfield_value;
	$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
	if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
	} elseif ( !$requireCNPJandCPF ) {
		$customer = [
			'phone_number' => $phone,
			'juridical_person' => $juridical_data
		];
	}
	
}
elseif (strlen($cpf_customfield_value) === 14) {
	$cpf 				= false;
	$cnpj				= $cpf_customfield_value;
	$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
	if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
	} elseif ( !$requireCNPJandCPF ) {
		$customer = [
			'phone_number' => $phone,
			'juridical_person' => $juridical_data
		];
	}

}

elseif (!$cpf_customfield_value || strlen($cpf_customfield_value) !== 10 || strlen($cpf_customfield_value) !== 11 || strlen($cpf_customfield_value) !== 13 || strlen($cpf_customfield_value) !== 14 ) {
	if (strlen($cnpj_customfield_value) === 13) {
		
		$cnpj = '0'.$cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
	} elseif (strlen($cnpj_customfield_value) === 14) {
		$cnpj = $cnpj_customfield_value;
		$juridical_data = [
  			'corporate_name' => $corporateName,
  			'cnpj' => $cnpj
		];
		
		if ( $requireCNPJandCPF ) {
			$customer = [
				'name' => $firstname.' '.$lastname,
				'cpf' => $cpf,
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		} elseif ( !$requireCNPJandCPF ) {
			$customer = [
				'phone_number' => $phone,
				'juridical_person' => $juridical_data
			];
		}
		
	} elseif ( !$cnpj_customfield_value || strlen($cnpj_customfield_value) !== 14 || strlen($cnpj_customfield_value) !== 13) {
		$cnpj = false;
		$cpf = false;
	}
}
// Dados pessoa física
$customer_pf = [
	'name' => $firstname.' '.$lastname,
	'cpf' => $cpf,
	'phone_number' => $phone,
];


// Verify if generate billet
