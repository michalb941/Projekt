<?php
/**
 * Plugin Name: Głosowania
 * Description: Plugin umożliwia głosowania przez USOS. Dodaj [glosowania] aby wyświetlić wszystkie głosowania. Dodaj [glosowania id="nr"] aby wyświetlić głosowanie o id równym nr.
 * Version: 1.0.0
 * Author: Michał Błaziak
*/

add_action( 'init', 'install_plugin' );
add_action( 'admin_menu', 'dodaj_menu' );
add_shortcode( 'glosowania', 'glosowania_shortcode' );

include 'tabele.php';

/** Zwraca id użytkownika. */
function uzytkownik() {
	$uzytkownik = wp_get_current_user();
	return $uzytkownik -> ID;
}

/** Zwraca rodzaj głosowania. */
function rodzaj_glosowania($id_glosowania) {
	global $wpdb;
	$glosowanie_opcje = $wpdb -> query($wpdb -> prepare("SELECT * FROM GlosowanieListaOpcji WHERE idGlosowania = %d;", $id_glosowania));
	$glosowanie_polubienie = $wpdb -> query($wpdb -> prepare("SELECT * FROM GlosowaniePolubienie WHERE idGlosowania = %d;", $id_glosowania));
	$glosowanie_suwakowe = $wpdb -> query($wpdb -> prepare("SELECT * FROM GlosowanieZWartoscia WHERE idGlosowania = %d;", $id_glosowania));
	if ($glosowanie_opcje > 0)
		return "lista opcji";
	if ($glosowanie_polubienie > 0)
		return "polubienie";
	if ($glosowanie_suwakowe > 0)
		return "gwiazdka/suwak";
}

/** Wyświetla pytanie w głosowaniu id_glosowania. */
function pytanie($id_glosowania) {
	global $wpdb;
	$glosowanie = $wpdb -> get_row($wpdb -> prepare("SELECT pytanie FROM Glosowanie WHERE idGlosowania = %d;", (int) $id_glosowania));
	echo '<h4>'.$glosowanie -> pytanie.'</h4>';
}

/** Wyświetla liczbę głosów w głosowaniu $id_glosowania. */
function liczba_glosow($id_glosowania) {
	global $wpdb;
	echo 'Liczba głosów: ';
	$liczba_glosow = $wpdb -> query($wpdb -> prepare("SELECT idGlosu FROM Glos WHERE idGlosowania = %d;", (int)$id_glosowania));
	echo '<b>'.$liczba_glosow.'</b>';
	return $liczba_glosow;
}

/** Wyświetla wyniki głosowania typu wiele opcji. */
function wyniki_opcje($id_glosowania) {
	global $wpdb;
	if (isset($_GET['usun_glos'])) {
		$wpdb -> query($wpdb -> prepare("DELETE FROM GlosNaOpcje WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
		$wpdb -> query($wpdb -> prepare("DELETE FROM Glos WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
	}
	pytanie($id_glosowania);
	$ile_glosow = liczba_glosow($id_glosowania);
	$wyniki = $wpdb -> get_results($wpdb -> prepare("SELECT Opcja.tresc AS propozycja, Opcja.idGlosowania, COUNT(GlosNaOpcje.idGlosu) AS ile FROM (Opcja LEFT JOIN GlosNaOpcje ON Opcja.idOpcji = GlosNaOpcje.idOpcji) GROUP BY Opcja.tresc HAVING Opcja.idGlosowania = %d ORDER BY ile DESC;", $id_glosowania));
	echo '<br><br><table><tr><th>Opcja</th><th>Liczba głosów</th><th>% liczby głosów</th></tr>';
	foreach ($wyniki as $opcja)
		echo '<tr><td>'.$opcja -> propozycja.'</td><td>'.$opcja -> ile.'</td><td>'.($ile_glosow == 0 ? '' : (($opcja -> ile) * 100.0 / $ile_glosow)).'</td></tr>';
	echo '</table>';
}

/** Wyświetla wyniki głosowania typu polubienie. */
function wyniki_polubienie($id_glosowania) {
	global $wpdb;
	if (isset($_GET['usun_glos'])) {
		$wpdb -> query($wpdb -> prepare("DELETE FROM GlosLogiczny WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
		$wpdb -> query($wpdb -> prepare("DELETE FROM Glos WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
	}
	pytanie($id_glosowania);
	$ile_glosow = liczba_glosow($id_glosowania);
	$lubi = $wpdb -> query($wpdb -> prepare("SELECT Glos.idGlosu FROM Glos, GlosLogiczny WHERE GlosLogiczny.polubienie = 'lubi' AND Glos.idGlosu = GlosLogiczny.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	$nie_lubi = $ile_glosow - $lubi;
	echo '<br><br><table><tr><th>Polubienie</th><th>Liczba głosów</th><th>% liczby głosów</th></tr>
		<tr><td>Lubi</td><td>'.$lubi.'</td><td>'.($ile_glosow == 0 ? '' : ($lubi * 100.0 / $ile_glosow)).'</td></tr>
		<tr><td>Nie lubi</td><td>'.$nie_lubi.'</td><td>'.($ile_glosow == 0 ? '' : ($nie_lubi * 100.0 / $ile_glosow)).'</td></tr>
		</table>';
}

/** Wyświetla wyniki głosowania typu gwiazdkowe/suwakowe. */
function wyniki_wartosc($id_glosowania) {
	global $wpdb;
	if (isset($_GET['usun_glos'])) {
		$wpdb -> query($wpdb -> prepare("DELETE FROM GlosZWartoscia WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
		$wpdb -> query($wpdb -> prepare("DELETE FROM Glos WHERE idGlosu = %d;", (int)$_GET['usun_glos']));
	}
	pytanie($id_glosowania);
	$ile_glosow = liczba_glosow($id_glosowania);
	$srednia = $wpdb -> get_row($wpdb -> prepare("SELECT AVG(wartosc) AS srednia FROM Glos, GlosZWartoscia WHERE Glos.idGlosu = GlosZWartoscia.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	$zakres = $wpdb -> get_row($wpdb -> prepare("SELECT koniecZakresu FROM GlosowanieZWartoscia WHERE idGlosowania = %d", $id_glosowania));
	echo '<br><br>Średnia: <b>'.$srednia -> srednia.'</b> / '.$zakres -> koniecZakresu; 
}

/** Zwraca czy obecny użytkownik może głosować w głosowaniu $id_glosowania. */
function czy_dopuszczony($id_glosowania) {
	global $wpdb;
	if (uzytkownik() == 0)
		return false;
	$glosowanie = $wpdb -> get_row($wpdb -> prepare("SELECT grupaUprawnionychDoGlosowania AS uprawnieni FROM Glosowanie WHERE idGlosowania = %d;", $id_glosowania));
	$uzytkownik = $wpdb -> get_row($wpdb -> prepare("SELECT identifier, description FROM wp_wslusersprofiles WHERE user_id = %d;", uzytkownik()));
	if ($glosowanie -> uprawnieni != 0 && (int)$uzytkownik -> description != $glosowanie -> uprawnieni)
		return false;
	$warunki = $wpdb -> get_row($wpdb -> prepare("SELECT COUNT(*) AS ile FROM UprawnienieDoGlosowania WHERE idGlosowania = %d;", $id_glosowania));
	if ($warunki -> ile == 0)
		return true;
	$query = "SELECT GrupaUzytkownika.id FROM UprawnienieDoGlosowania, GrupaUzytkownika 
		WHERE UprawnienieDoGlosowania.warunek = GrupaUzytkownika.idGrupy
		AND GrupaUzytkownika.idUzytkownika = %d AND UprawnienieDoGlosowania.idGlosowania = %d;";
	$uprawnienie = $wpdb -> query($wpdb -> prepare($query, $uzytkownik -> identifier, $id_glosowania));
	return $uprawnienie > 0;
}

/** Wyświetla wyniki głosowania $id_glosowania. */
function wyswietl_wyniki_glosowania($id_glosowania) {
	$rodzaj = rodzaj_glosowania($id_glosowania);
	if ($rodzaj == "lista opcji")
		wyniki_opcje($id_glosowania);
	if ($rodzaj == "polubienie")
		wyniki_polubienie($id_glosowania);
	if ($rodzaj == "gwiazdka/suwak")
		wyniki_wartosc($id_glosowania);
}

/** Wyświetla panel głosowania w głosowaniu typu wiele opcji. */
function zaglosuj_opcje($id_glosowania) {
	global $wpdb;
	$zaznaczone = true;
	$glosowanie = $wpdb -> get_row($wpdb -> prepare("SELECT czyWieleOpcji FROM GlosowanieListaOpcji WHERE idGlosowania = %d;", $id_glosowania));
	if ($glosowanie -> czyWieleOpcji == "nie") {
		if (isset($_POST['zaglosuj'])) {
			$wpdb -> query($wpdb -> prepare("INSERT INTO Glos (idGlosowania, idUzytkownika) VALUES (%d, %d);", $id_glosowania, uzytkownik()));
			$glos = $wpdb -> get_row($wpdb -> prepare("SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d;", $id_glosowania, uzytkownik()));
			$wpdb -> query($wpdb -> prepare("INSERT INTO GlosNaOpcje (idGlosu, idOpcji) VALUES (%d, %s);", $glos -> idGlosu, $_POST['glos']));
		} else {
			echo '<form action = "'.get_permalink().'&glosowanie='.$id_glosowania.'" method = "post">';
			$opcje = $wpdb -> get_results($wpdb -> prepare("SELECT idOpcji, tresc FROM Opcja WHERE idGlosowania = %d", $id_glosowania));
			foreach ($opcje as $opcja) {
				if ($zaznaczone == true) {
					echo '<input type="radio" name="glos" value="'.$opcja -> idOpcji.'" checked /> '.$opcja -> tresc.'<br>';
					$zaznaczone = false;
				} else
					echo '<input type="radio" name="glos" value="'.$opcja -> idOpcji.'" /> '.$opcja -> tresc.'<br>';
			}
			echo '<br><input type="submit" value="Zapisz głos" name="zaglosuj" ><br>';
		}
	} else {
		if (isset($_POST['zaglosuj'])) {
			$opcje = $wpdb -> get_results($wpdb -> prepare("SELECT idOpcji FROM Opcja WHERE idGlosowania = %d", $id_glosowania));
			foreach ($opcje as $opcja)
				if (isset($_POST[$opcja -> idOpcji.""])) {
					$wpdb -> query($wpdb -> prepare("INSERT INTO Glos (idGlosowania, idUzytkownika) VALUES (%d, %d);", $id_glosowania, uzytkownik()));
					$glos = $wpdb -> get_row($wpdb -> prepare("SELECT MAX(idGlosu) AS id FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d;", $id_glosowania, uzytkownik()));
					$wpdb -> query($wpdb -> prepare("INSERT INTO GlosNaOpcje (idGlosu, idOpcji) VALUES (%d, %s);", $glos -> id, $opcja -> idOpcji));
				}
		} else {
			echo '<form action = "'.get_permalink().'&glosowanie='.$id_glosowania.'" method = "post">';
			$opcje = $wpdb -> get_results($wpdb -> prepare("SELECT idOpcji, tresc FROM Opcja WHERE idGlosowania = %d", $id_glosowania));
			foreach ($opcje as $opcja)
					echo '<input type="checkbox" name="'.$opcja -> idOpcji.'" /> '.$opcja -> tresc.'<br>';
			echo '<br><input type="submit" value="Zapisz głos" name="zaglosuj" ><br></form>';
		}
	}
}

/** Wyświetla panel głosowania w głosowaniu typu polubienie. */
function zaglosuj_polubienie($id_glosowania) {
	global $wpdb;
	if (isset($_POST['zaglosuj'])) {
		$wpdb -> query($wpdb -> prepare("INSERT INTO Glos (idGlosowania, idUzytkownika) VALUES (%d, %d);", $id_glosowania, $uzytkownik()));
		$glos = $wpdb -> get_row($wpdb -> prepare("SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d;", $id_glosowania, $uzytkownik()));
		$wpdb -> query($wpdb -> prepare("INSERT INTO GlosLogiczny (idGlosu, polubienie) VALUES (%d, %s);", $glos -> idGlosu, $_POST['glos']));
	} else {
		echo '<form action = "'.get_permalink().'&glosowanie='.$id_glosowania.'" method = "post">
			<input type="radio" name="glos" value="lubi" checked /> Lubię to<br>
			<input type="radio" name="glos" value="nie_lubi" /> Nie lubię<br><br>
			<input type="submit" value="Zapisz głos" name="zaglosuj" ><br></form>';
	}
}

/** Wyświetla panel głosowania w głosowaniu typu gwiazdkowe / suwakowe. */
function zaglosuj_wartosc($id_glosowania) {
	global $wpdb;
	if (isset($_POST['zaglosuj'])) {
		$wpdb -> query($wpdb -> prepare("INSERT INTO Glos (idGlosowania, idUzytkownika) VALUES (%d, %d);", $id_glosowania, uzytkownik()));
		$glos = $wpdb -> get_row($wpdb -> prepare("SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d;", $id_glosowania, uzytkownik()));
		$wpdb -> query($wpdb -> prepare("INSERT INTO GlosZWartoscia (idGlosu, wartosc) VALUES (%d, %s);", $glos -> idGlosu, $_POST['glos']));
	} else {
		$prezentacja = $wpdb -> get_row($wpdb -> prepare("SELECT prezentacja, koniecZakresu FROM GlosowanieZWartoscia WHERE idGlosowania = %d;", $id_glosowania));
		if ($prezentacja -> prezentacja == "gwiazdki") {
			echo '<script type="text/javascript" src="wp-content/plugins/glosowania/gwiazdki/prototype.lite.js"></script> 
				<script type="text/javascript" src="wp-content/plugins/glosowania/gwiazdki/stars.js"></script>  
				<link rel="stylesheet" href="wp-content/plugins/glosowania/gwiazdki/stars.css" type="text/css" /> 
				<form action = "'.get_permalink().'&glosowanie='.$id_glosowania.'" method="POST" >
				<script> new Starry("glos", {name:"glos", startAt:1, maxLength:5, showNull:false}) </script>
				<input type="submit" value="Zapisz głos" name="zaglosuj" ></form>';
		} else {
			echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
				<script src="wp-content/plugins/glosowania/suwak/js/simple-slider.js"></script>
				<link href="wp-content/plugins/glosowania/suwak/css/simple-slider.css" rel="stylesheet" type="text/css" />
				<form action = "'.get_permalink().'&glosowanie='.$id_glosowania.'" method="POST"> 
				<input id="suwak" type="text" data-slider="true" data-slider-range="1,'.$prezentacja -> koniecZakresu.'" data-slider-step="1" name="glos">
				<br><br><input type="submit" value="Zapisz głos" name="zaglosuj" ></form>
				<script>
					$("[data-slider]")
						.each(function () {
							var input = $(this);
							$("<span>")
							.addClass("output")
							.insertAfter($(this));
						})
						.bind("slider:ready slider:changed", function (event, data) {
							$(this)
							.nextAll(".output:first")
							.html(data.value.toFixed(3));
						});
				</script>';
		}
	}
}

/** Wyświetla panel głosowania. */
function zaglosuj($id_glosowania) {
	$rodzaj = rodzaj_glosowania($id_glosowania);
	if ($rodzaj == "lista opcji")
		zaglosuj_opcje($id_glosowania);
	if ($rodzaj == "polubienie")
		zaglosuj_polubienie($id_glosowania);
	if ($rodzaj == "gwiazdka/suwak")
		zaglosuj_wartosc($id_glosowania);
}

function wyswietl_glosowanie($id_glosowania) {
	if(isset($_POST['zaglosuj']))
		zaglosuj($id_glosowania);
	global $wpdb;
	$glos = $wpdb -> query($wpdb -> prepare("SELECT * FROM Glos WHERE idUzytkownika = %d AND idGlosowania = %d;", uzytkownik(), $id_glosowania));
	$aktywne = $wpdb -> query($wpdb -> prepare("SELECT * FROM Glosowanie WHERE dataWygasniecia > NOW() AND idGlosowania = %d;", $id_glosowania));
	$glosowanie = $wpdb -> get_row($wpdb -> prepare("SELECT pytanie, czyWynikiWTrakcieGlosowania FROM Glosowanie WHERE idGlosowania = %d;", $id_glosowania));
	$link = get_permalink();
	if ($aktywne == 0)
		wyswietl_wyniki_glosowania($id_glosowania);
	else {
		if ($glos > 0) {
			if ($glosowanie -> czyWynikiWTrakcieGlosowania == 'tak')
				wyswietl_wyniki_glosowania($id_glosowania);
			else
				echo '<h4>'.$glosowanie -> pytanie.'</h4>';
			echo '<br><br>Oddałeś swój głos w tym głosowaniu.<br>
				<a href = "'.$link.'&usun_glos&glosowanie='.$id_glosowania.'"><button>Usuń głos</button></a>';
		} else {
			echo '<h4>'.$glosowanie -> pytanie.'</h4>';
			zaglosuj($id_glosowania);
		}
	}
}

function usun_glosy_w_glosowaniu($id_glosowania) {
	global $wpdb;
	$wpdb -> query($wpdb -> prepare("DELETE FROM GlosNaOpcje WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d);", $id_glosowania, uzytkownik()));
	$wpdb -> query($wpdb -> prepare("DELETE FROM GlosLogiczny WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d);", $id_glosowania, uzytkownik()));
	$wpdb -> query($wpdb -> prepare("DELETE FROM GlosZWartoscia WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d);", $id_glosowania, uzytkownik()));
	$wpdb -> query($wpdb -> prepare("DELETE FROM Glos WHERE idGlosowania = %d AND idUzytkownika = %d;", $id_glosowania, uzytkownik()));
	echo '<br>Głos został usunięty!<br><br>';
}

function glosowania_shortcode($atrybuty) {
	global $wpdb;
	if (isset($_GET['usun_glos']))
		usun_glosy_w_glosowaniu((int)$_GET['glosowanie']);
	if (isset($_GET['glosowanie'])) {
		$id_glosowania = (int)$_GET['glosowanie'];
		wyswietl_glosowanie($id_glosowania);
		return;
	}
    $tab = shortcode_atts(array('id' => 0), $arybuty);
	if ($tab['id'] == 0) {
		$glosowania = $wpdb -> get_results("SELECT * FROM Glosowanie ORDER BY dataDodania DESC;");
		echo '<table><tr><th>Głosowanie</th><th>Stan</th><th>Głos</th></tr>';
		foreach($glosowania as $glosowanie)
			if (czy_dopuszczony($glosowanie -> idGlosowania)) {
				$glos = $wpdb -> query($wpdb -> prepare("SELECT * FROM Glos WHERE idUzytkownika = %d AND idGlosowania = %d;", uzytkownik(), $glosowanie -> idGlosowania));
				$aktywne = $wpdb -> query($wpdb -> prepare("SELECT * FROM Glosowanie WHERE dataWygasniecia > NOW() AND idGlosowania = %d;", $glosowanie -> idGlosowania));
				echo '<tr><td><a href = '.get_permalink().'&glosowanie='.$glosowanie -> idGlosowania.'>'.$glosowanie -> pytanie.'</a></td>
					<td>'.($aktywne == 0 ? 'Zakończone' : 'Trwa').'</td><td>'.($glos == 0 ? ($aktywne == 0 ? '' : 'Głosuj teraz!') : 'Zagłosowałeś').'</tr>';
			}			
		echo '</table>';
	} else {
		$id_glosowania = (int)$tab['id'];
		$ile = $wpdb -> query($wpdb -> prepare("SELECT idGlosowania FROM Glosowanie WHERE idGlosowania = %d;", $id_glosowania));
		if ($ile != 1)
			echo 'Błędny parametr id = '.$id_glosowania;
		else if (czy_dopuszczony($id_glosowania))
			wyswietl_glosowanie($id_glosowania);
	}
}

function dodaj_menu() {
    add_menu_page( 'Głosowania', 'Głosowania', 'manage_options', 'glosowania', 'glosowania', '', 90); 
	add_submenu_page( 'glosowania', 'Dodaj głosowanie', 'Dodaj głosowanie', 'manage_options', 'dodaj_glosowania', 'dodaj_glosowanie');
}

/** Pokazuje głosy w panelu administratora w głosowaniu typu wiele opcji. */ 
function pokaz_glosy_opcje($id_glosowania) {
	global $wpdb;
	$wynik = $wpdb -> get_results($wpdb -> prepare("SELECT Glos.idGlosu AS idGlosu, idOpcji, idUzytkownika FROM GlosNaOpcje, Glos WHERE GlosNaOpcje.idGlosu = Glos.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	echo '<table><tr><th>Opcja</th><th>Imię</th><th>Nazwisko</th><th>Usuń</th></tr>';
	foreach ($wynik as $glos) {
		$opcja = $wpdb -> get_row($wpdb -> prepare("SELECT tresc FROM Opcja WHERE idOpcji = %d;",$glos -> idOpcji));
		$uzytkownik = $wpdb -> get_row($wpdb -> prepare("SELECT firstname, lastname FROM wp_wslusersprofiles WHERE user_id = %d;", $glos -> idUzytkownika));
		echo '<tr><td>'.$opcja -> tresc.'</td><td>'.$uzytkownik -> firstname.'</td><td>'.$uzytkownik -> lastname.'</td>
			<td><a href="admin.php?page=glosowania&pokaz_glosy='.$id_glosowania.'&usun_glos='.$glos -> idGlosu.'">'.Usuń.'</a></td></tr>';
	}
	echo '</table>';
}

/** Pokazuje głosy w panelu administratora w głosowaniu typu polubienie. */ 
function pokaz_glosy_polubienie($id_glosowania) {
	global $wpdb;
	$wynik = $wpdb -> get_results($wpdb -> prepare("SELECT Glos.idGlosu AS idGlosu, polubienie, idUzytkownika FROM GlosLogiczny, Glos WHERE GlosLogiczny.idGlosu = Glos.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	echo '<table><tr><th>Lubi / nie lubi</th><th>Imię</th><th>Nazwisko</th><th>Usuń</th></tr>';
	foreach ($wynik as $glos) {
		$uzytkownik = $wpdb -> get_row($wpdb -> prepare("SELECT firstname, lastname FROM wp_wslusersprofiles WHERE user_id = %d;", $glos -> idUzytkownika));
		echo '<tr><td>'.$glos -> polubienie.'</td><td>'.$uzytkownik -> firstname.'</td><td>'.$uzytkownik -> lastname.'</td>
			<td><a href="admin.php?page=glosowania&pokaz_glosy='.$id_glosowania.'&usun_glos='.$glos -> idGlosu.'">'.Usuń.'</a></td></tr>';
	}
	echo '</table>';
}

/** Pokazuje głosy w panelu administratora w głosowaniu typu suwakowe/gwiazdkowe. */ 
function pokaz_glosy_wartosc($id_glosowania) {
	global $wpdb;
	$wynik = $wpdb -> get_results($wpdb -> prepare("SELECT Glos.idGlosu AS idGlosu, wartosc, idUzytkownika FROM GlosZWartoscia, Glos WHERE GlosZWartoscia.idGlosu = Glos.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	echo '<table><tr><th>Wartość</th><th>Imię</th><th>Nazwisko</th><th>Usuń</th></tr>';
	foreach ($wynik as $glos) {
		$uzytkownik = $wpdb -> get_row($wpdb -> prepare("SELECT firstname, lastname FROM wp_wslusersprofiles WHERE user_id = %d;", $glos -> idUzytkownika));
		echo '<tr><td>'.$glos -> wartosc.'</td><td>'.$uzytkownik -> firstname.'</td><td>'.$uzytkownik -> lastname.'</td>
			<td><a href="admin.php?page=glosowania&pokaz_glosy='.$id_glosowania.'&usun_glos='.$glos -> idGlosu.'">'.Usuń.'</a></td></tr>';
	}
}

/** Generowanie raportu do druku głosowania typu wiele opcji. */ 
function do_druku_wyniki_opcje($id_glosowania, $nr) {
	global $wpdb;
	$wyniki = $wpdb -> get_results($wpdb -> prepare("SELECT Opcja.tresc AS propozycja, Opcja.idGlosowania, COUNT(GlosNaOpcje.idGlosu) AS ile FROM (Opcja LEFT JOIN GlosNaOpcje ON Opcja.idOpcji = GlosNaOpcje.idOpcji) GROUP BY Opcja.tresc HAVING Opcja.idGlosowania = '%d';", $id_glosowania));
	$nr_opcji = 0;
	foreach ($wyniki as $opcja) {
		echo '<input type="hidden" name ="'.$nr.'opcja'.$nr_opcji.'propozycja" value="'.$opcja -> propozycja.'" />';
		echo '<input type="hidden" name ="'.$nr.'opcja'.$nr_opcji.'ile" value="'.$opcja -> ile.'" />';
		$nr_opcji++;
	}
	echo '<input type="hidden" name ="'.$nr.'ile_opcji" value="'.$nr_opcji.'" />';
}

/** Generowanie raportu do druku głosowania typu polubienie. */ 
function do_druku_wyniki_polubienie($id_glosowania, $nr) {
	global $wpdb;
	$lubi = $wpdb -> query($wpdb -> prepare("SELECT idGlosu FROM Glos, GlosLogiczny WHERE GlosLogiczny.polubienie = 'lubi' AND Glos.idGlosu = GlosLogiczny.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	echo '<input type="hidden" name ="'.$nr.'lubi" value="'.$lubi.'" />';
}

/** Generowanie raportu do druku głosowania typu gwiazdkowe/suwakowe. */ 
function do_druku_wyniki_wartosc($id_glosowania, $nr) {
	global $wpdb;
	$srednia = $wpdb -> get_row($wpdb -> prepare("SELECT AVG(wartosc) AS srednia FROM Glos, GlosZWartoscia WHERE Glos.idGlosu = GlosZWartoscia.idGlosu AND Glos.idGlosowania = %d;", $id_glosowania));
	$zakres = $wpdb -> get_row($wpdb -> prepare("SELECT koniecZakresu FROM GlosowanieZWartoscia WHERE idGlosowania = %d", $id_glosowania));
	echo '<input type="hidden" name ="'.$nr.'srednia" value="'.$srednia -> srednia.'" />
		<input type="hidden" name ="'.$nr.'zakres" value="'.$zakres -> koniecZakresu.'" />';
}

/** Generowanie raportu. */ 
function generuj_raport() {
	global $wpdb;
	echo '<h3>Raport</h3><br>';
	$glosowania = $wpdb -> get_results("SELECT idGlosowania, pytanie FROM Glosowanie;");
	foreach ($glosowania as $glosowanie)
		if (isset($_POST[($glosowanie -> idGlosowania).""]) || isset($_POST["raport_wszystkie"])) {
			$rodzaj = rodzaj_glosowania($glosowanie -> idGlosowania);
			if ($rodzaj == "lista opcji")
				wyniki_opcje($glosowanie -> idGlosowania);
			if ($rodzaj == "polubienie")
				wyniki_polubienie($glosowanie -> idGlosowania);
			if ($rodzaj == "gwiazdka/suwak")
				wyniki_wartosc($glosowanie -> idGlosowania);
			echo '<br><br><hr />';
		}
	
	$link = get_home_url().'/wp-content/plugins/glosowania/raport.php';
	echo '<form action = "'.$link.'" method = "post">';
	$nr = 0;
	foreach ($glosowania as $glosowanie)
		if (isset($_POST[($glosowanie -> idGlosowania).""]) || isset($_POST["raport_wszystkie"])) {
			$rodzaj = rodzaj_glosowania($glosowanie -> idGlosowania);
			$ile_glosow = $wpdb -> query($wpdb -> prepare("SELECT idGlosu FROM Glos WHERE idGlosowania = %d;", $glosowanie -> idGlosowania));
			echo '<input type="hidden" name="'.$nr.'" value = "'.$rodzaj.'" />';
			echo '<input type="hidden" name="'.$nr.'ile_glosow" value = "'.$ile_glosow.'" />'; 
			echo '<input type="hidden" name="'.$nr.'pytanie" value = "'.$glosowanie -> pytanie.'" />';
			if ($rodzaj == "lista opcji")
				do_druku_wyniki_opcje($glosowanie -> idGlosowania, $nr);
			if ($rodzaj == "polubienie")
				do_druku_wyniki_polubienie($glosowanie -> idGlosowania, $nr);
			if ($rodzaj == "gwiazdka/suwak")
				do_druku_wyniki_wartosc($glosowanie -> idGlosowania, $nr);
			$nr++;
		}
	echo '<input type="hidden" name="ile_glosowan" value="'.$nr.'" />
		<input type="submit" value="Generuj raport do druku" name="raport_do_druku" class="button-primary" ><br></form>';
}

function usun_wybrane_glosowania() {
	global $wpdb;
	$glosowania = $wpdb -> get_results("SELECT idGlosowania FROM Glosowanie;");
	foreach ($glosowania as $glosowanie)
		if (isset($_POST[($glosowanie -> idGlosowania).""])) {
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosowanieListaOpcji WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosowaniePolubienie WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosowanieZWartoscia WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM UprawnienieDoGlosowania WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosNaOpcje WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d)", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosLogiczny WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d)", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM GlosZWartoscia WHERE idGlosu in (SELECT idGlosu FROM Glos WHERE idGlosowania = %d)", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM Glos WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM Opcja WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
			$wpdb -> query($wpdb -> prepare("DELETE FROM Glosowanie WHERE idGlosowania = %d", $glosowanie -> idGlosowania));
		}
}

/* Menu głosowania w panelu administratora. */
function glosowania() {
	global $wpdb;
    echo '<h2>Głosowania</h2>';
	if (isset($_POST['raport_do_druku'])) {
		generuj_raport_do_druku();
		return;
	}
	if (isset($_POST['raport']) || isset($_POST["raport_wszystkie"])) {
		generuj_raport();
		return;
	}
	if (isset($_GET['pokaz_glosy'])) {
		$rodzaj = rodzaj_glosowania($_GET['pokaz_glosy']);
		wyswietl_wyniki_glosowania((int)$_GET['pokaz_glosy']);
		echo '<h3>Głosy</h3>';
		if ($rodzaj == "lista opcji")
			pokaz_glosy_opcje($_GET['pokaz_glosy']);
		if ($rodzaj == "polubienie")
			pokaz_glosy_polubienie($_GET['pokaz_glosy']);
		if ($rodzaj == "gwiazdka/suwak")
			pokaz_glosy_wartosc($_GET['pokaz_glosy']);
		return;
	}
	if (isset($_GET['zakoncz']))
		$wpdb -> query($wpdb -> prepare("UPDATE Glosowanie SET dataWygasniecia = NOW() WHERE idGlosowania = %d;", $_GET['zakoncz'])); 
	if (isset($_POST['usun']))
		usun_wybrane_glosowania();
	$wynik = $wpdb -> get_results("SELECT * FROM Glosowanie WHERE dataWygasniecia > NOW() + 2 ORDER BY dataDodania DESC;");
	echo '<form action = "admin.php?page=glosowania" method = "post">
		</table><br><input type="submit" value="Generuj raport" name="raport" class="button-primary"> 
		<input type="submit" value="Generuj raport ze wszystkich głosowań" name="raport_wszystkie" class="button-primary"> 
		<input type="submit" value="Usuń" name="usun" class="button-primary"><br>
		<h4>Trwające głosowania</h4>
		<table><tr><th></th><th>Id</th><th>Głosowanie</th><th>Rodzaj</th><th>Data dodania</th><th>Data zakończenia</th><th>Zakończ</th></tr>';
	foreach ($wynik as $glosowanie) {
		echo '<tr><td><input type="checkbox" name="'.$glosowanie -> idGlosowania.'" /></td><td>'.$glosowanie -> idGlosowania.'</td>
			<td><a href="admin.php?page=glosowania&pokaz_glosy='.$glosowanie -> idGlosowania.'">'.$glosowanie -> pytanie.'</a></td>
			<td>'.rodzaj_glosowania($glosowanie -> idGlosowania).'</td>
			<td>'.$glosowanie -> dataDodania.'</td><td>'.$glosowanie -> dataWygasniecia.'</td>
			<td><a href="admin.php?page=glosowania&zakoncz='.$glosowanie -> idGlosowania.'" >Zakończ głosowanie</a></td></tr>';
	}
	echo '</table><br><h4>Zakończone głosowania</h4>';
	$wynik = $wpdb -> get_results("SELECT * FROM Glosowanie WHERE dataWygasniecia < NOW() + 2 ORDER BY dataDodania DESC;");
	echo '<table><tr><th></th><th>Id</th><th>Głosowanie</th><th>Rodzaj</th><th>Data dodania</th><th>Data zakończenia</th></tr>';
	foreach ($wynik as $glosowanie) {
		echo '<tr><td><input type="checkbox" name="'.$glosowanie -> idGlosowania.'" /></td><td>'.$glosowanie -> idGlosowania.'</td>
		<td><a href="admin.php?page=glosowania&pokaz_glosy='.$glosowanie -> idGlosowania.'">'.$glosowanie -> pytanie.'</a></td>
		<td>'.rodzaj_glosowania($glosowanie -> idGlosowania).'</td>
		<td>'.$glosowanie -> dataDodania.'</td><td>'.$glosowanie -> dataWygasniecia.'</td></tr>';
	}
	echo '</table><br><input type="submit" value="Generuj raport" name="raport" class="button-primary"> 
		<input type="submit" value="Generuj raport ze wszystkich głosowań" name="raport_wszystkie" class="button-primary"> 
		<input type="submit" value="Usuń" name="usun" class="button-primary"></form>';
}

function zapisz_glosowanie() {
	global $wpdb;
	$data = $_POST['5'].'-'.$_POST['6'].'-'.$_POST['7'].' '.$_POST['8'].':'.$_POST['9'].':00';
	$sql = 
		"INSERT INTO Glosowanie 
		(dataDodania, dataWygasniecia, czyWynikiWTrakcieGlosowania, grupaUprawnionychDoGlosowania, pytanie)
		VALUES (NOW(), '".$data."', '".$_POST['4']."', ".(int)$_POST['2'].", '".$_POST['1']."');";
	$wpdb -> query($sql);
	$ret = $wpdb -> get_row("SELECT MAX(idGlosowania) AS wynik FROM Glosowanie");
	if ($_POST['3'] == "0") {
		$wpdb -> query($wpdb -> prepare("INSERT INTO GlosowanieListaOpcji VALUES(%d, %s);", ($ret -> wynik), $_POST['11']));
		for ($i = 0; $i < (int)$_POST['10']; ++$i)
			$wpdb -> query($wpdb -> prepare("INSERT INTO Opcja (tresc, idGlosowania) VALUES(%s, %d);", $_POST[(100 + $i).""], $ret -> wynik));
	} else if ($_POST['3'] == "1")
		$wpdb -> query("INSERT INTO GlosowaniePolubienie VALUES(".($ret -> wynik).");");
	else {
		if ($_POST['12'] == "gwiazdki")
			$wpdb -> query($wpdb -> prepare("INSERT INTO GlosowanieZWartoscia (idGlosowania, prezentacja) VALUES(%d, %s);", $ret -> wynik, $_POST['12']));
		else
			$wpdb -> query($wpdb -> prepare("INSERT INTO GlosowanieZWartoscia VALUES(%d, %s, %d);", $ret -> wynik, $_POST['12'], (int)$_POST['13']));
	}
	for ($i = 0; $i <= $_POST["ile_grup"]; ++$i)
		if (isset($_POST["grupa".$i]))
			$wpdb -> query($wpdb -> prepare("INSERT INTO UprawnienieDoGlosowania (idGlosowania, rodzajUprawnienia, warunek) VALUES (%d, 1, %d)", $ret -> wynik, $i));
	echo 'Głosowanie zostało zapisane!';
}

/** Dodanie głosowania przez panel administratora. */
function dodaj_glosowanie() {
	global $wpdb;
	echo '<h2>Dodaj głosowanie</h2><br>';
	echo '<table class="form-table">';
	if (isset($_POST['nazwy_opcji']) || isset($_POST['suwakowe']))
		zapisz_glosowanie();
	else if (isset($_POST['opcje'])) {
		echo '<form action = "admin.php?page=dodaj_glosowania" method = "post">';
		for ($i = 1; $i <= 13; ++$i)
			echo '<input type="hidden" name="' . $i . '" value="' . $_POST[$i.""] . '" />';
		for ($i = 0; $i <= $_POST["ile_grup"]; ++$i)
			if (isset($_POST["grupa".$i]))
				echo '<input type="hidden" name="grupa'.$i.'" value="' .$i. '" />';
		echo '<input type="hidden" name="ile_grup" value="'.$_POST["ile_grup"].'" />
			<tr valign="top"><th>Opcje odpowiedzi:</th><td scope="row">';
		for ($i = 0; $i < $_POST['10']; ++$i)
			echo '<input type="text" name="' . ($i + 100) . '" size = "40"/><br>';
		echo '</td></tr></table><br><input type="submit" value="Zapisz" name="nazwy_opcji" size = "8" class="button-primary"></form>';
	} else if (isset($_POST['rodzaje'])) {
		if ($_POST['3'] == "1")
			zapisz_glosowanie();
		else {
			echo '<form action = "admin.php?page=dodaj_glosowania" method = "post">';
			for ($i = 1; $i < 10; ++$i)
				echo '<input type="hidden" name="' . $i . '" value="' . $_POST[$i.""] . '" />';
			for ($i = 0; $i <= $_POST["ile_grup"]; ++$i)
				if (isset($_POST["grupa".$i]))
					echo '<input type="hidden" name="grupa'.$i.'" value="'.$i. '" />';
			echo '<input type="hidden" name="ile_grup" value="'.$_POST["ile_grup"].'" />';
			if ($_POST['3'] == "0") {
				echo '<tr valign="top"><th>Liczba opcji odpowiedzi na pytanie:</th>
					<td scope="row"><input type="text" name="10" size = "5" value="4"/></td></tr>
					<tr valign="top"><th>Czy użytkownik może zaznaczyć wiele opcji?</th>
					<td scope="row"><input type="radio" name="11" value="tak" checked /> Tak<br>
					<input type="radio" name="11" value="nie" /> Nie</td></tr></table>
					<br><input type="submit" value="Dalej" name="opcje" size = "8" class="button-primary"></form>';
			} else {
				echo '<tr valign="top"><th>Ustal sposób ustawienia wyniku:</th>
					<td scope="row"><select name="12"><option type="radio" value="gwiazdki" > Gwiazdkowe</option>
					<option value="suwak" > Suwakowe</option></select></td></tr>
					<tr valign="top"><th>Jeżeli wybierasz głosowanie suwakowe to określ wielkość suwaka:</th>
					<td scope="row"><input type="text" name="13" size = "5" value="5"/></td></tr></table>
					<br><input type="submit" value="Zapisz" name="suwakowe" size = "8" class="button-primary"></form>';
			}
		}
	} else {
		echo '<form action = "admin.php?page=dodaj_glosowania" method = "post">
			<tr valign="top"><th>Tytuł głosowania / pytanie:</th>
			<td scope="row"><input type="text" name="1" size = "70"/></td></tr>
			<tr valign="top"><th>Uprawnieni do głosowania: </th>
			<td scopr="row"><input type="radio" name="2" value="0" checked /> Wszyscy użytkownicy<br>
			<input type="radio" name="2" value="1" /> Tylko studenci<br>
			<input type="radio" name="2" value="2" /> Tylko pracownicy<br></td></tr>
			<tr valign="top"><th>Grupy użytkowników: <br></th>
			<td scope="row"><i>Brak zaznaczenia oznacza dopuszczenie wszystkich użytkowników z punktu powyżej.</i><br>';
		$id = $wpdb -> get_row($wpdb -> prepare("SELECT identifier FROM wp_wslusersprofiles WHERE user_id = %d", uzytkownik()));
		$grupy = $wpdb -> get_results($wpdb -> prepare("SELECT Grupa.id id, Grupa.nazwa nazwa FROM GrupaUzytkownika, Grupa WHERE GrupaUzytkownika.idGrupy = Grupa.id AND GrupaUzytkownika.idUzytkownika = %d ORDER BY Grupa.id", $id -> identifier));
		$ile_grup = 0;
		foreach ($grupy as $grupa) {
			echo '<br><input type="checkbox" name="grupa'.$grupa -> id.'" /> '.$grupa -> nazwa;
			if ($grupa -> id > $ile_grup)
				$ile_grup = $grupa -> id;
		}
		echo '</td><input type="hidden" name="ile_grup" value="'.$ile_grup.'" >
			<tr valign="top"><th>Rodzaj głosowania:</th>
			<td scope="row"><input type="radio" name="3" value="0" checked /> Głosowanie z opcjami do wyboru<br>
			<input type="radio" name="3" value="1" /> Głosowanie lubię / nie lubię<br>
			<input type="radio" name="3" value="2" /> Głosowanie z suwakiem / gwiazdkami</td>
			<tr valign="top"><th>Wyświetlanie wyników głosowania:</th>
			<td><input type="radio" name="4" value="tak" checked /> Wyniki wyświetlane w trakcie głosowania<br>
			<input type="radio" name="4" value="nie" /> Wyniki wyświetlane dopiero po zakończeniu głosowania</td>
			<tr valign="top"><th>Koniec głosowania:</th>
			<td><i>Wpisz cyfry w odpowiednie pola.</i><br>
			Rok:  <input type="text" name="5" size = "5" value = "2020"/><br>
			Miesiąc:  <input type="text" name="6" size = "5" value = "1"/><br>
			Dzień:  <input type="text" name="7" size = "5" value = "1"/><br>
			Czas:  <input type="text" name="8" size = "5" value = "20"/> h: <input type="text" name="9" size = "5" value = "14"/>m</td></table>
			<br><input type="submit" value="Dalej" name="rodzaje" size = "8" class="button-primary"></form>';
	}
}	

?>