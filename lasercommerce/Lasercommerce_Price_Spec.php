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
	 * 		[‘special_active’:<special active>,]
	 * 		‘dynamic’: {
	 * 			‘collector’: {
	 *				‘type’:<collector type>,
	 *				‘params’:<collector params>
	 * 			},
 	 *			‘rules’:<rules> 
 	 *		}
	 *		[‘dynamic_active’:<dynamic active>]
	 *		['tax_status':<'inc'|'exc'>]
	 *	}
	 *
	 * <special active>, <dynamic active> :=  True | False | { from’:<active from>, to’:<active to> }
	 * <collector type> := ‘cat’ | ‘prod’ | 'var’ | ‘catweights’
	 * <rules> := { ‘qty brk’:<qty brk>, ‘discount’:<discount> }
	 * <collector params> := <list of SKUs>|<list of category slugs>|<list of varIDs>|<list of weighted category slugs>
	 */
	private array $price_spec;

	public $optionNamePrefix;
	public $optionNameSuffix;
	public $postID;
	public $tier_slug

    public static function sort_spec_by_regular_price($spec_a, $spec_b){
        //TODO: sort by regular or sort by price??
        return $spec_a['regular'] > $spec_b['regular'];
    }

	/**
	 * fills this class' price spec array with data from a given product's metadata
	 * @param mixed $postID The postID of the product
	 * @param string $tier_slug The tier of the price_spec
	 */
	public function __construct($postID, $tier_slug){
		global $Lasercommerce_Plugin;
		if(!isset($Lasercommerce_Plugin)){
			$Lasercommerce_Plugin = new Lasercommerce_Plugin();
			// Then dependency lasercommerce is not configured correctly
			// TODO: Handle this 
		}

		$this->optionNamePrefix = $Lasercommerce_Plugin->getOptionNamePrefix(); 
		$this->optionNameSuffix = "_price_spec"

        $this->postID = sanitize_key($postID);
        $this->tier_slug = sanitize_key($tier_slug);

		$this->load();
	}

	/**
	 * Returns the meta identifier used to store this field
	 * @return string $meta_name Name of the meta field
	 */
	public function get_meta_name(){
		return $this->optionNamePrefix . $this->tier_slug . $this->optionNameSuffix
	}

	/**
	 * Loads the price_spec array from the postID and tier_slug given in construction
	 */
	public function load(){
		$string = get_post_meta( $this->postID, , true);
		//TODO: error checking
		$price_spec = deserialize($string);
		//TODO: error checking
		$this->price_spec = $price_spec;
	}

	/**
	 * Turns this class' price spec into a serialized string and saves in product meta
	 */
	public function save(){
		update_post_meta( $this->postID, $this->get_meta_name(), serialize($this->price_spec) );
	}

	public function __get( $key ){
		if( in_array($key, array_keys($price_spec)) ){
			if(isset($price_spec[$key])){
				return $price_spec[$key];
			} else {
				return null;
			}
		} elseif( in_array($key, array('special_active_from', 'special_active_to'))) {
			$special_active = $this['special_active'];
			if(is_null($special_active)) return null;
			switch ($key) {
				case 'special_active_from':
					return isset($special_active['from'])?$special_active['from']:null;
				case 'special_active_to':
					return isset($special_active[ 'to' ])?$special_active[ 'to' ]:null;
				default:
					return null;
			}
		} elseif( in_array($key, array('collector', 'dynamic_rules'))) {
			$dynamic = $this['dynamic'];
			if(is_null($dynamic)) return null;
			switch ($key) {
				case 'collector':
					return isset($dynamic['collector'])?$dynamic['collector']:null;
				case 'dynamic_rules':
					return isset($dynamic['rules'])?$dynamic['rules']:null;
				default:
					return null;
			}
		} elseif( in_array($key, array('collector_type', 'collector_params')) ) {
			$collector = $this['dynamic_collector'];
			if(is_null($collector)) return null;
			switch ($key) {
				case 'collector_type':
					return isset($collector['type'])?$collector['type']:null;
				case 'collector_params':
					return isset($collector['params'])?$collector['params']:null;
				default:
					return null;
			}
		} else {
			return null;
		}
	}

	public function __set( $key, $value ){
		if( in_array($key, array('regular', 'special', 'special_active', 'dynamic', 'dynamic_active', 'tax_status')) ){
			$price_spec[$key] = $value;
		} elseif (in_array($key, array('special_active_from', 'special_active_to')) ) {
			if(is_null($this['special_active'])) $this['special_active'] = array();
			switch ($key) {
				case 'special_active_from':
					$this['special_active']['from'] = $value;
					break;
				case 'special_active_to':
					$this['special_active']['to'] = $value;
					break;
				default:
					break;					
			}
			$this['special_active'] = $special_active;
		} elseif (in_array($key, array('collector', 'dynamic_rules'))) {
			if(is_null($this['dynamic'])) $this['dynamic'] = array();
			switch ($key) {
				case 'collector':
					$this['dynamic']['collector'] = $value;
					break;
				case 'dynamic_rules':
					$this['dynamic']['rules'] = $value;
					break;
				default:
					break;					
			}
		} elseif (in_array($key, array('collector_type', 'collector_params'))) {
			if(is_null($this['dynamic_collector'])) $this->dynamic_collector = array();
			switch ($key) {
				case 'collector_type':
					$this['dynamic_collector']['type'] = $value;
					break;
				case 'collector_params':
					$this['dynamic_collector']['params'] = $value;
					break;
				default:
					break;
			}
		} else {

		}
	}

	public function __isset( $key ){
		if(in_array($key, array_keys($price_spec))){
			return true;
		} elseif( in_array($key, array('special_active_from', 'special_active_to')) ){
			if(isset($this['special_active'])) {
				switch ($key) {
					case 'special_active_from':
						return isset($this['special_active']['from']);
					case 'special_active_to':
						return isset($this['special_active']['to']);
				}
			}
		} elseif( in_array($key, array('collector', 'dynamic_rules'))){
			if(isset($this['dynamic'])) {
				switch ($key) {
					case 'collector':
						return isset($this['dynamic']['collector']);
					case 'dynamic_rules':
						return isset($this['dynamic']['rules']);
				}
			} 
		} elseif( in_array($key, array('collector_type', 'collector_params')) ){
			if(isset($this['collector'])){
				switch ($key) {
					case 'collector_type':
						return isset($this['collector']['type']);
					case 'collector_params':
						return isset($this['collector']['params']);
				}
			}
		}
		return false;
	}

	/**
	 * Helper function for is_something_active_now()
	 * returns whether something is active at a given timestamp
	 * given the active times specified by $active
	 * @param array $active Specifies when something should be active
	 * @param int $timestamp Specifies time to test if active
	 * @return bool $active if something is active 
	 */
	private function is_something_active($active, $timestamp){
		if(isset($active['from']){ 
			if(strtotime($active['from']) - strtotime($timestamp) > 0){ //if it's too early
				return False;
			}
		} 
		if(isset($active['to'])){
			if(strtotime($active['from'])) - strtotime($timestamp) < 0{ //if it's too late
				return False;
			}
		}
		return True; //otherwise it's active
		break;
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
		if(isset($price_spec['special_active'])){
			return is_something_active_now($price_spec['special_active']);
		} else {
			return null;
		} 
	}

	/**
	 * Returns True or False depending on whether the dynamic pricing is active
	 * @return boolean $active Whether the special price is active
	 */
	public function is_dynamic_active_now(){
		if(isset($price_spec['dynamic_active'])){
			return is_something_active($price_spec['dynamic_active']);
		} else {
			return null;
		}
	}

	/**
	 * Calculates the price for a given collector quantity
	 * @param integer $quantity The collector quantity
	 * @return float|null $price The final price or null if cannot be calculated
	 */
	public function calculate_price($quantity){
		$base_price = is_special_active_now()?get_special_price():get_regular_price();
		if(is_dynamic_active_now() && !is_null($base_price)){
			if(isset($price_spec['dynamic'])){
				$dynamic = $price_spec['dynamic'];
				if(isset($dynamic['rules'])){
					$rules = $dynamic['rules'];
					foreach ($rules as $qty_brk => $discount) {
						if($quantity > $qty_brk){
							return (1 - $discount) * $base_price;
						}
					}
				} 
			} 
		} 
		return $base_price;
		
	}

	/**
	 * Calculates the collector quantity for this price spec for a given cart
	 * @param mixed $cart the cart to be analysed
	 * @return integer $quantity the collector quantity
	 */
	private function get_collector_quantity( $cart ){
		$type = $this['collector_type'];
		if(is_null($type)) return null;
		$params = $this->get_collector_quantity();
		if(is_null($params)) return null; //ASSUMES THAT ALL COLLECTORS REQUIRE NONEMPTY PARAMS
		if($params == 0) return 0;
		$quantity = 0;
		foreach ($cart as $cart_item_key => $values) {
			switch ($type) {
				case 'cat':
					// calculate the number of products that match the given cat list
					// TODO: basically if cart line category in cat list, $quantity++
					break;
				case 'prod':
					// calculate the number of products that match the given prod list
					// TODO: basically if cart line prod in prod list, $quantity++
					break;
				case 'var':
					// calculate the number of products that match the given var list
					// TODO: basically if cart line var in var list, $quantity++
					break;
				case 'catweights':
					// calculate the number of products that match the given cat list
					// multiplied by their given weights
					// TODO: basically if cart line cat in catweight list, $quantity += weight
					break;
				default:
					// TODO: fail if type not recognised
					break;
			}
		}
		
		return $quantity
	}

	/** 
	 * Looks up cart in session data and finds the discount for this product
	 * Given the dynamic pricing rules for this spec
	 * @param float $discount the discount given for this product
	 */
	public function calculate_current_price(){
		//TODO: this
		//gets the dynamic pricing collector mode and finds the quantity from session data
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