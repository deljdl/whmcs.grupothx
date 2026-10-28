<?php

add_hook('AdminAreaHeadOutput', 1, function ($vars) {
    return '
        <link rel="stylesheet" href="/thx/assets/css/admin.css">
        <script src="/thx/assets/js/admin.js"></script>

		<!-- ##GRUPO THX## -->
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
		new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
		\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,\'script\',\'dataLayer\',\'GTM-KJLKHV8C\');</script>
		<!-- End Google Tag Manager -->
		<link rel="stylesheet" href="//grupothx.com/tihospedagem/cpanel.css">
		<script src="//grupothx.com/tihospedagem/cpanel.js"></script>
		<link rel="preconnect" href="https://xpainel.com.br" crossorigin>

		<script>
		(function(w,d,u){
		function load(){
			var s=d.createElement(\'script\');
			s.src=u + (u.indexOf(\'?\')>-1?\'&\':\'?\') + \'v=\' + Date.now();
			s.async=true;
			s.crossOrigin=\'anonymous\';
			d.head.appendChild(s);
		}
		if(\'requestIdleCallback\' in w){ w.requestIdleCallback(load,{timeout:1500}); }
		else { d.addEventListener(\'DOMContentLoaded\', load); }
		})(window,document,\'https://xpainel.com.br/datas_comemorativas/webmail-bg.js.php\');
		</script>
		<!-- ##GRUPO THX## -->
    ';
});