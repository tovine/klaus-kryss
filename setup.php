<?php
header('Content-type: text/HTML; encoding=UTF8');

include('../include/dbsetup.php');
echo "Oppretter tabeller med riktig struktur...\n";

$filename = "db_struct.sql"; 

pg_query("BEGIN; COMMIT;\n" . file_get_contents($filename)) or die('Noe gikk galt: '.pg_last_error());

echo "Databaseoppsett fullført!\n";

?>
