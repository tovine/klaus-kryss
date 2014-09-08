<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$liste = $_REQUEST['liste'];
$bruker = $_REQUEST['bruker'];
if(!is_numeric($liste)) $liste = 0;

function print_list($liste, $link) {
	global $svartegrenser, $total_saldo, $listenavn;
	$query = "SELECT id, kallenavn, saldo, svartegrense FROM klaus_saldoer WHERE liste = $liste ORDER BY kallenavn ASC";
	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	if (!$link) {
		if (!pg_num_rows($result)) return;
		echo "<h4>Saldoliste: $listenavn</h4>";
	}
	echo "<table><tr><th>Kallenavn</th><th>Saldo</th><th>Status</th></tr>";

	while($row = pg_fetch_array($result)) {
		if($row['svartegrense'] != 0) $svartegrense = $row['svartegrense'];
		else $svartegrense = $svartegrenser[$liste];
		if($row['saldo'] == null) $row['saldo'] = 0;
		if($row['saldo'] >= $svartegrense) $status = "hvit";
		else $status = "svart";
		if($link) echo "<tr><td><a href='saldoer.php?liste=$liste&bruker=".$row['id']."'>".$row['kallenavn']."</a></td><td>".$row['saldo']."</td><td class='$status'>$status</td></tr>";
		else echo "<tr><td>".$row['kallenavn']."</td><td>".$row['saldo']."</td><td class='$status'>$status</td></tr>";
		$total_saldo += $row['saldo'];
	}
	echo "</table>";
}
?>

<style type="text/css">
.svart {
background-color:black;
color:white;
}
.hvit {
background-color:white;
}
</style>

<h3>Vis/skriv ut krysseliste</h3>
<a href='index.php'>Tilbake</a><br />
<form name='krysseliste' action='liste.php' method='post'>
Velg liste: <select name='liste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
	<option value='-1' <?if($liste == -1) echo "selected";?>>Alle</option>
</select>
<input name='velgliste' type='submit' value='Velg' />

<?
$total_saldo = 0;

if($liste == -1) { // Skriv ut alle listene
	foreach($lister as $i => $listenavn) {
		print_list($i,false);
//		echo "<br />";
	}
	echo "<h4>Total saldo til alle i gjengen: $total_saldo</h4>";
} else {
	echo "<h4>Saldoliste: ".$lister[$liste]." (<a href='pdf?liste=$liste'>skriv ut PDF</a>)</h4>";
	print_list($liste,true);
	echo "<h4>Total saldo ".$lister[$liste].": kr.$total_saldo,-</h4>";
}
include('../include/foot.php');
?>
