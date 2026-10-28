<?php
if (!defined('WHMCS')) { die('No direct access'); }

add_hook('AdminAreaHeaderOutput', 1, function($vars)
{
    if ($vars['filename'] !== 'invoices' && $vars['filename'] !== 'clientsinvoices' && (!isset($vars['pageicon']) || $vars['pageicon'] !== 'invoices')) {
        return '';
    }
    if (!empty($_GET['id'])) {
        return '';
    }

    // URL que vai receber o POST
    $pixPostUrl = 'https://pix.grupothx.com/cobranca-layout';
    $pixUrlEsc  = htmlspecialchars($pixPostUrl, ENT_QUOTES);

  return '
<script type="text/javascript">
jQuery(function($){
  try {
    var $table = $(".datatable");
    if (!$table.length || !$table.find("tbody tr").length) return;


    function isOverdueBR(vencimento) {
      if (!vencimento) return false;

      var m = vencimento.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
      if (!m) return false;

      var dia = parseInt(m[1], 10);
      var mes = parseInt(m[2], 10) - 1; // meses começam em 0 no JS
      var ano = parseInt(m[3], 10);

      var dataVenc = new Date(ano, mes, dia);
      var hoje = new Date();

      // Zera as horas para comparar só a data
      dataVenc.setHours(0,0,0,0);
      hoje.setHours(0,0,0,0);

      return dataVenc < hoje;
    }

    function toMySQLAmount(text){
      if (!text) return "";
      var raw = String(text).trim();
      raw = raw.replace(/[^0-9.,-]/g, "");
      var neg = raw.indexOf("-") !== -1;
      raw = raw.replace(/-/g, "");
      var lastComma = raw.lastIndexOf(",");
      var lastDot   = raw.lastIndexOf(".");
      var decSep = "";
      if (lastComma !== -1 && lastDot !== -1) {
        decSep = (lastComma > lastDot) ? "," : ".";
      } else if (lastComma !== -1) {
        decSep = ",";
      } else if (lastDot !== -1) {
        decSep = ".";
      }
      var normalized = raw;
      if (decSep) {
        if (decSep === ",") {
          normalized = normalized.replace(/\./g, "");
          normalized = normalized.replace(/,/g, ".");
        } else {
          normalized = normalized.replace(/,/g, "");
        }
      }
      var num = parseFloat(normalized);
      if (isNaN(num)) return "";
      return (neg ? -num : num).toFixed(2);
    }

    $table.find("tbody tr").each(function(){
      var $tr = $(this), id = $tr.find("input[name=\"selectedinvoices[]\"]").val() || null;
      var delOnclick = $tr.find("td:last a[onclick*=doDelete]").attr("onclick") || "";
      var m = delOnclick.match(/doDelete\\(\'(\\d+)\'\\)/);
      if (m) id = m[1];
      if (!id) {
        var href = $tr.find("a[href*=\"/invoice/\"],a[href*=\"invoices.php?id=\"],a[href*=\"viewinvoice.php?id=\"]").attr("href") || "";
        var m2 = href.match(/\\/invoice\\/(\\d+)/) || href.match(/[?&]id=(\\d+)/);
        if (m2) id = m2[1];
      }
      if (!id) {
        var t0 = ($tr.find("td,th").first().text() || "").match(/(\\d+)/);
        if (t0) id = t0[1];
      }

       var vencimento = $.trim($tr.find("td").eq(3).text());
       var atrasada = isOverdueBR(vencimento);
       console.log(\'Vencimento:\', vencimento, \'Atrasada:\', atrasada);

      var valorTxt = $.trim($tr.find("td").eq(5).text());
      var status = $.trim($tr.find("td").eq(7).text());
      var valorMysql = toMySQLAmount(valorTxt);
      var descricao = "Grupo THX Fatura "+id;
      var idCustom = "A"+id;

      if (id && atrasada) {
        descricao += "<br />Multa por atraso R$ 3.45";
        valorMysql = (parseFloat(valorMysql) + 3.45).toFixed(2);

        var $form = $("<form/>", { method:"get", action:"'. $pixUrlEsc .'", target:"_blank", style:"display:inline" });
        $form.append($("<input/>", { type:"hidden", name:"id", value:idCustom }));
        $form.append($("<input/>", { type:"hidden", name:"generate", value:true }));
        $form.append($("<input/>", { type:"hidden", name:"conta", value:"nujuridica" }));
        $form.append($("<input/>", { type:"hidden", name:"valor", value:valorMysql }));
        $form.append($("<input/>", { type:"hidden", name:"descricao", value:descricao }));
        $form.append($("<button/>", { type:"submit", class:"btn btn-default btn-xs", html:'<i class="ri-pix-fill"></i>', title:"Gerar PIX", style:"padding:2px 5px;white-space:nowrap" }));

        var $acoes = $tr.children("td").last().find(".btn-group").first();
        if ($acoes.length) {
          $acoes.append($form);
        } else {
          $tr.children("td").last().append($form);
        }
      }
    });
  } catch(e) {
    if (window.console && console.warn) console.warn("PIX hook error:", e);
  }
});
</script>';
});