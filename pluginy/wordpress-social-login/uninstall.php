<?php

/**
 * Usuwa tabele pluginu.
 */
function deinstalacja() {
	global $wpdb;
	$nazwy_tabel = array('wp_wsluserscontacts', 'wp_wslusersprofiles', 'GrupaUzytkownika', 'Grupa');
	foreach ($nazwy_tabel as $nazwa)
		$wpdb -> query("DROP TABLE IF EXISTS $nazwa");
}

deinstalacja();
?>