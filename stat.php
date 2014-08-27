<?php
include '../include/top.php';
include '../include/dbsetup.php'; // Setter opp tilkobling mot postgresql-database, så man bare kan bruke pg_query() direkte.
include 'klaus_inc.php';

//bare for innlogga folk
//if (!$sess_level){ header("Location: login.php?lfunk=loginfail&ref=".$_SERVER['REQUEST_URI']);exit;}

$sqlaar = "
	SELECT kallenavn AS navn,
	ABS( SUM( belop ) ) AS sum
	FROM klaus_personer
	WHERE dato > ( NOW() - INTERVAL '1 YEAR' )
	AND type >0
	GROUP BY navn
	ORDER BY sum DESC
	LIMIT 20
;";
$aar = pg_query($sqlaar) or die(pg_last_error());
$aart = pg_query($sqlaar);

$sqlmnd = "
	SELECT kallenavn AS navn ,
	ABS( SUM( belop ) ) AS sum
	FROM klaus_personer
	WHERE dato > ( NOW() - INTERVAL '1 MONTH' )
	AND type >0
	GROUP BY navn
	ORDER BY sum DESC
	LIMIT 20
;";
$mnd = pg_query($sqlmnd);
$mndt = pg_query($sqlmnd);

$sqlall = "
	SELECT kallenavn AS navn ,
	ABS( SUM( belop ) ) AS sum
	FROM klaus_personer
	WHERE type >0
	GROUP BY navn
	ORDER BY sum DESC
	LIMIT 40
;";
$all = pg_query($sqlall);
$allt = pg_query($sqlall);

$sqlmonth = "
	SELECT TO_CHAR(  dato, 'FMMonth'  )  AS  month, ABS( SUM(  belop  ) )  AS  sum
	FROM klaus_personer
	WHERE  dato > (NOW() - INTERVAL '1 YEAR')
	AND  type > 0
	GROUP  BY  month, DATE_PART( 'MONTH', dato )
	ORDER  BY  DATE_PART( 'MONTH', dato  )
	LIMIT 12
	;";
$month = pg_query($sqlmonth) or die(pg_last_error());

//FUNKSJONER

function listeHor($res, $del) {
	$f = 0;
	while ($row = pg_fetch_array($res)) {
		$r = 200;
		$g = 200*$f;
		$b = 200*$f;
		echo "<div style=\"width:".round($row[1]/$del).
		"px;background-color:rgb(".$r.",".$g.",".$b.")\">".$row[0].": ".$row[1]."</div>\n";
		if ($f == 1) $f = 0;
		else $f = 1;
	}
}

function listeVer($res, $del) {
	$max = 0;
	$f = 0;
	while ($row = pg_fetch_array($res)) {
		$r = 200;
		$g = 200*$f;
		$b = 200*$f;
		$graph .= "<div style=\"width:60px;height:".(round($row[1]/$del)+3).
		"px;top:".(620-round($row[1]/$del))."px;float:left;position:relative;background-color:rgb(".$r.",".$g.",".$b.")\"><div style=\"position:absolute;bottom:5px\">".$row[0].": ".$row[1]."</div></div>\n";
		if ($f == 1) $f = 0;
		else $f = 1;
		if ($row[1] > $max) $max = $row[1];
	}
	$height = $max/$del;
	echo "<div style=\"height:".$height."px;margin:15px\">$graph</div>";
}

function liste($res) {
	while ($row = pg_fetch_array($res)) {
		echo $row[0]." drakk for ".$row[1]." kr <br>";
	}
}

function lagDel($t) {
	$res = $t;
	$storst = 0;
	while ($row = pg_fetch_array($res)) {
		if ($row[1] > $storst) $storst = $row[1];
	}
	$del = round($storst/750);
	return $del;
}

//PRESENTASJON
// TODO: La bruker velge hvilken type statistikk man vil se, og gjøre det mulig å velge en spesifikk tidsperiode...
?>
<a href='index.php'>Tilbake</a><br />
<?
echo "<h3>Siste år:</h3>";
$del = lagDel($aart);
listeHor($aar, $del); //30
echo "";

echo "<h3>Siste måned:</h3>";
$del = lagDel($mndt);
listeHor($mnd, $del); //10
echo "";

echo "<h3>Siste år, måned for måned:</h3>";
listeVer($month, 147); //130
echo "";

echo "<h3>All time:</h3>";
$del = lagDel($allt);
listeHor($all, $del); //100
echo "";


include("../include/foot.php");
?>
