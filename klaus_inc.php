<?php
// Global config-fil for ting som angår baren - forandre verdier her for å bruke dem i hele Klaus-systemet
$klaus_kontonr = "9615.12.62837";
$klaus_epost = "fk-bar@org.ntnu.no";

$pris_pils = 25;
$pris_brus = 20;

// Indekseres på samme måte som ellers - aktiv, nypang, pang, ukefunk osv...
$svartegrenser = array(800, 800, 0, 500);

$lister = array(0 => "Aktive", 1 => "Nypang", 2 => "Pang", 3 => "Ukefunk", 4 => "Test");

// Dette angir navnene på de forskjellige listetypene (bør ikke endres da de brukes til å linke til riktig CSS-stil
$listetype = array("Innskudd", "Krysseliste", "BSF");

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
