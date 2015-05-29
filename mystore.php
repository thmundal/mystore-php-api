<?php
/**
 * API Interface for mystore.no written for PHP
 * 
 * @author Thomas Mundal <thmundal@gmail.com>
 * @version 0.1.0
 * @todo: Fully implement memcached support for large amounts of data
 **/

Class myStoreAPI {
    private $memcached = false;
    private $cache_object;
    
	private $api_key;
	private $store_url;

	private $http_request;
	private $api_map = [
		// Categories
		"get_categories" => [
			"method" => "get",
			"url" => "categories.json"],

		// Proucts
	 	"get_all_products" => [
	 		"method" => "get",
			"url" => "products.json"],
	   	"get_products_in_category" => [
	   		"method" => "get",
	   		"url" => "products/:category_id.json"],
   		"products_text_search" => [
   			"method" => "get",
			"url" => "products/search/gent.json"],
		"create_update_product" => [
			"method" => "post",
			"url" => "products/create_or_update.json",
			"data_structure" => ["product_data" => 
                                    ["products_images" => "array",
                                      "products_description" => "string"]]],
		"update_stock_by_model" =>  [
			"method" => "post",
			"url" => "products/update_stock_by_model.json",
			"data_structure" => ["product_data" => true]],


		// Orders
		"get_orders" => [
			"method" => "get",
			"url" => "orders.json"],
		"get_order_by_id" => [
			"method" =>"get",
			"url" => "orders/:id.json"],
		"get_statuses" => [
			"method" => "get",
			"url" => "orders_statuses.json"],
		"update_status" => [
			"method" => "post",
			"url" => "orders/update_status.json",
			"data_structure" => [
				"orders_id" => true,
				"status_id" => true,
				"status_message" => false]],

		// Customers
		"get_customers" => [
			"method" => "get",
			"url" => "customers.json"],
		"get_customer_by_id" => [
			"method" => "get",
			"url" => "customers/:id.json"],

		// Cart
		"products_in_cart" => [
			"method" => "get",
			"url" => "cart/:id.json"],

		// Modules
		"get_module_type" => [
			"method" => "get",
			"url" => "modules/:type.json"]
	];

	public function __construct($api_key, $store_url = "https://mystore-api.no/") {
        if(class_exists('Memcached')) {
            // Memcached setup
            $this->memcached = new Memcached();
            $this->memcached->addServer("localhost", 11211);
        }
        
		$this->set("api_key", $api_key);
		$this->set("store_url", $store_url);
	}
    
    /**
     *  Save a member-value, and put it in memcached if present
     *  @param string $name The name of the variable
     *  @param mixed $value The value of the variable
     *  @return void;
     */
    public function set($name, $value) {
        if($this->memcached !== false) {
            $this->memcached->set("MystoreApiCache_".$name, $value);
        } else {
            $this->name = $value;
        }
    }
    
    /**
     *  Extract a member-value from memcached if present, or directly from the class if memcached is not installed
     *  @param string $name The name of the variable to extract
     *  @return mixed The value of the extracted variable
     */
    public function get($name) {
        if($this->memcached !== false) {
            return $this->memcached->get("MystoreApiCache_".$name);
        } else {
            return $this->$name;
        }
    }

	/**
	 * Executes the API http-request
	 * @param string $type 
	 * @param Array $args 
	 * @return Array The JSON decoded response from the server
	 */
	public function api($type, Array $args = []) {
		$input = $this->type($type);
		$options = [];

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $input["url"]);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL self-signature
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		if($input["method"] == "post") {
			curl_setopt($handle, CURLOPT_POST, true);
			$data = $this->buildData($type, $args);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
		}

		$result = curl_exec($handle);

		if(!$result) {
			// cURL error
			throw new MystoreAPI_exception("cURL error: ".curl_error($handle));
		}

		curl_close($handle);

		// Translate JSON
		$r = $this->response($result);

		// Check for errors
		$this->error($r);
		return $r;
	}

	public function test($type, Array $args = []) {
	  $input = $this->type($type);
	  $options = [];

	  $request = [];
	  $request["url"] = $input["url"];

      echo sizeof($args["product_data"]);
	  if($input["method"] = "post") {
	    $request["data"] = $this->buildData($type, $args);
	  }
      //pre_print_r("Data length: ".strlen($request["data"]["product_data"]));
      
      $test = json_decode($request["data"]["product_data"]);
      if(sizeof($test) == sizeof($args["product_data"]));
        pre_print_r("All tests passed!");
      
	  return $request;
	}

	/**
	 * Throws an exception if the response returned with an error and not a 200 code
	 * @param Array $response 
	 * @return void
	 */
	private function error($response) {
		if(property_exists($response, "error") AND $response->code != 200) {
			throw new MystoreAPI_exception("Error ".$response->code."<br />".$response->error->message);
		}
	}

	/**
	 * Decodes the JSON information from the response into a usable PHP array
	 * @param string $data The input JSON string
	 * @return Array The output PHP Array
	 */
	private function response($data) {
		return json_decode($data);
	}

	/**
	 * Returns the appropriate API request header information for use in the request adding the API key to the URL and the custom store URL if present
	 * @param sting $type The type of API request
	 * @return Array The output API header information
	 */
	private function type($type) {
		$t = $this->api_map[$type];
		$t["url"] = $this->get("store_url") . $t["url"] . "?api_key=".$this->get("api_key");
		return $t;
	}

	/**
	 * Validates and builds the post-data needed for API requests trough post
	 * @param string $type The type of API request
	 * @param Array $input_data The input data-array
	 * @return Array The generated data-array ready for posting to the server
	 */
	private function buildData($type, $input_data) {
		if(!array_key_exists("data_structure", $this->api_map[$type]))
			return;

		// Validate data-structure
		$data_struct = $this->api_map[$type]["data_structure"];
		$output_data = [];

		foreach($data_struct as $key => $value) {
			if($value === true) {
				if(!array_key_exists($key, $input_data))
					throw new MystoreAPI_exception("Missing required key ".$key." on request for ".$type);
			} elseif(is_array($value)) {
                foreach($value as $typekey => $typevalue) {
                    for($i=0; $i<sizeof($input_data[$key]); $i++) {
                        if(strtolower(gettype($input_data[$key][$i][$typekey])) !== strtolower($typevalue)) {
                            throw new MystoreAPI_exception("Wrong datatype for ".$typekey.". Excepcted type is ".$typevalue." but got ".gettype($input_data[$key][$i][$typekey]));
                        }
                    }
                }
            }

			if(array_key_exists($key, $input_data)) {
			  $output_data[$key] = json_encode($input_data[$key]);
			}
		}

		return $output_data;
	}
}

Class MystoreAPI_exception extends Exception {
    public function __toString() {
        ob_end_clean();
        echo "<pre>" . __CLASS__ . ": " . $this->getMessage() . " | file: " .$this->getFile() . " line: " . $this->getLine() . "\n" . $this->getTraceAsString()."</pre>";
        exit;
        return "";
    }
}

?>