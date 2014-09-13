<?php
// Global config-fil for ting som angår baren - forandre verdier her for å bruke dem i hele Klaus-systemet
$klaus_kontonr = "9615.12.62837";
$klaus_epost = "fk-bar@org.ntnu.no";
$klaus_barnavn = "Klaus Minnefond";

$pris_pils = 25;
$pris_brus = 20;

// Indekseres på samme måte som ellers - aktiv, nypang, pang, ukefunk osv...
$svartegrenser = array(0 => 800, 1 => 800, 2 => 0, 3 => 500, 5 => 0, 6 => 0);

$lister = array(0 => "Aktive", 1 => "Nypang", 2 => "Pang", 3 => "Ukefunk", 5 => "Inndrikking", 6 => "UkefunkPang");

// Dette angir navnene på de forskjellige listetypene (bør ikke endres da de brukes til å linke til riktig CSS-stil
$listetype = array("Innskudd", "Krysseliste", "BSF");

// Følgende brukes til å sette konstanter til beregning av kostpris for polvarer (deler hele flasken på riktig antall salg før "break even" medregnet svinn)
$polfaktor = array('1.0L' => 14, '0.7L' => 11, '0.5L' => 8);
$polekstra = 5;	// Påslag for "break even" på ting som selges i enkeltenheter (poløl, brus o.l.)

// Hindre enter i å fucke opp krysseliste-skjema (kommenter ut/slett hvis du ønsker å kunne sende inn skjema med enter)
?>
<script type="text/javascript">

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  if ((evt.keyCode == 13) && (node.type=="text"))  {return false;}
}


document.onkeypress = stopRKey;

</script> 
