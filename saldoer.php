<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$liste = $_REQUEST['liste'];
$bruker = $_REQUEST['bruker'];
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
			$query = "UPDATE personer SET liste = $nyliste WHERE id = $bruker";
			if(pg_query($query)) $liste = $nyliste;
			else echo "Noe gikk galt: ".pg_last_error();
		}
	}
}
$query = "SELECT * FROM personer WHERE liste = $liste AND slettet = FALSE ORDER BY kallenavn";
$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
echo "<table><tr><td valign='top'><select name='bruker' size='30'>";

while ($row = pg_fetch_array($result)) {
	echo "<option value='".$row['id']."'>".$row['kallenavn']."</option>";
}
echo "</select><br />
<input type='submit' name='velgbruker' value='Velg' /></td><td valign='top'>";

if(is_numeric($bruker)) {
	if($_POST['nysvartegrense'] == 'OK') {
		// Sett ny svartegrense
		$svartegrense = $_POST['svartegrense'];
		if(!is_numeric($svartegrense)) echo "Svartegrense må bestå av tall...";
		else {
			$query = "UPDATE personer SET svartegrense = $svartegrense WHERE id = $bruker";
			pg_query($query) or die('Noe gikk galt: '.pg_last_error());
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
			$query = "INSERT INTO klaus VALUES(DEFAULT, $bruker, $type, $belop, '$kommentar', '$dato', DEFAULT)";
			pg_query($query) or die('Noe gikk galt: '.pg_last_error());
		} else {
			echo "Feil: beløp må angis med tall, og kan ikke være tomt eller 0 (litt poengløst å registrere 0kr - ikke sant?)";
		}
	}
	
	// Hent brukerdetaljer
	$query = "SELECT kallenavn, fornavn, etternavn, epost, svartegrense, tlf  FROM personer WHERE id = $bruker";
	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	$row = pg_fetch_array($result);
	$svartegrense = $row['svartegrense'];
	if ($svartegrense == 0 || !isset($svartegrense)) $svartegrense = $svartegrenser[$liste];

	$query = "SELECT SUM(belop) AS saldo FROM klaus WHERE bruker = $bruker";
	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	$saldo = pg_fetch_array($result)['saldo'];
?>
<table>
<tr><th>Navn:</th><td colspan='2'><? echo $row['fornavn']." \"".$row['kallenavn']."\" ".$row['etternavn'];?></td><th>Epost:</th><td><?=$row['epost']?></td><th>Telefon:</th><td><?=$row['tlf']?></td></tr>
<tr><th>Svartegrense:</th><td><input type='text' name='svartegrense' size='3' value='<?=$svartegrense?>' /><input type='submit' name='nysvartegrense' value='OK' /></td><td colspan='3'>Flytt til liste:
<select name='nyliste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
//	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
</select><input type='submit' name='flyttbruker' value='Flytt' />
</td></tr>
<tr><th>Sum: <input type='text' name='regbelop' size='4' placeholder='Beløp' /></th><th colspan='2'>Type:<select name='type'>
<?
foreach ($listetype as $listetype_index => $listetype_navn) {
	echo "<option value='$listetype_index'>$listetype_navn</option>";
}
?>
</select></th><td colspan='2'><input type='text' name='kommentar' placeholder='Kommentar' /></td><td colspan='2'><input type='date' name='dato' size='8' value='<?=$dato?>' /></td><td><input type='submit' name='registrer' value='Legg inn' /></td>
</tr></table>
<table width='100%'>
<tr><th>Saldo:</th><td><?=$saldo?></td></tr>
<tr><th width='45'>Beløp</th><th width='60'>Type</th><th>Kommentar</th><th width='80'>Dato</th><th width='130'>Registrert</th></tr>

<?
	$query = "SELECT * FROM klaus WHERE bruker = $bruker ORDER BY id DESC";
	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
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
