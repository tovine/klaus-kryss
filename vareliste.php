<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

// Tar inn parametere som trengs - escape quotes for å redde databasen fra SQL-injection
$sokeord = str_replace("'", "''", $_REQUEST['sokeord']);
$filter_kategori = str_replace("'", "''", $_REQUEST['filter_kategori']);
$sort_parameter = str_replace("'", "''", $_REQUEST['sort']);

// Bygg query
$query = "SELECT * FROM varer";
// Variabel for å holde orden på filter-kondisjoner
$limits = 0;

if ($sokeord != '') {
	$query .= " WHERE navn ILIKE('%$sokeord%')";
	$limits++;
}
if ($filter_kategori != '') {
	// $limits > 0 -> TRUE, derfor funker dette
	if ($limits) $query .= " AND ";
	else $query .= " WHERE ";
	$query .= "kategori = '$filter_kategori'";
}
if ($sort_parameter != '') $query .= " ORDER BY $sort_parameter";

$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
?>

<style type="text/css">
.slettet {
/* Stil for å tydeliggjøre at en vare ikke lenger er i sortimentet */
text-decoration: line-through;
}
</style>

<script type="text/javascript">
function wipeQuery() {
	document.getElementById('sokefelt').value=''
}
</script>

<h3>Vareliste (endringer krever barsjef-tilgang)</h3>
<a href='index.php'>Tilbake</a><br />
<form action='vareliste.php'>
<h4>Filtrer søkeresultat (<a href='vare.php'>Legg inn ny vare</a>)</h4>
Navn: <input type='text' name='sokeord' id='sokefelt' value='<?=$sokeord?>' />
Kategori: <select name='filter_kategori' onChange='wipeQuery()'>
<option value=''>Alle</option>
<?
// Henter oversikten over alle de forskjellige kategoriene som finnes i databasen
$kategori_query = "SELECT pg_enum.enumlabel AS varetype
                FROM pg_enum
                JOIN pg_type
                ON pg_enum.enumtypid = pg_type.oid
                WHERE pg_type.typname = 'klaus_varetype'";
$kategori_result = pg_query($kategori_query) or die('Noe gikk galt: '.pg_last_error());
while ($row = pg_fetch_array($kategori_result)) {
	echo "<option value='".$row['varetype']."'";
	if ($row['varetype'] == $filter_kategori) echo " selected";
	echo ">".$row['varetype']."</option>";
}
?>
</select>
<input type='submit' value='Søk' />
</form>

<?
if (pg_num_rows($result)) {
	if ($sokeord) $filter_args .= "&sokeord=$sokeord";
	if ($filter_kategori) $filter_args .= "&filter_kategori=$filter_kategori"; 
	// TODO: mulighet for å sortere andre veien
	echo "<table><tr><th><a href='vareliste.php?sort=navn";
	if ($sort_parameter == 'navn') echo "%20desc";
	echo "$filter_args'>Navn</a></th><th><a href='vareliste.php?sort=kategori";
	if ($sort_parameter == 'kategori') echo "%20desc";
	echo "$filter_args'>Kategori</a></th><th><a href='vareliste.php?sort=pris";
	if ($sort_parameter == 'pris') echo "%20desc";
	echo "$filter_args'>Pris</a></th></tr>";
	while ($row = pg_fetch_array($result)) {
		// Fyll ut tabell med resultat
?>
<tr<?if ($row['slettet'] == 't') echo " class='slettet'";?>><td><a href='vare.php?id=<?=$row['id']?>'><?=$row['navn']?></a></td><td><?=$row['kategori']?></td><td><?=$row['pris']?></td></tr>
<?
	}
	echo "</table>";
} else 	echo "<p>Søket returnerte et tomt resultat</p>";


include '../include/foot.php';
?>
