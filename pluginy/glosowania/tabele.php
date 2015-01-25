<?php

function install_plugin() {
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE Glosowanie (
        idGlosowania INT AUTO_INCREMENT,
		dataDodania DATETIME NOT NULL,
		dataWygasniecia DATETIME NOT NULL,
		czyWynikiWTrakcieGlosowania ENUM('tak', 'nie') NOT NULL,
		grupaUprawnionychDoGlosowania INT NOT NULL,
		pytanie VARCHAR(100) NOT NULL,
		idTworcy INT NOT NULL,
		PRIMARY KEY (idGlosowania)
    );";
    dbDelta($sql);

	$sql = "CREATE TABLE GlosowanieListaOpcji (
		idGlosowania INT,
		czyWieleOpcji ENUM('tak', 'nie') NOT NULL,
		PRIMARY KEY (idGlosowania),
		FOREIGN KEY (idGlosowania) REFERENCES Glosowanie(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE GlosowaniePolubienie (
		idGlosowania INT,
		PRIMARY KEY (idGlosowania),
		FOREIGN KEY (idGlosowania) REFERENCES Glosowanie(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE GlosowanieZWartoscia (
		idGlosowania INT,
		prezentacja ENUM('gwiazdki', 'suwak') NOT NULL,
		koniecZakresu INT NOT NULL DEFAULT 5,
		PRIMARY KEY (idGlosowania),
		FOREIGN KEY (idGlosowania) REFERENCES Glosowanie(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE UprawnienieDoGlosowania (
		idUprawnienia INT AUTO_INCREMENT,
		idGlosowania INT NOT NULL,
		rodzajUprawnienia INT NOT NULL,
		warunek INT NOT NULL,
		PRIMARY KEY (idUprawnienia),
		FOREIGN KEY (idGlosowania) REFERENCES Glosowanie(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE Opcja (
		idOpcji INT AUTO_INCREMENT,
		tresc VARCHAR(50) NOT NULL,
		idGlosowania INT NOT NULL,
		PRIMARY KEY (idOpcji),
		FOREIGN KEY (idGlosowania) REFERENCES GlosowanieListaOpcji(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE Glos (
		idGlosu INT AUTO_INCREMENT,
		idGlosowania INT NOT NULL,
		idUzytkownika INT NOT NULL,
		PRIMARY KEY (idGlosu),
		FOREIGN KEY (idGlosowania) REFERENCES Glosowanie(idGlosowania)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE GlosNaOpcje (
		idGlosu INT,
		idOpcji INT NOT NULL,
		PRIMARY KEY (idGlosu),
		FOREIGN KEY (idGlosu) REFERENCES Glos(idGlosu),
		FOREIGN KEY (idOpcji) REFERENCES Opcja(idOpcji)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE GlosLogiczny (
		idGlosu INT,
		polubienie ENUM('lubi', 'nie_lubi') NOT NULL,
		PRIMARY KEY (idGlosu),
		FOREIGN KEY (idGlosu) REFERENCES Glos(idGlosu)
	);";
	dbDelta($sql);
	
	$sql = "CREATE TABLE GlosZWartoscia (
		idGlosu INT,
		wartosc INT NOT NULL,
		PRIMARY KEY (idGlosu),
		FOREIGN KEY (idGlosu) REFERENCES Glos(idGlosu)
	);";
	dbDelta($sql);	
	
	global $wpdb;
	$wpdb -> query("CREATE TRIGGER liczba_glosow BEFORE INSERT ON GlosZWartoscia FOR EACH ROW SET @liczba = @liczba + NEW.wartosc;");
}

?>