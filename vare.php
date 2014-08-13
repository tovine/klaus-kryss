<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$vare_id = $_REQUEST['id'];
$vare_navn = str_replace("'", "''", $_POST['navn']);
$vare_kategori = str_replace("'", "''", $_POST['kategori']);
$vare_pris = str_replace("'", "''", $_POST['pris']);
$vare_slettet = $_POST['slettet'];
$vare_strekkode = str_replace("'", "''", $_POST['strekkode']); // Til bruk senere, når man bestemmer seg for å eventuelt bruke dette...

// TODO: felt for å skrive inn pris hos polet og flaskestørrelse (evt. bare literpris hos polet) og så få ut forslag til salgspris - basert på innpris/14 for 1L, innpris/11 for 0.7L og innpris/8 for 0.5L, evt. rund opp til nærmeste 5'er

if ($_POST['lagre'] != '') {
	if ($vare_id && !is_numeric($vare_id)) echo "Feil: vare-id må være numerisk - ingen SQL-injection her...";
	else {
		// Oppdater eller legg inn vare
		if ($vare_slettet != "t") $vare_slettet = "f";
		
		if ($vare_id && pg_num_rows(pg_query("SELECT * FROM varer WHERE id = $vare_id"))) $query = "UPDATE varer SET navn = '$vare_navn', kategori = '$vare_kategori', pris = $vare_pris, strekkode = '$vare_strekkode', slettet = '$vare_slettet' WHERE id = $vare_id";
		else $query = "INSERT INTO varer (navn, kategori, pris, strekkode, slettet) VALUES('$vare_navn', '$vare_kategori', $vare_pris, '$vare_strekkode', '$vare_slettet')";
		pg_query($query) or die('Noe gikk galt: '.pg_last_error());
		$message = "<p>Vare lagret</p>";
	}
}

if ($vare_id != '') {
	// Henter vare med den ID'en
	$query = "SELECT * FROM varer WHERE id = $vare_id";
	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	$row = pg_fetch_array($result) or die('Noe gikk galt: '.pg_last_error());
	$vare_navn = $row['navn'];
	$vare_kategori = $row['kategori'];
	$vare_pris = $row['pris'];
	$vare_strekkode = $row['strekkode'];
	$vare_slettet = $row['slettet'];
}
?>

<script type="text/javascript">
// TODO: utvid denne funksjonen til å kunne fungere med poløl og andre enheter som bare selges i hele flasker - skal reagere på hvilken kategori den gjeldende varen er
function calculate() {
	// Funksjon som beregner prisforslag ut fra formel (break_even = totalpris/svinnkorrigert_antall)
	if (document.getElementById('pris_inn').value != '') {
		var storrelsevalg = document.getElementById("flaskestr");
		var divisor = storrelsevalg.options[storrelsevalg.selectedIndex].value;
		var kostpris = Math.ceil(eval (document.getElementById('pris_inn').value + "/" + divisor));
		document.getElementById('pris_break').value = kostpris;
		// Runder prisen opp til nærmeste femmer
		var last_digit = (kostpris % 10);
		if (last_digit == 0);
		else if (last_digit > 5 ) kostpris += 10 - last_digit;
		else kostpris += 5 - last_digit;
		document.getElementById('pris_forslag').value = kostpris;
	}
}
function updatePrice() {
	// Setter verdien i pris-feltet lik prisforslaget, dersom dette ikke er tomt
	if (document.getElementById('pris_forslag').value != '') document.getElementById('input_pris').value = document.getElementById('pris_forslag').value
}
</script>

<h3>Ny/endre vare</h3>
<div style="float:left;">
<a href='vareliste.php'>Tilbake</a><br />
<form name="vare" action='vare.php' method='post'>
<p><input type='hidden' name='id' value='<?=$vare_id?>' />
<table>
<tr><th>Navn:</th><td><input type='text' name='navn' value='<?=$vare_navn?>' /></td></tr>
<tr><th>Kategori:</th><td><select name='kategori'>
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
	if ($row['varetype'] == $vare_kategori) echo " selected";
	echo ">".$row['varetype']."</option>";
}
?>
</select></td></tr>
<tr><th>Pris</th><td><input type='text' id='input_pris' name='pris' size='3' value='<?=$vare_pris?>'/> Utgått? <input type="checkbox" name="slettet" value="t" <? if($vare_slettet == 't') echo "checked "; ?>/></td></tr>
</table>
<input type='submit' name='lagre' value='Lagre vare' />
</p>
</form>
<? echo $message; ?>
</div>

<div style="float: left; padding-left: 20px">
<h4>Beregn prisforslag (foreløpig kun for brennevin o.l.)</h4>
<p>Flaskestørrelse: <select id='flaskestr' onChange='calculate()'>
<?
foreach ($polfaktor as $storrelse => $faktor) {
	echo "<option value='$faktor'>$storrelse</option>";
}
?>
</select><br />
Pris hos vinmonopolet: <input id='pris_inn' size='4' onChange='calculate()'/></p>
Break even: <input id='pris_break' size='4' disabled /><br />
Prisforslag: <input id='pris_forslag' size='4' disabled />
<button onClick="updatePrice()">Bruk</button>

</form>
</div>

<?

include '../include/foot.php';
?>
