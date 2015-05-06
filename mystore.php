<?php

Class myStoreAPI {
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
	 		"metehod" => "get",
			"url" => "products.json"],
	   	"get_products_in_category" => [
	   		"method" => "get",
	   		"url" => "products/:category_id.json"],
   		"products_text_search" => [
   			"method" => "get",
			"url" => "products/search/:keyword.json"],
		"create_update_product" => [
			"method" => "post",
			"url" => "products/create_or_update.json",
			"data_structure" => ["product_data" => true]],
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
		$this->api_key = $api_key;
		$this->store_url = $store_url;
	}

	public function api($type, Array $args) {
		$input = $this->type($type);
		$options = [];

		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $input["url"]);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL self-signature
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

		if($input["method"] == "post") {
			curl_setopt($handle, CURLOPT_POST, true);
			// TODO: Need to create a datapattern for the different post data types
			$data = $this->buildData($type, $args);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
		}
		$result = "test";

		$result = curl_exec($handle);
//		echo curl_error($handle);
		curl_close($handle);

		return $this->response($result);
	}

	private function error($response) {
		if($response->code != "200" AND isset($response->error))
			throw new MystoreAPI_exception("Error ".$response->code."<br />".$response->error->message);
	}

	private function response($data) {
		return json_decode($data);
	}

	private function type($type) {
		$t = $this->api_map[$type];
		$t["url"] = $this->store_url . $t["url"] . "?api_key=".$this->api_key;
		return $t;
	}

	private function buildData($type, $input_data) {
		if(!array_key_exists("data_structure", $this->api_map[$type]))
			return;

		// Validate data-structure
		$data_struct = $this->api_map[$type]["data_structure"];
		$output_data = [];

		foreach($data_struct as $key => $value) {
			if($value == true) {
				if(!array_key_exists($key, $input_data))
					throw new MystoreAPI_exception("Missing required key ".$key." on request for ".$type);
			}

			if(array_key_exists($key, $input_data)) {
				$output_data[$key] = json_encode($input_data[$key]);
			}
		}

		return $output_data;
	}
}

Class MystoreAPI_exception extends Exception {

}

?>