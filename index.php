<?php
header("content-type:text/html");
require_once("mystore.php");
require_once("../Util/util.php");

$mystore = new MystoreApi("NIVO-45C882B8-42");
?><!DOCTYPE html>
<html>
<body>

<div>
<a href="?a=list_products">List products</a>|<a href="?a=list_categories">List categories</a>|<a href="?a=csv_import">CSV import</a>
</div>


<?php

$r = $mystore->api("get_all_products");




switch(arrGet($_GET, 'a', false)) {
	case 'list_products':
		$a_product = $r->data->product_data->{"132"};

		//var_dump($a_product);

		foreach($a_product as $field => $value) {
			pre_print_r($field);
		}
	break;

	case 'list_categories':
	break;

	case 'csv_import':
		?>
		<form name="csv" method="post" type="multipart/form-data">
		<table>
		<tr><td>CSV File</td><td><input type="file" name="file" /></td></tr>
		</table>
		</form>
		<?php
	break;

	default:
	echo "blah";
	break;
}
?>

</body>
</html>