<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

// Hvis liste er -1 betyr det 'alle', og systemet går inn i BSF-modus...
$liste = $_REQUEST['liste'];
if (!is_numeric($liste)) $liste = 0;

$dato = str_replace("'","''",$_REQUEST['dato']) or $dato = date("Y-m-d"); // YYYY-MM-D

?>

<h3>Legg inn ny krysseliste</h3>
<a href='index.php'>Tilbake</a>
<form name='krysseliste' onSubmit="cleanForm()" action='regkryss.php' method='post'>
<p>Velg en liste for å registrere nye kryss...</p>
<select name='liste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
<option value='-1'<?if ($liste == -1) echo " selected"?>>Alle (BSF)</option>
</select>
<input name='velgliste' type='submit' value='Velg' />
Listedato: <input name='dato' type='date' value="<? echo $dato?>" />

<?
if(is_numeric($liste)) {
	echo "Valgt krysseliste: ".$lister[$liste];
	if ($liste == -1) $result = pg_query("SELECT * FROM personer WHERE slettet = FALSE ORDER BY kallenavn");
	else $result = pg_query_params("SELECT * FROM personer WHERE liste = $1 AND slettet = FALSE ORDER BY kallenavn", array($liste));
	if(!$result) die('Noe gikk galt: '.pg_last_error());

	if($_POST['legginnliste']) {
		$summer = array();
		$navn = array();
		$data = json_decode($_POST['data'], true);
//		var_dump($data);

		if ($liste == -1) $insert_result = pg_query("SELECT id, kallenavn FROM personer WHERE slettet = FALSE ORDER BY kallenavn");
		else $insert_result = pg_query_params("SELECT id, kallenavn FROM personer WHERE liste = $1 AND slettet = FALSE ORDER BY kallenavn", array($liste));
		if (!$insert_result) die('Noe gikk galt: '.pg_last_error());
		while($row = pg_fetch_array($insert_result)) {
		//	$sum = $_POST['_'.$row['id'].'_tot'];
			$sum = $data['_'.$row['id'].'_tot'];
			if($sum && is_numeric($sum) && ($sum != 0)) {
				// Klargjør data for å legge inn i databasen
				$summer[$row['id']] = -$sum;
				// Link navn med id for bruk i "resultatet"
				$navn[$row['id']] = $row['kallenavn'];
			}
		}
//		var_dump($summer);
//		var_dump($navn);
		// type 1 er FK-liste (2 er BSF og 0 er innskudd)
		$kommentar = str_replace("'","''",$_POST['kommentar']);	// SQL injection-beskyttelse

		if ($liste == -1) $statement = pg_prepare("insert_transaction","INSERT INTO klaus (bruker, type, belop, dato, kommentar) VALUES($1, 2, $2, '$dato', '$kommentar')") or die("Mislyktes i å opprette query, prøv igjen...<br />Debug-info: ".pg_last_error());
		else $statement = pg_prepare("insert_transaction","INSERT INTO klaus (bruker, type, belop, dato, kommentar) VALUES($1, 1, $2, '$dato', '$kommentar')") or die("Mislyktes i å opprette query, prøv igjen...<br />Debug-info: ".pg_last_error());
		echo "<p>Siste listeregistrering:<br />----------------------------";
		$totalsum = 0;
		foreach($summer as $id => $belop) {
			if(pg_execute("insert_transaction",array($id,$belop))) {
				echo "<br />Registrerte $belop,- på ".$navn[$id];
				$totalsum += $belop;
			} else {
				// Gi beskjed om at noe gikk galt og skriv ut feilmelding
				echo "<br />Noe gikk galt under registreringen av $belop,- på ".$navn[$id].": ".pg_last_error();
			}
		}
		echo "<br />----------------------------<br /><b>Totalt registrert: kr.$totalsum,-</b>";
		if ($kommentar != '') echo "<br />----------------------------<br />Listekommentar: $kommentar";
		echo "</p>";
	}
?>
<script type="text/javascript">
var inputAdded = false;
function summer(id) {
	inputAdded = true; // Brukes for å advare
<?
	echo "eval(	'document.krysseliste._' + id + '_tot.value = '";
	foreach ($col_pris as $felt => $verdi) {
		// Generer javascript for å summere verdiene
		echo " +\n'document.krysseliste._' + id + '_$felt.value * $verdi + '";
	}
	echo "+ '0');\n"; // Workaround for at javascript ikke skal bli furten...
?>
}
function cleanForm() {
	var data = new Object();
	for(var i=0; i < document.krysseliste.elements.length; i++) {
		var el = document.krysseliste.elements[i];
		if(el.type == "text" && /_[0-9]+_/gi.test(el.name) /*&& !(/_tot/gi.test(el.name))*/) {
			el.disabled = true;
			if(/_tot/gi.test(el.name) && el.value.length > 0) data[el.name] = el.value;
		}
	}
	document.krysseliste.elements.data.value = JSON.stringify(data);
}
window.onbeforeunload = function(e) {
	if (inputAdded == false)
		return false;
	var dialogText = 'Er du sikker på at du vil avbryte krysselisteregistreringen?';
	e.returnValue = dialogText;
	return dialogText;
}
</script>
<table>
<tr><th>Kallenavn</th>
<?
	foreach ($col_hdrs as $hdr_title => $derp) {
		echo "<th>$hdr_title</th>";
	}
	echo "<th>Sum</th></tr>";
	while($row = pg_fetch_array($result)) {
		$nick = $row['kallenavn'];
		echo "<tr><td>$nick</td>";

		foreach ($col_hdrs as $col => $width) {
			echo "<td><input name='_".$row['id']."_".$col."' type='text' size='2' onchange='summer(".$row['id'].")' /></td>";
		}
		echo "<td><input name='_".$row['id']."_tot' type='text' size='3' readonly /></td></tr>";
	}

	echo "</table><br />
	Kommentar til listen: <input type='text' name='kommentar' /><br /><br />
	<input type='hidden' id='form_serialized' name='data' />
	<input type='submit' name='legginnliste' value='Legg inn' /><input type='reset' value='Nullstill' />";
} else {?>
<?
}

echo "</form>";

include '../include/foot.php';
?>
