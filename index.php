<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$query = "SELECT * FROM verv WHERE verv = 'barsjef' ORDER BY dato DESC LIMIT 1";
$result = pg_query($query) or die('Database-spørring mislyktes: '.pg_last_error());
$barsjef = pg_fetch_array($result);
$aar = explode('-',$barsjef['dato'])[0];
$query = "SELECT * FROM personer WHERE id = ".$barsjef['person'];
$result = pg_query($query);
$row = pg_fetch_array($result);
?>

<h3><?=$klaus_barnavn?></h3>
<table>
<tr><th>Barsjef <?=$aar?>:</th><td><?=$row['fornavn']?> "<?=$row['kallenavn']?>" <?=$row['etternavn']?></td></tr>
<tr><th>Kontonr:</th><td><?=$klaus_kontonr?></td></tr>
<tr><th>Epost:</th><td><?=$klaus_epost?></td></tr>
<tr><th>Tlf:</th><td><?=$row['tlf']?></td></tr>
</table>
<h4>Funksjoner</h4>
<ul>
<li><a href='bruker.php'>Legg inn ny bruker</a> (kun for barsjef!)</li>
<li><a href='saldoer.php'>Sjekk/endre saldoer</a> (kun for barsjef!)</li>
<li><a href='regkryss.php'>Legg inn liste</a> (kun for barsjef!)</li>
<li><a href='vareliste.php'>Vareutvalg</a></li>
<li><a href='liste.php'>Vis/print liste</a></li>
<li><a href='stat.php'>Statistikk</a></li>
</ul>
<?
include '../include/foot.php';
?>
