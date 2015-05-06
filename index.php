<?php
require_once("mystore.php");

$mystore = new MystoreApi("NIVO-45C882B8-42");

$r = $mystore->api("get_categories", ["prosduct_data" => ["name" =>"blah"]]);

echo "<pre>";
var_dump($r);
echo "</pre>";
?>