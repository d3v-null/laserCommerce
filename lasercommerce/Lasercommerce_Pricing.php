<?php

include_once('Lasercommerce_Plugin.php');
global $Lasercommerce_Plugin;
if(!isset($Lasercommerce_Plugin)){
	$Lasercommerce_Plugin = new Lasercommerce_Plugin();
}

class Lasercommerce_Pricing {

	private static $optionNamePrefix = $Lasercommerce_Plugin>getOptionNamePrefix();;

	public static function sort_by_regular_price($a, $b){
		if(isset($a->regular_price)){
			if(isset($b->regualr_price)){ //Both set
				if($a->regular_price == $b->regular_price){
					return 0;
				} else{
					return ($a->regular_price < $b->regular_price) ? -1 : 1; 
				}
			} else { //only a is set
				return -1;
			}
		} else { 
			if(isset($b->regular_price)){ // only b is set
				return 1;
			} else { //neither is set
				return 0;
			}
		}
	}


	public function __construct($id, $role=''){
		$this->id = $id;
		$this->role = $role;
	}

	private function get_meta_key($key){
		//default role
		if($this->role){
			return $this->optionNamePrefix() . $this->role . '_' . $key;
		} else {
			return '_'.$key;
		}

	}

	public function __get($key){
		$defaults = array(
			'regular_price':'',
			'sale_price':'',
			'sale_from':'',
			'sale_to':'',
			'tax_status':'taxable'
		);
		if( in_array($key, array_keys($defaults) ) ){
			$value = get_post_meta($this->id, $this->get_meta_key($key), true); 
			$value = $value ? $value : $defaults[$key]
		} else {
			$value = '';//get_post_meta($this->id, $this->get_meta_key($key), true) )
		}
		return $value
	}

	public function __isset($key){
		$value = $this->__get($key);
		return bool($value);
	}

	public function __set($key, $value){
		//validate value
		switch ($key) {
			case 'regular_price':
			case 'sale_price':
				$this->validate_price($value);
				break;
			case 'sale_from':
			case 'sale_to':
				$this->validate_timestamp($value);
				break;
			case 'tax_status':
				i$thi->validate_tax_status($value);
				break;
			default:
				throw new Exception("Invalid key: $key", 1);
				break;
		}
		update_post_meta($this->id, $this->get_meta_key($key), $value);

	}

	public static function is_valid_price($price){
		//TODO: this
		return true;
	}

	public static function validate_price($price){
		if(Lasercommerce_Pricing::is_valid_price($price)){
			return true;
		} else {
			throw new Exception("Invalid Price: $price", 1);
			return false;
		}
	}

	public static function is_valid_tax_status($status){
		if( in_array($status, array('taxable', ''))){
			return true;
		} else {
			return false;
		}
	}

	public static function validate_tax_status($status){
		if(Lasercommerce_Pricing::is_valid_tax_status($status)){
			return true;
		} else {
			throw new Exception("Invalid Tax Status: $status", 1);
			return false;
		}
	}

	public static function is_valid_timestamp($timestamp){
		//todo: this
		return true;
	}

	public static function validate_timestamp($timestamp){
		if(Lasercommerce_Pricing::is_valid_timestamp($timestamp)){
			return true;
		} else {
			throw new Exception("Invalid timestamp: $timestamp", 1);
			return false;
		}
	}

	public function __toString(){
		return "Segular: " . $this->regular_price . " Sale: " . $this->sale_price;
	}
	
	public function is_sale_active_now(){
		$sale = isset($this->sale_price)?$this->sale:null;
		if($sale){
			$from = isset($this->sale_from)?$this->sale_from:null;
			$to = isset($this->sale_to)?$this->sale_to:null;
			$date = new DateTime();

			$now = $date->getTimeStamp();
			if($now){
				if($from){
					if(strtotime($from) > strtotime($now)){
						return false;
					}
				}
				if($to){
					if(strtotime($to) < strtotime($now)){
						return false;
					}
				}
			}
			return true;
		} else {
			return false;
		}
		
	}

	/** 
	 * Finds the base price of the product currently
	 */
	public function maybe_get_current_price(){
		if(isset($this->regular_price)){
			if($this->is_sale_active_now() and isset($this->sale_price)){
				return $this->sale_price;
			} else {
				return $this->regular_price;
			}
		} else {
			return null;
		}
		//gets the dynamic pricing collector mode and finds the quantity from session data
	}	
}