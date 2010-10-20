<?php

// Show all errors:
error_reporting(E_ALL);

echo "start<hr>";

require_once('config.php');
require_once('whiteboard.php');

$wbobj = new Whiteboard($wbdbfn);
echo "create: ".$wbdbfn."<hr>";
$wbobj->db_create();

$wbobj->db_close();

echo "stop";

?>
