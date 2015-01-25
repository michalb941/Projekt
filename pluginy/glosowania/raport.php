<html>
<body>
<h3>Raport</h3>
<?php

function do_druku_wyniki_opcje($nr) {
	$ile_glosow = (int)$_POST[$nr."ile_glosow"];
	$ile_opcji = (int)$_POST[$nr."ile_opcji"];
	for ($nr_opcji = 0; $nr_opcji < $ile_opcji; ++$nr_opcji) {
		$ile = (int)$_POST[$nr."opcja".$nr_opcji."ile"];
		echo $_POST[$nr."opcja".$nr_opcji."propozycja"].' '.$ile.' '.($ile_glosow == 0 ? '' : ($ile * 100.0 / $ile_glosow).'%');
		if ($nr_opcji != $ile_opcji - 1)
			echo '<br>';
	}
}

function do_druku_wyniki_polubienie($nr) {
	$ile_glosow = (int)$_POST[$nr."ile_glosow"];
	$lubi = (int)$_POST[$nr."lubi"];
	$nie_lubi = $ile_glosow - $lubi;
	echo 'Lubi: '.$lubi.' '.($ile_glosow == 0 ? '' : ($lubi * 100.0 / $ile_glosow).'%').'<br>Nie lubi: '.$nie_lubi.' '.($ile_glosow == 0 ? '' : ($nie_lubi * 100.0 / $ile_glosow).'%');
}

function do_druku_wyniki_wartosc($nr) {
	echo 'Średnia: <b>'.$_POST[$nr."srednia"].'</b> / '.$_POST[$nr."zakres"]; 
}

function generuj_raport_do_druku() {
	for ($i = 0; $i < (int)$_POST["ile_glosowan"]; ++$i)
		if (isset($_POST[$i.""])) {
			echo '<br>----------------------------------------------------------------<br>';
			echo '<b>'.$_POST[$i."pytanie"].'</b><br>';
			echo 'Liczba głosów: '.$_POST[$i."ile_glosow"].'<br><br>';
			$rodzaj = $_POST[$i.""];
			if ($rodzaj == "lista opcji")
				do_druku_wyniki_opcje($i);
			if ($rodzaj == "polubienie")
				do_druku_wyniki_polubienie($i);
			if ($rodzaj == "gwiazdka/suwak")
				do_druku_wyniki_wartosc($i);
		}
	echo '<br>----------------------------------------------------------------<br>';
}

generuj_raport_do_druku();
?>
</body>
</html>