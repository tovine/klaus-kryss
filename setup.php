<?php
header('Content-type: text/HTML; charset=UTF8');
(PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) && die("Feil: dette skriptet må kjøres fra kommandolinjen, 'php setup.php'\n");
include('../include/dbsetup.php');
echo "Oppretter tabeller med riktig struktur...\n";

$filename = "db_struct.sql"; 

//pg_query("BEGIN; COMMIT;\n" . file_get_contents($filename)) or die('Noe gikk galt: '.pg_last_error());

echo "Databaseoppsett fullført!\n";

?>
