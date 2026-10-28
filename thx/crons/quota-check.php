<?
function listaHospedagemsComQuotaNoLImite()
{
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, HTTP_WHMCS . '/includes/api.php');
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS,
      http_build_query(
         array(
               'action' => 'GetClientsProducts',
               'username' => 'deljdl',
               'password' => API_MD5_PASSWORD,
               'responsetype' => 'json',
         )
      )
   );
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $response = curl_exec($ch);
   curl_close($ch);

   $lista = json_decode($response);

   foreach ($lista->products->product as $res)
   {
      if ($res->diskusage > 0 && $res->status == 'Active' && $res->disklimit > 0)
      {
         $porcentagemUsada = round(($res->diskusage * 100 / $res->disklimit), 2);

         if ($porcentagemUsada >= USO_DE_DISCO_MAXIMO_ALERTA)
         {
            if (! clienteAceitaReceberAvisosDeEspacoEmDisco($res->clientid))
            {
               continue; // Pula para o próximo item da lista se o cliente não aceita receber avisos de espaço em disco
            }
               // Alerta crítico: pode enviar diariamente
               if ($porcentagemUsada >= USO_DE_DISCO_MAXIMO_ALERTA_CRITICO)
               {
                  enviaAvisoDeEspacoEmDisco($res);
               }
               elseif (date('N') == 1) // Alerta normal: somente às segundas-feiras
               {
                  enviaAvisoDeEspacoEmDisco($res);
               }
         }
      }
   }

}


function clienteAceitaReceberAvisosDeEspacoEmDisco($clientid)
{
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, HTTP_WHMCS . '/includes/api.php');
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS,
      http_build_query(
         array(
               'action' => 'GetClientsDetails',
               // See https://developers.whmcs.com/api/authentication
               'username' => 'deljdl',
               'password' => API_MD5_PASSWORD,
               'clientid' => $clientid,
               'stats' => true,
               'responsetype' => 'json',
         )
      )
   );
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $response = curl_exec($ch);
   curl_close($ch);

   $lista = json_decode($response);
   return $lista->customfields[5]->value == 'on' ? false : true;

}


function enviaAvisoDeEspacoEmDisco($res)
{

   $service_id = $res->id;

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, HTTP_WHMCS . '/includes/api.php');
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS,
      http_build_query(
         array(
               'action' => 'SendEmail',
               // See https://developers.whmcs.com/api/authentication
               'username' => 'deljdl',
               'password' => API_MD5_PASSWORD,
               'messagename' => 'Espaço em Disco Aviso',
               'id' => $service_id,
               'responsetype' => 'json',
         )
      )
   );
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $response = curl_exec($ch);
   curl_close($ch);

   $lista = json_decode($response);
   echo '<pre>'.print_r($lista, 1).'</pre>';
}


listaHospedagemsComQuotaNoLImite();