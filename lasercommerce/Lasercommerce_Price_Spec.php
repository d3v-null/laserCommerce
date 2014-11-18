<?php

/**
 * Helper class for specifying the price of a product including the parameters that
 * determine a product's fixed prices, and the rules that determine it's dynamic pricing.
 */
class Lasercommerce_Price_Spec {
	
	/**
	 * The array specifying the pricing of a product, here is it's format:
	 *
	 * 	pricing := {
 	 *		<role>:<pricing>,
	 *		...
	 *	}
	 */
	private $pricings;

	/**
	 * 	dynamics := {
	 * 		<rule ID>:<rules>,
	 * 		...
 	 *	};
	 * 	rules := {
	 * 		‘collector’: {
	 *			‘type’:<collector type>,
	 *			‘params’:<collector params>
	 * 		},
	 * 		'conditions': {
	 * 			['include':<tiers>,]
	 *			['include_ancestors':<tiers>,]
	 *			['include_descendents':<tiers>,]
	 *			['exclude_ancestors':<tiers>,]
	 *			['exclude_descendents':<tiers>,]
	 *			['active_from':<timestamp>,]
	 *			['active_to':<timestamp>]
	 * 		},
	 * 		'modifier': {
	 * 			'type':<modifier type>,
	 * 			'params':<modifier params>
	 *		}
	 * 	}
	 * 	collector type := ‘cat’ | ‘prod’ | 'var’ | ‘catweights’
	 * 	modifier type := 
	 */
	private $dynamics;

	public $optionNamePrefix;
	public $optionNameSuffix;
	public $postID;
	public $tier_slug;

	/**
	 * fills this class' price spec array with data from a given product's metadata
	 * @param mixed $postID The postID of the product
	 * @param string $tier_slug The tier of the price_spec
	 */
	public function __construct($postID){
		global $Lasercommerce_Plugin;
		if(!isset($Lasercommerce_Plugin)){
			$Lasercommerce_Plugin = new Lasercommerce_Plugin();
			// Then dependency lasercommerce is not configured correctly
			// TODO: Handle this 
		}

		$this->optionNamePrefix = $Lasercommerce_Plugin->getOptionNamePrefix(); 
		$this->optionNameSuffix = "_price_spec";

        $this->postID = sanitize_key($postID);
        $this->tier_slug = sanitize_key($tier_slug);

		$this->load();
	}

	/**
	 * Returns the meta identifier used to store this field
	 * @return string $meta_name Name of the meta field
	 */
	public function get_meta_name(){
		return $this->optionNamePrefix . $this->optionNameSuffix;
	}

	/**
	 * Loads the price_spec array from the postID and tier_slug given in construction
	 */
	public function load(){
		$string = get_post_meta( $this->postID, $this->get_meta_name(), true);
		$price_spec = unserialize($string);

		$this->pricing = array();
		if(isset($price_spec['pricing'])){
			foreach ($price_spec['pricing'] as $role => $params) {
				$this->pricing[$role] = new Lasercommercee_Pricing(deserialize($params));
			}
		}
		$this->dynamics = array();
		if(isset($price_spec['dynamics'])){
			foreach ($price_spec['dynamics'] as $dynamicID => $dynamic) {
				$this->dynamics[$dynamicID] = new Lasercommerce_Dynamic(deserialize($dynamic));
			}
		}
	}

	/**
	 * Turns this class' price spec into a serialized string and saves in product meta
	 */
	public function save(){
		$price_spec = array(
			'pricing' => array(),
			'dynamics' => array()
		);

		foreach ($this->pricing as $role => $pricing) {
			$price_spec[$role] = $pricing.__toString();
		}

		$price_spec = array(
			'pricing' => $this->pricing;
			'dynamics' => $this->dynamics;
		);
		update_post_meta( $this->postID, $this->get_meta_name(), serialize($price_spec) );
	}

	public static function is_valid_role($role){
		return true;
	}

	public static function validate_role($role){
		if($this->is_valid_role($role)){
			return true;
		} else {
			throw new Exception("Invalid timestamp: $timestamp", 1);
			return false;
		}
	}

	public static function is_valid_role_list($roles){
		foreach ($roles as $role) {
			if(!$this->is_valid_role($role)) return false;
		}
	}

	public static function validate_role_list($roles){
		foreach ($roles as $role) {
			$this->validate_role($role);
		}
	}

	private function maybe_get_dynamic_rules($dynamicID){
		if(isset($this->dynamics[$dynamicID])){
			return $this->dynamics[$dynamicID];
		} else {
			throw new Exception("Invalid dynamicID: $dynamicID", 1);
			return null;
		}
	}

	private function maybe_get_dynamic_conditions($dynamicID){
		$rules = $this->maybe_get_dynamic_rules($dynamicID);

		if(isset($this->dynamics[$dynamicID])){
			$rules = $this->dynamics[$dynamicID];
			return isset($rules['conditions'])?$rules['conditions']:array();
		} else {
			return array();
		}
	}

	private function maybe_get_dynamic_collector($dynamicID){
		if(isset($this->dynamics[$dynamicID])){
			$rules = $this->dynamics[$dynamicID]
		}
	}

	private function maybe_get_dynamic_condition($dynamicID, $condition){
		$conditions = $this->maybe_get_dynamic_conditions($dynamicID);
		return isset($conditions[$condition])?$conditions[$condition]:null;
	}

	public function maybe_set_dynamic_condition($dynamicID, $condition, $value){
		$conditions = $this->maybe_get_dynamic_conditions($dynamicID);
		if(!$conditions) $conditions = array();
		$conditions[$condition] = $value;
	}

	public function maybe_get_dynamic_active_from($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'active_from');
	}

	public function maybe_set_dynamic_active_from($dynamicID, $value){
		if($this->validate_timestamp($value)){
			$this->maybe_set_dynamic_condition($dynamicID, $condition, $value);
		}
	}

	public function maybe_get_dynamic_active_to($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'active_to');
	}

	public function maybe_set_dynamic_active_to($dynamicID, $value){
		if ($this->validate_timestamp($value)) {
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_active', $value);
		}
	}

	public function maybe_get_dynamic_include($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'include');
	}

	public function maybe_set_dynamic_include($dynamicID, $value){
		if($this->validate_role_list($value)){
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_include', $value);
		}
	}

	public function maybe_get_dynamic_include_ancestors($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'include_ancestors');
	}

	public function maybe_set_dynamic_include_ancestors($dynamicID, $value){
		if($this->validate_role_list($value)){
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_include_ancestors', $value);
		}
	}

	public function maybe_get_dynamic_include_descendents($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'include_descendents');
	}

	public function maybe_set_dynamic_include_descendents($dynamicID, $value){
		if($this->validate_role_list($value)){
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_include_descendents', $value);
		}
	}

	public function maybe_get_dynamic_exclude_ancestors($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'exclude_ancestors');
	}

	public function maybe_set_dynamic_exclude_ancestors($dynamicID, $value){
		if($this->validate_role_list($value)){
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_exclude_ancestors', $value);
		}
	}	

	public function maybe_get_dynamic_exclude_descendents($dynamicID){
		return maybe_get_dynamic_condition($dynamicID, 'exclude_descendents');
	}

	public function maybe_set_dynamic_exclude_descendents($dynamicID, $value){
		if($this->validate_role_list($value)){
			$this->maybe_set_dynamic_condition($dynamicID, 'dynamic_exclude_descendents', $value);
		}
	}

	public function is_dynamic_active_now($dynamicID){
		$from = $this->maybe_get_dynamic_active_from($dynamicID);
		$to = $this->maybe_get_dynamic_active_to($dynamicID);
		return is_something_active_now($from, $to);
	}

	public function is_dynamic_visible_to_role($dynamicID, $role){
		global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $optionNamePrefix );
        }

		$include = $this->maybe_get_dynamic_include($dynamicID);
		//$include_ancestors = $this->maybe_get_dynamic_include_ancestors($dynamicID);
		$include_descendents = $this->maybe_get_dynamic_include_descendents($dynamicID);
		//$exclude_ancestors = $this->maybe_get_dynamic_exclude_ancestors($dynamicID)
		$exclude_descendents = $this->maybe_get_dynamic_exclude_descendents($dynamicID);

		//TODO: This

	}

	public function is_dynamic_visible_to_roles($dynamicID, $roles){
		foreach ($roles as $role) {
			if($this->is_dynamic_visible_to_role($dynamicID, $role)) return true;
		} 
		return false;
	}

	public function get_valid_dynamics($roles){
		$dynamics = array();
		foreach ($this->dynamics as $dynamicID => $rules) {
			if ($this->is_dynamic_active_now($dynamicID) and $this->is_dynamic_visible_to_roles($dynamicID, $roles)){
				array_push($dynamics, $dynamicID)
			}
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
		
		return $quantity;
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

    public static function sort_spec_by_regular_price($spec_a, $spec_b){
        //TODO: sort by regular or sort by price??
        return $spec_a['regular'] > $spec_b['regular'];
    }
}