<?php
namespace Gerencianet\Exception {
 class GerencianetException extends \Exception {
  private $apiError; private $apiErrorDescription;
  public function __construct($payload, $httpCode=0) {
   $this->apiError=isset($payload['error'])?$payload['error']:'api_error';
   $d=isset($payload['error_description'])?$payload['error_description']:$payload;
   if(is_array($d)){ $m=isset($d['message'])?$d['message']:json_encode($d); if(!empty($d['property'])) $m.=': '.$d['property']; } else $m=(string)$d;
   $this->apiErrorDescription=$m; parent::__construct($m,isset($payload['code'])?(int)$payload['code']:(int)$httpCode);
  }
  public function __get($n){ if($n==='error')return $this->apiError; if($n==='errorDescription')return $this->apiErrorDescription; if($n==='code')return $this->getCode(); return null; }
 }
}
namespace Gerencianet {
 use Gerencianet\Exception\GerencianetException;
 class Gerencianet {
  private $clientId; private $clientSecret; private $baseUrl; private $accessToken;
  public function __construct(array $o){
   $this->clientId=isset($o['client_id'])?$o['client_id']:''; $this->clientSecret=isset($o['client_secret'])?$o['client_secret']:'';
   $this->baseUrl=!empty($o['sandbox'])?'https://cobrancas-h.api.efipay.com.br':'https://cobrancas.api.efipay.com.br';
   if(!$this->clientId||!$this->clientSecret) throw new GerencianetException(array('error'=>'invalid_credentials','error_description'=>'Client ID ou Client Secret da Efí não configurado.'));
  }
  private function request($method,$path,$body=null,$auth=true){
   $h=array('Accept: application/json','Content-Type: application/json');
   $h[]=$auth?'Authorization: Bearer '.$this->token():'Authorization: Basic '.base64_encode($this->clientId.':'.$this->clientSecret);
   $c=curl_init($this->baseUrl.$path); curl_setopt_array($c,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$h,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>45));
   if($body!==null)curl_setopt($c,CURLOPT_POSTFIELDS,json_encode($body));
   $r=curl_exec($c);
   $status=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);
   $responseType=(string)curl_getinfo($c,CURLINFO_CONTENT_TYPE);
   $ce=curl_error($c); $errno=curl_errno($c);
   $timings=array(
    'ip'=>curl_getinfo($c,CURLINFO_PRIMARY_IP),
    'dns_ms'=>round(curl_getinfo($c,CURLINFO_NAMELOOKUP_TIME)*1000),
    'connect_ms'=>round(curl_getinfo($c,CURLINFO_CONNECT_TIME)*1000),
    'tls_ms'=>round(curl_getinfo($c,CURLINFO_APPCONNECT_TIME)*1000),
    'first_byte_ms'=>round(curl_getinfo($c,CURLINFO_STARTTRANSFER_TIME)*1000),
    'total_ms'=>round(curl_getinfo($c,CURLINFO_TOTAL_TIME)*1000)
   );
   curl_close($c);
   if($r===false){
    $this->logFailure($method,$path,$status,$errno,$ce,$timings);
    throw new GerencianetException(array('error'=>'connection_error','error_description'=>'Falha de comunicação com a API Efí: '.$ce));
   }
   $d=json_decode($r,true);
   if(!is_array($d)){
    $this->logFailure($method,$path,$status,0,'Resposta JSON inválida (Content-Type: '.substr($responseType,0,80).')',$timings);
    throw new GerencianetException(array('error'=>'invalid_response','error_description'=>'A API Efí retornou uma resposta inválida na rota '.$path.' (HTTP '.$status.').','code'=>$status),$status);
   }
   if($status<200||$status>=300){
    $this->logFailure($method,$path,$status,0,'Erro HTTP retornado pela Efí',$timings);
    throw new GerencianetException($d,$status);
   }
   return $d;
  }
  // Registro centralizado de falhas de todas as operações (inclusive autenticação).
  // Nunca registrar credenciais, corpo da requisição ou token de notificação.
  private function logFailure($method,$path,$status,$errno,$message,$timings){
   if(!function_exists('logActivity'))return;
   $safePath=preg_replace('~(/v1/notification/)[^/?]+~','$1[oculto]',$path);
   $info=array(
    'method'=>$method,'route'=>$safePath,'http'=>$status,
    'curl_errno'=>$errno,'curl_error'=>$message,'timings'=>$timings,
    'origin'=>strtok((string)($_SERVER['REQUEST_URI'] ?? 'cron'),'?')
   );
   try{logActivity('Efí API HTTP: '.json_encode($info,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}
   catch(\Throwable $e){}
  }
  private function token(){
   if($this->accessToken)return $this->accessToken;
   $r=$this->request('POST','/v1/authorize',array('grant_type'=>'client_credentials'),false);
   if(empty($r['access_token']))throw new GerencianetException(array('error'=>'invalid_token','error_description'=>'A API Efí não retornou um access_token.'));
   return $this->accessToken=$r['access_token'];
  }
  public function createCharge($p=array(),$b=array()){return $this->request('POST','/v1/charge',$b);}
  public function payCharge($p,$b=array()){
   $id=isset($p['id'])?(int)$p['id']:0; if(!$id)throw new GerencianetException(array('error'=>'invalid_charge','error_description'=>'ID da cobrança inválido.'));
   unset($b['payment']['banking_billet']['instructions'],$b['payment']['banking_billet']['configurations']);
   $r=$this->request('POST','/v1/charge/'.$id.'/pay',$b);
   if(isset($r['data'])&&is_array($r['data'])){ $d=&$r['data'];
    if(empty($d['expire_at'])&&!empty($d['charges'][0]['dueDate']))$d['expire_at']=date('Y-m-d',strtotime(str_replace('/','-',$d['charges'][0]['dueDate'])));
    if(empty($d['charges'][0]['dueDate'])&&!empty($d['expire_at']))$d['charges'][0]['dueDate']=date('d/m/Y',strtotime($d['expire_at']));
    if(empty($d['link'])&&!empty($d['billet_link']))$d['link']=$d['billet_link'];
    if(empty($d['pdf']['charge'])&&!empty($d['link']))$d['pdf']['charge']=$d['link'];
   } return $r;
  }
  public function detailCharge($p,$b=array()){ $id=isset($p['id'])?(int)$p['id']:0; if(!$id)throw new GerencianetException(array('error'=>'invalid_charge','error_description'=>'ID da cobrança inválido.')); return $this->request('GET','/v1/charge/'.$id); }
  public function updateChargeMetadata($p,$b=array()){ $id=isset($p['id'])?(int)$p['id']:0; if(!$id)throw new GerencianetException(array('error'=>'invalid_charge','error_description'=>'ID da cobrança inválido.')); return $this->request('PUT','/v1/charge/'.$id.'/metadata',$b); }
  public function updateBillet($p,$b=array()){ $id=isset($p['id'])?(int)$p['id']:0; if(!$id)throw new GerencianetException(array('error'=>'invalid_charge','error_description'=>'ID da cobrança inválido.')); return $this->request('PUT','/v1/charge/'.$id.'/billet',$b); }
  public function cancelCharge($p,$b=array()){ $id=isset($p['id'])?(int)$p['id']:0; if(!$id)throw new GerencianetException(array('error'=>'invalid_charge','error_description'=>'ID da cobrança inválido.')); return $this->request('PUT','/v1/charge/'.$id.'/cancel',new \stdClass()); }
  public function getNotification($p,$b=array()){ $t=isset($p['token'])?trim($p['token']):''; if($t==='')throw new GerencianetException(array('error'=>'invalid_notification','error_description'=>'Token de notificação ausente.')); return $this->request('GET','/v1/notification/'.rawurlencode($t)); }
 }
}