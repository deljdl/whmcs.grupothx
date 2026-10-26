<iframe src="https://kamban.grupothx.com.br"></iframe>
	<script>
		document.querySelector('h1').remove();

		var elementosContentArea = document.querySelectorAll('.contentarea');

// Itera sobre os elementos e remove o padding
elementosContentArea.forEach(function(elemento) {
    elemento.style.padding = '0'; // Define o padding como 0
});
	</script>