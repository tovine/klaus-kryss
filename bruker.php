<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

$bruker = $_REQUEST['bruker'];

$kallenavn = str_replace("'","''",$_POST['kallenavn']);
$fornavn = str_replace("'","''",$_POST['fornavn']);
$etternavn = str_replace("'","''",$_POST['etternavn']);
$epost = str_replace("'","''",$_POST['epost']);
$kull = $_POST['kull'];
if(!$kull) $kull = date('Y');
$liste = $_POST['liste'];
$svartegrense = $_POST['svartegrense'];
$aktiv = $_POST['aktiv'];
$slettet = $_POST['slettet'];
$tlf = str_replace("'","''",$_POST['tlf']);

$img_dir = $_SERVER['DOCUMENT_ROOT']."/img";

if($_POST['lagrebruker']) {
	if (is_numeric($kull) && is_numeric($liste)) {
		if (!is_numeric($svartegrense)) $svartegrense = 0;
		if ($aktiv != 't') $aktiv = 'f';
		if ($slettet != 't') $slettet ='f';
		if (is_numeric($bruker)) {
//			$query = "UPDATE personer SET kallenavn = '$kallenavn', fornavn = '$fornavn', etternavn = '$etternavn', epost = '$epost', kull = $kull, liste = $liste, svartegrense = $svartegrense, aktiv = '$aktiv', slettet = '$slettet', tlf = '$tlf' WHERE id = $bruker";
			$result = pg_query_params("UPDATE personer SET kallenavn = $1, fornavn = $2, etternavn = $3, epost = $4, kull = $5, liste = $6, svartegrense = $7, aktiv = $8, slettet = $9, tlf = $10 WHERE id = $11", array($kallenavn, $fornavn, $etternavn, $epost, $kull, $liste, $svartegrense, $aktiv, $slettet, $tlf, $bruker));
		} else {
//			$query = "INSERT INTO personer (kallenavn, fornavn, etternavn, epost, kull, liste, svartegrense, aktiv, slettet, tlf) VALUES ('$kallenavn', '$fornavn', '$etternavn', '$epost', $kull, $liste, $svartegrense, '$aktiv', '$slettet', '$tlf')";
			$result = pg_query_params("INSERT INTO personer (kallenavn, fornavn, etternavn, epost, kull, liste, svartegrense, aktiv, slettet, tlf) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)", array($kallenavn, $fornavn, $etternavn, $epost, $kull, $liste, $svartegrense, $aktiv, $slettet, $tlf));
		}
		//if(!pg_query($query)) echo "<span style='color: red'>Ikke lagret - noe gikk galt: ".pg_last_error()."</span><br />";
		if(!$result) echo "<span style='color: red'>Ikke lagret - noe gikk galt: ".pg_last_error()."</span><br />";
		else $lagret = true;
		if ($_FILES['portrett']['name']){//vi har faatt filer!!
			$org_bildefil = "$img_dir/portretter/".$id."_stor.jpg";
			$ny_bildefil = "$img_dir/portretter/".$id.".jpg";
			$str = 100;
			move_uploaded_file($_FILES['portrett']['tmp_name'], $org_bildefil);
			$org_bilde=imagecreatefromjpeg($org_bildefil);
			$org_x=imageSX($org_bilde);
			$org_y=imageSY($org_bilde);
			if ($org_x != $str || $org_y != $str) { // bildet har feil st�rrelse og m� resizes
				$thumb_b=$str;
				$thumb_h=$str;
				$ny_bilde=ImageCreateTrueColor($thumb_b,$thumb_h);
				imagecopyresampled($ny_bilde,$org_bilde,0,0,0,0,$thumb_b,$thumb_h,$org_x,$org_y);
				imagejpeg($ny_bilde,$ny_bildefil, 95);
				//chgrp($ny_bildefil,"fk"); //gi fk tilgang til fila..
				chmod($ny_bildefil,0664); //  -"-
				imagedestroy($ny_bilde);
				imagedestroy($org_bilde);

			} else {
				rename($org_bildefil,$ny_bildefil);
			}
		}
	} else echo "<span style='color: red'>Ikke lagret: feil i input</span><br />";
}
if(is_numeric($bruker)) {
	echo "<h3>Rediger bruker</h3>";
	// Hent brukerdetaljer
//	$query = "SELECT * FROM personer WHERE id = $bruker";
	$result = pg_query_params("SELECT * FROM personer WHERE id = $1", array($bruker)) or die('Noe gikk galt: '.pg_last_error());
//	$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
	$row = pg_fetch_array($result);

	$kallenavn = $row['kallenavn'];
	$fornavn = $row['fornavn'];
	$etternavn = $row['etternavn'];
	$epost = $row['epost'];
	$kull = $row['kull'];
	$liste = $row['liste'];
	$svartegrense = $row['svartegrense'];
	$aktiv = $row['aktiv'];
	$slettet = $row['slettet'];
	$tlf = $row['tlf'];
	$profile_img = "/img/portretter/$bruker.jpg";
} else echo "<h3>Opprett bruker</h3>";

?>
<a href='index.php'>Tilbake</a><br />

<h4>(<a href='?'>Legg inn ny bruker</a>)</h4>

<form name='personinfo' method='post' action='bruker.php' enctype='multipart/form-data'>
<input type='hidden' name='bruker' value='<?=$bruker?>' />
<table>
<?
if(is_dir($img_dir)) {
	// Print out part of form handling image input
	echo "<tr><th>Bilde:</th><td>";
	if(is_readable("$img_dir/portretter/$bruker.jpg")) echo "<img src='$profile_img' /><br />";
	echo "<input type='file' name='portrett' /></td></tr>";
}
?>
<tr><th>Kallenavn:</th><td><input type='text' name='kallenavn' value='<?=$kallenavn?>' /></td></tr>
<tr><th>Fornavn:</th><td><input type='text' name='fornavn' value='<?=$fornavn?>' /></td></tr>
<tr><th>Etternavn:</th><td><input type='text' name='etternavn' value='<?=$etternavn?>' /></td></tr>
<tr><th>Kull:</th><td><input type='number' name='kull' value='<?=$kull?>' /></td></tr>
<tr><th>Liste:</th><td><select name='liste'>
<?
foreach ($lister as $liste_index => $liste_navn) {
	echo "<option value='$liste_index' ";
	if ($liste_index == $liste) echo "selected";
	echo ">$liste_navn</option>";
}
?>
</select> Aktiv: <input type="checkbox" name="aktiv" value='t' <?if($aktiv == 't') echo "checked";?> />
Slettet: <input type="checkbox" name="slettet" value='t' <?if($slettet == 't') echo "checked";?> /></td></tr>
<tr><th>Svartegrense:</th><td><input type='text' name='svartegrense' value='<?=$svartegrense?>' /></td></tr>
<tr><th>Epost:</th><td><input type='text' name='epost' value='<?=$epost?>' /></td></tr>
<tr><th>Telefon:</th><td><input type='text' name='tlf' value='<?=$tlf?>' /></td></tr>
</table>
<input type='submit' name='lagrebruker' value='Lagre bruker' />
<input type='reset' value='Angre endringer' />
</form>

<?
if ($lagret) echo "<p>Bruker ble lagret...</p>";

include '../include/foot.php';
?>
