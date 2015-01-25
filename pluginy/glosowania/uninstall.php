<?php

/**
 * Usuwa tabele pluginu.
 */
function plugin_uninstalled() {
	global $wpdb;
	$nazwy_tabel = array('GlosZWartoscia', 'GlosLogiczny', 'GlosNaOpcje', 'Glos', 
		'Opcja', 'UprawnienieDoGlosowania', 'GlosowanieZWartoscia', 'GlosowaniePolubienie', 
		'GlosowanieListaOpcji', 'Glosowanie');
	foreach ($nazwy_tabel as $nazwa)
		$wpdb -> query("DROP TABLE IF EXISTS $nazwa");
}

?>