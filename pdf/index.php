<?php
//include ('class.ezpdf.php');
include('../klaus_inc.php');
include('../../include/dbsetup.php');
include('mpdf/mpdf.php');


$style="<style type='text/css'>
table { width: 100%; }
table, th, td {
	border-collapse: collapse;
	border: 1px solid black;
	white-space:nowrap;
	text-align: left;
	font-family: helvetica;
}
table.krysseliste {
	font-size: 20px;
}
tr {
	height:25px;
}
tr.svart {
	background: black;
}
td.svart { 
	color: #ffffff;
}
tr.hvit {
	background: white;
}
td.hvit {
	color: black;
}

</style>";

$nopdf = $_REQUEST['nopdf'];
if (!$nopdf) $pdf = new mPDF('');

$liste = $_REQUEST['liste'];
if(!is_numeric($liste)) die('Feil: liste ikke spesifisert (må angis som et tall)');

// Hent info om barsjef
$query = "SELECT * FROM verv WHERE verv = 'barsjef' ORDER BY dato DESC LIMIT 1";
$result = pg_query($query) or die('Database-spørring mislyktes: '.pg_last_error());
$barsjef_array = pg_fetch_array($result);
$aar = explode('-',$barsjef['dato'])[0];
$query = "SELECT * FROM personer WHERE id = ".$barsjef_array['person'];
$result = pg_query($query);
$row = pg_fetch_array($result);
$barsjef = $row['fornavn']." \"".$row['kallenavn']."\" ".$row['etternavn'];
$tlf_barsjef = $row['tlf'];

//$query = "SELECT kallenavn, saldo, svartegrense FROM klaus_saldoer WHERE liste = $liste ORDER BY kallenavn ASC";
$query = "SELECT kallenavn, saldo, svartegrense FROM klaus_saldoer WHERE liste = $1 ORDER BY kallenavn ASC";
//$result = pg_query($query) or die('Noe gikk galt: '.pg_last_error());
$result = pg_query_params($query, array($liste)) or die('Noe gikk galt: '.pg_last_error());

// Bygg header
if ($nopdf) {
	$header = "<table style='width:100%; border:none;'><tr style='border:none;'><td style='width: 100px; border:none;'><img height='80px' src='fklogo_stor_hvit.png' /></td><td style='border:none;'><h1 style='display:inline'>Klaus Minnefond</h1><br /><h3 style='display:inline'>Krysseliste ".$lister[$liste].",<br />Forsterkerkomiteen</h3></td><td style='text-align:right; border:none'>
Dato: ".date('d.m.y')."<br />
Barsjef: $barsjef<br  />
$klaus_epost, $tlf_barsjef<br />
Kontonummer: $klaus_kontonr
</td></tr></table>";
} else {
	$header = "<table style='width:100%; border:none;'><tr style='border:none;'><td style='width: 100px; border:none;'><img height='80px' src='fklogo_stor_hvit.png' /></td><td style='border:none;'><h1 style='display:inline'>Klaus Minnefond</h1><h3 style='display:inline'>Krysseliste ".$lister[$liste].",<br />Forsterkerkomiteen</h3></td><td style='text-align:right; border:none'>
Dato: ".date('d.m.y')."<br />
Barsjef: $barsjef<br  />
$klaus_epost, $tlf_barsjef<br />
Kontonummer: $klaus_kontonr
</td></tr></table>";
}

//$col_hdrs = array('Navn' => '1%','Pils' => '40%','Brus' => '10%','50' => '10%','20' => '16%','10' => '7%','5' => '7%','1' => '7%');

$body = "<table class='krysseliste'><thead><tr>";
//$body = "<table class='krysseliste'><thead><td colspan='".sizeof($col_hdrs)."'>$header</td><tr>";

$body .= "<th style='width: 10;'>&nbsp;Navn</th>";
foreach ($col_hdrs as $hdr => $width) {
	$body .= "<th style='width: $width;'>&nbsp;$hdr&nbsp;</th>";
}
$body .= "</tr></thead>";

while ($row = pg_fetch_array($result)) {
	$nick = $row['kallenavn'];
	$svart = $row['svartegrense'];
	$sum = $row['saldo'];
	if($sum == null) $sum = 0;

	if ($svart != 0) $denne_sin_grense = $svart;
	else $denne_sin_grense = $svartegrenser[$liste];

	if ($sum < $denne_sin_grense)
		$body .= "<tr class='svart'>";
	//	$farge = 0;
	else
		$body .= "<tr class='hvit'>";
	//	$farge = 1;
	$body .= "<td style='background: white'>&nbsp;$nick</td>";
	for ($i = 0;$i < sizeof($col_hdrs);$i++) $body .= "<td></td>";

	$body .= "</tr>";
}

$body .= "</table>";


$footer = "<p align='right'>Side {PAGENO}</p>";
if ($nopdf) {
	header('Content-type: Text/HTML; charset=UTF-8'); 
	echo $style;
	echo $header;
	echo $body;
} else {
	$pdf->SetTitle($lister[$liste].date('-ymd'));
	$pdf->SetCreator($_SERVER['PHP_AUTH_USER']); //TODO: må endre måten brukernavn hentes når ting migreres tilbake til Samfundet og annen login...
	$pdf->setAutoTopMargin = 'stretch';
	$pdf->setAutoBottomMargin = 'stretch';
	$pdf->keep_table_proportions = true;
	$pdf->tableMinSizePriority = true;

	$pdf->SetHTMLHeader($header, 'O');
	$pdf->SetHTMLHeader($header, 'E');
	$pdf->SetHTMLFooter($footer, 'O');
	$pdf->SetHTMLFooter($footer, 'E');

	$pdf->WriteHTML($style, 1);
	$pdf->WriteHTML($body, 2, false);

	$pdf->Output(strtolower($lister[$liste]).'-siste.pdf','I');
}

?>
