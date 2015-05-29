<?php
header("content-type:text/html;charset=UTF-8");
require_once("mystore.php");
require_once("../Util/util.php");
require_once("../csvparse/csvparse.php");

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
        $headers_registered = false;
        echo "<table>";
        foreach($r->data->product_data as $prod) {
                // Extract headers
                echo "<tr>";
                foreach($prod as $header => $value) {
                    if(!$headers_registered) {
                        echo "<td>" . $header ."</td>";
                        $headers_registered = true;
                    }
                    echo "<td>";
                    if(is_string($value))
                        echo utf8_decode($value);
                    echo "</td>";
                }
                echo "</tr>";
//            pre_print_r($i);
        }
        echo "</table>";
		// $a_product = $r->data->product_data->{"132"};

		//var_dump($a_product);

		// foreach($a_product as $field => $value) {
			// pre_print_r($field);
		// }
	break;

	case 'list_categories':
	break;

	case 'csv_import':
	  echo '
	  <form name="csv" method="post" type="multipart/form-data" action="?a=import_subbed">
	  <table>
	  <tr><td>CSV File</td><td><input type="file" name="file" name="csv" /></td></tr>
	  <tr><td colspan="2">Choose saved file</td></tr>';

	  if($folder = opendir("../csvparse/files")) {
	    while(($file = readdir($folder)) !== false) {
            if($file != "." AND $file != "..")
                echo '<tr><td colspan="2"><a href="?a=import_subbed&csv='.$file.'">'.$file.'</a></td></tr>';
	    }
	  }

	  echo '</table>
	  </form>';
	break;
	
        case 'import_subbed':
	  // if upload blabla
	  // ...
	  // endif -------------------
	  

	  if(arrGet($_GET, 'csv', false)) {
	    pre_print_r($_GET);

	    $file = "../csvparse/files/" . arrGet($_GET, 'csv');
	    
	    $csv = new csv($file);

	    $csv->toArray();
	    
	    $a_product = $r->data->product_data->{"132"};

	    echo '<form action="?a=import_save" method="post">';
	    echo '<input type="hidden" name="file" value="'.$file.'" />';
	    $csv->html_table(5, array_keys((array)$a_product));
	    echo '<input type="submit" />';
	    echo '</form>';
	  }
	  break;

          case 'import_save':
	    $fields_to_import = [];

	    foreach(arrGet($_POST, 'header') as $key => $val) {
	      if($val != "none") {
            $fields_to_import[$key] = $val;
	      }
	    }

	    //pre_print_r($fields_to_import);

	    //pre_print_r($a_product = $r->data->product_data->{"132"});


	    $file = arrGet($_POST, "file");
	    $csv = new csv($file);
	    $products = $csv->toArray();

	    $savelist = [];

	    //pre_print_r($products);

	    foreach($products as $i => $product) {
            foreach($fields_to_import as $name => $db_name) {
                $savelist[$i][$db_name] = json_decode($product[$name]);
            }
            $savelist[$i]["products_status"] = 0;
	    }

	    $test = $mystore->test('create_update_product',
				   ["product_data" => array_to_utf8($savelist)]);

	    pre_print_r($test);
	    break;

	default:
	echo "blah";
	break;
}
?>

</body>
</html>