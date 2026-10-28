<?php
/* WHMCS SMS Addon with GNU/GPL Licence
 * AktuelHost - http://www.aktuelhost.com
 *
 * https://github.com/AktuelSistem/WHMCS-SmsModule
 *
 * Developed at Aktuel Sistem ve Bilgi Teknolojileri (www.aktuelsistem.com)
 * Licence: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.txt)
 * */
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

function fluxo_de_caixa_config()
{


    if(isset($_POST['ano']))
    {
        $_SESSION['Xano'] = $_POST['ano'];
        $_SESSION['Xmes'] = $_POST['mes'];
    }

    if(! isset($_SESSION['Xmes']))
    {
        $_SESSION['Xano'] = date('Y');
        $_SESSION['Xmes'] = date('m');
    }


    $configarray = array(
        "name" => "Fluxo de Caixa",
        "description" => "Controle de fluxo de caixa by Grupo THX",
        "version" => "2.0",
        "author" => "Grupo THX",
    );
    return $configarray;
}

function fluxo_de_caixa_activate()
{
    return array('status' => 'success', 'description' => 'Fluxo de Caixa Ativado :)');
}

function fluxo_de_caixa_deactivate()
{

    return array('status' => 'success', 'description' => 'Fluxo de Caixa Desativado :)');
}

function fluxo_de_caixa_upgrade($vars)
{
    return;
}

function fluxo_de_caixa_output($vars)
{
    ## chamada segura cli e http (conte o número de barras ou quantos ../ do path relativo) do tarifasTHX modules\addons\fluxo_de_caixa\fluxo_de_caixa.php
    require_once(dirname(dirname(dirname(__DIR__))) . '/tarifasTHX.php');
    $path ='https://grupothx.com/thx/admin/addonmodules.php?module=fluxo_de_caixa&tab=';

    echo '
    <div class="contentModule" style="padding: 0 5px;">
        <div class="row" style="    border-bottom: solid #eee 1px;
        margin-bottom: 21px;
        padding: 5px;">
            <div class="col-md-8" style="padding:0">
                <a href="'.$path.'caixa" class="btn btn-default" type="button">
                  Caixa
                </a>

                <!--<a href="'.$path.'retorno" class="btn btn-default" type="button">
                  Processar Arquivo Retorno
                </a>-->

                <a href="'.$path.'gerenciamento-pj" class="btn btn-default" type="button">
                  Gerenciamento de Job\'s
                </a>

                <a href="'.$path.'ticket" class="btn btn-default" type="button">
                 Ticket Médio
              </a>

              <a href="'.$path.'fluxo-diario" class="btn btn-default" type="button">
                 Fluxo Diário
              </a>

              <a href="'.$path.'receita-mensal-estimada" class="btn btn-default" type="button">
                 Receita Mensal Estimada Detalhada
              </a>

              <a href="'.$path.'evolucao-dividas" class="btn btn-default" type="button">
                 Evolucao Dívidas
              </a>

              
              
            </div>
            <div class="col-md-4" style="padding:0">
                <form method="post" id="periodoform">
                    <div class="col-md-12" style="padding:0">
                        <div class="col-md-2" style="padding:0">
                            <input style="width: 45px; padding:5px;float: left;border: 0;color: #202f60;font-size: 25px;font-weight: bold;" class="form-control" type="text"  min="1" max="12" value="'.$_SESSION['Xmes'].'" id="mes" name="mes">
                        </div>
                        <div class="col-md-2" style="padding:0;font-size: 30px;line-height: 35px;font-weight: bolder;text-align: center;margin: 0px -15px;">
                            /
                        </div>
                        <div class="col-md-3" style="padding:0">
                            <input  style="width: 75px;padding:5px;float: left; border: 0;color: #202f60;font-size: 25px;font-weight: bold;" class="form-control" min="2012" max="'.date('Y').'" type="text" value="'.$_SESSION['Xano'].'" id="ano" name="ano">
                        </div>
                        <div class="col-md-4" style="padding:0; font-size: 30px; line-height: 35px;">
                            <a href="javascript:setPeriodo(2)"><i class="fa fa-chevron-right" aria-hidden="true"></i></a>
                            <a href="javascript:setPeriodo(1)"><i class="fa fa-chevron-up" aria-hidden="true"></i></a>
                            <a href="javascript:setPeriodo(0)"><i class="fa fa-chevron-down" aria-hidden="true"></i></a>
                        </div>
                    </div>

                </form>
            </div>
        </div>';

    $mod = isset($_GET['tab']) ? $_GET['tab'] : 'caixa';


    $tb = ROOT_WHMCS.'/modules/addons/fluxo_de_caixa/modulos/'.$mod.'.php';



    require_once($tb);



   echo '</div><hr />Grupo THX'."
        <script>

        function setPeriodo(mais)
        {

            mesInt = parseInt($('#mes').val());
            anoInt = parseInt($('#ano').val());



            if(mais == 1)
            {
                if(mesInt == 12)
                {
                    mesInt = 1;
                    anoInt++;
                }
                else
                {
                    mesInt++;
                }
            }
            else
            {
                if(mais == 0)
                {
                    if(mesInt == 1)
                    {
                        mesInt = 12;
                        anoInt--;
                    }
                    else
                    {
                        mesInt--;
                    }
                }
            }

            $('#mes').val(mesInt);
            $('#ano').val(anoInt);

            $('#periodoform').submit();

        }

        $('a[href$=\"&tab={$mod}\"]').addClass('btn-danger');</script>";
}
