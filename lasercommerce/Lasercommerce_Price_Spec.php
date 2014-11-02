<?php

/**
 * Helper class for specifying the price of a product
 */
class Lasercommerce_Price_Spec {
	/**
	 * The array specifying the price of a product, here is it's format:
	 *
	 * 	price_spec: {
	 * 		‘regular’:<regular price>, 
	 * 		‘special’:<special price>,
	 * 		‘special_active’:<special active>,
	 * 		‘dynamic’: {
	 * 			‘collector’: {
	 *				‘type’:<collector type>,
	 *				‘params’:<collector params>
	 * 			},
 	 *			‘rules’:<rules> 
 	 *		}
	 *		‘dynamic_active’:<dynamic active>
	 *	}
	 *
	 * <special active>, <dynamic active> :=  True | False | { from’:<active from>, to’:<active to> }
	 * <collector type> := ‘Category’ | ‘Product’ | ‘Variation’ | ‘Category_Volume’
	 * <rules> := { ‘qty brk’:<qty brk>, ‘discount’:<discount> }
	 */
	private $price_spec;

	public function __construct(){

	}

	/**
	 * fills this class' price spec array with data from a serialized string
	 * @param string $string The string to be deserialized
	 */
	public function deserialize( $string ){

	}

	/**
	 * Turns this class' price spec into a php array
	 * @return string $serialized The serialized price spec
	 */
	public function serialize(){

	}

	/**
	 * Gets the regular price
	 */
	public function get_regular_price(){
		return $price_spec['regular'];
	}

	/**
	 * Gets the special price even if it is not active
	 * @return float $price The special price
	 */
	public function get_special_price(){
		return $price_spec['special'];
	}

	/**
	 * Helper function for is_something_active_now()
	 * returns whether something is active at a given timestamp
	 * given the active times specified by $active
	 * @param array|bool $active Specifies when something should be active
	 * @param int $timestamp Specifies time to test if active
	 * @return bool $active if something is active 
	 */
	private function is_something_active($active, $timestamp){
		switch( gettype($active) ){
			case "array":
				//TODO: this
				break;
			case "bool":
				return $active;
				break;
		}
	}


	private function is_something_active_now($active){
		$date = new DateTime();
		return is_something_active($active, $date->getTimestamp());
	}

	/**
	 * Returns True or False depending on whether the special price is active
	 * @return boolean $active Whether the special price is active
	 */
	public function is_special_active_now(){
		//TODO: this
		//get current date 
		//$active = $price_spec['special_active'];
	}

	/**
	 * Returns True or False depending on whether the dynamic pricing is active
	 * @return boolean $active Whether the special price is active
	 */
	public function is_dynamic_active_now(){
		$date = 
		return is_something_active($price_spec['dynamic_active']);
	}

	/** 
	 * Looks up cart in session data and finds the discount for this product
	 * Given the dynamic pricing rules for this spec
	 * @param float $discount the discount given for this product
	 */
	public function calculate_discount(){
		//TODO: this
	}

	/**
	 * 
	public function calculate_final_price(){
		//TODO: this
	}
	/**
	 * Generates the HTML used to display a table specifying pricing rules
	 */
	public function generate_pricing_table_html(){
		
	}
}