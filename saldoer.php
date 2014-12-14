<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$liste = $_REQUEST['liste'];
$bruker = $_REQUEST['bruker'];
if($bruker && !is_numeric($bruker)) die('Feil: bruker må være numerisk!');
if(!is_numeric($liste)) $liste = 0;

$dato = date("Y-m-d");
?>

<style type="text/css">
.Innskudd {
background-color:#70FF60;
text-align:right;
}
.Krysseliste {
background-color:red;
text-align:right;
}
.BSF {
background-color:#CC99FF;
text-align:right;
}
.infocol {
padding-left: 4px;
white-space: nowrap;
}
</style>

<h3>Vis/oppdater saldoer</h3>
<a href='index.php'>Tilbake</a><br />
<form name='krysseliste' action='saldoer.php' method='post'>
Velg liste: <select name='liste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
</select>
<input name='velgliste' type='submit' value='Velg' />

<?
echo "Valgt krysseliste: ".$lister[$liste];

// Husk hvilken bruker vi jobber med...
if(is_numeric($bruker)) {

	echo "<input type='hidden' name='bruker' value='$bruker' />";
	if($_POST['flyttbruker'] == "Flytt") {
		// Flytt bruker til ny liste
		$nyliste = $_POST['nyliste'];
		if(is_numeric($nyliste)) {
//			$query = "UPDATE personer SET liste = $nyliste WHERE id = $bruker";
			$result = pg_query_params("UPDATE personer SET liste = $1 WHERE id = $2", array($nyliste, $bruker));
//			if(pg_query($query)) $liste = $nyliste;
			if($result) $liste = $nyliste;
			else echo "Noe gikk galt: ".pg_last_error();
		}
	}
}
//$query = "SELECT * FROM personer WHERE liste = $liste AND slettet = FALSE ORDER BY kallenavn";
$result = pg_query_params("SELECT * FROM personer WHERE liste = $1 AND slettet = FALSE ORDER BY kallenavn", array($liste));
//$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
if(!$result) die('Noe gikk galt: '.pg_last_error());
echo "<table><tr><td valign='top'><select name='bruker' size='30'>";

while ($row = pg_fetch_array($result)) {
	echo "<option value='".$row['id']."'>".$row['kallenavn']."</option>";
}
echo "</select><br />
<input type='submit' name='velgbruker' value='Velg' /></td><td valign='top'>";
if(is_numeric($bruker)) {
echo "<a href='bruker.php?bruker=$bruker'><b>(Rediger bruker)</b></a>";
	if($_POST['nysvartegrense']) {
		// Sett ny svartegrense
		$svartegrense = $_POST['svartegrense'];
		if($_POST['nysvartegrense'] == "Slett") $svartegrense = 0;
		if(!is_numeric($svartegrense)) echo "Svartegrense må bestå av tall...";
		else {
//			$query = "UPDATE personer SET svartegrense = $svartegrense WHERE id = $bruker";
			$result = pg_query_params("UPDATE personer SET svartegrense = $1 WHERE id = $2", array($svartegrense, $bruker));
//			pg_query($query) or die('Noe gikk galt: '.pg_last_error());
			if(!$result) die('Noe gikk galt: '.pg_last_error());
			echo "<p>Ny svartegrense ble satt</p>";
		}
	} else if($_POST['registrer'] == "Legg inn") {
		// Registrer transaksjon
		$belop = $_POST['regbelop'];
		if (is_numeric($belop) && $belop != 0) {
			$type = $_POST['type'];
			if(!is_numeric($type)) die('Feil: type må angis med tall - ikke prøv deg på SQL-injection...');
			if($type != 0) $belop = -$belop;
			$kommentar = str_replace("'","''", $_POST['kommentar']); // Escape strings for å beskytte mot SQL-injection
			$dato = str_replace("'","''", $_POST['dato']);
			// Når all input har blitt hentet og kontrollert - sett inn info i database...
//			$query = "INSERT INTO klaus VALUES(DEFAULT, $bruker, $type, $belop, '$kommentar', '$dato', DEFAULT)";
			$result = pg_query_params("INSERT INTO klaus VALUES(DEFAULT, $1, $2, $3, $4, $5, DEFAULT)", array($bruker, $type, $belop, $kommentar, $dato));
//			pg_query($query) or die('Noe gikk galt: '.pg_last_error());
			if(!$result) die('Noe gikk galt: '.pg_last_error());
		} else {
			// TODO: varsle bruker med alertbox i stedet?
			echo "Feil: beløp må angis med tall, og kan ikke være tomt eller 0 (litt poengløst å registrere 0kr - ikke sant?)";
		}
	}
	
	// Hent brukerdetaljer
//	$query = "SELECT kallenavn, fornavn, etternavn, epost, svartegrense, tlf  FROM personer WHERE id = $bruker";
	$result = pg_query_params("SELECT kallenavn, fornavn, etternavn, epost, svartegrense, tlf  FROM personer WHERE id = $1", array($bruker));
//	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	if(!$result) die('Noe gikk galt: '.pg_last_error());
	$row = pg_fetch_array($result);
	$svartegrense = $row['svartegrense'];
	if ($svartegrense == 0 || !isset($svartegrense)) $svartegrense = $svartegrenser[$liste];

//	$query = "SELECT SUM(belop) AS saldo FROM klaus WHERE bruker = $bruker";
	$result = pg_query_params("SELECT SUM(belop) AS saldo FROM klaus WHERE bruker = $1", array($bruker));
//	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	if(!$result) die('Noe gikk galt: '.pg_last_error());
	$saldo = pg_fetch_array($result)['saldo'];
?>

<table>
<tr><th>Navn:</th><th>Epost:</th><th>Telefon:</th><th>Svartegrense:</th></tr>
<tr><td class='infocol'><? echo $row['fornavn']." \"".$row['kallenavn']."\" ".$row['etternavn'];?></td><td class='infocol'><?=$row['epost']?></td><td class='infocol'><?=$row['tlf']?></td><td class='infocol'><input type='text' name='svartegrense' size='4' value='<?=$svartegrense?>' /><input type='submit' name='nysvartegrense' value='OK' /><input type='submit' name='nysvartegrense' value='Slett' /></td></tr>
<tr><th colspan='3'>Flytt til liste:
<select name='nyliste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
//	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
</select><input type='submit' name='flyttbruker' value='Flytt' />
</th></tr>
<tr><th colspan='5'>Sum: <input type='text' name='regbelop' size='4' placeholder='Beløp' /> Type:<select name='type'>
<!--<tr><th>Sum: <input type='text' name='regbelop' size='4' placeholder='Beløp' /></th><th colspan='2'>Type:<select name='type'>-->
<?
foreach ($listetype as $listetype_index => $listetype_navn) {
	echo "<option value='$listetype_index'>$listetype_navn</option>";
}
?>
<!--</select></th><td colspan='2'><input type='text' name='kommentar' placeholder='Kommentar' /></td><td><input type='date' name='dato' size='8' value='<?=$dato?>' /></td><td><input type='submit' name='registrer' value='Legg inn' /></td>-->
</select> <input type='text' name='kommentar' placeholder='Kommentar' /> <input type='date' name='dato' size='8' value='<?=$dato?>' /><input type='submit' name='registrer' value='Legg inn' /></th>
</tr></table>
<table width='100%'>
<tr><th>Saldo:</th><td><?=$saldo?></td></tr>
<tr><th width='45'>Beløp</th><th width='60'>Type</th><th>Kommentar</th><th width='80'>Dato</th><th width='130'>Registrert</th></tr>

<?
//	$query = "SELECT * FROM klaus WHERE bruker = $bruker ORDER BY id DESC";
	$result = pg_query_params("SELECT * FROM klaus WHERE bruker = $1 ORDER BY id DESC", array($bruker));
//	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	if(!$result) die('Noe gikk galt: '.pg_last_error());
	while($row = pg_fetch_array($result)) {
		echo "<tr><td class='".$listetype[$row['type']]."'>".$row['belop']."</td><td class='".$listetype[$row['type']]."'>".$listetype[$row['type']]."</td><td>".$row['kommentar']."</td><td>".$row['dato']."</td><td>".$row['registrert']."</td></tr>";
	}
echo "</table>";

//	var_dump($row);
} else {
	echo "Velg en bruker til venstre...";
}
echo "</td></tr></table></form>";
include '../include/foot.php';
?>
