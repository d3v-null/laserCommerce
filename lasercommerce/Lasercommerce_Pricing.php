<?php

class Lasercommerce_Pricing {

	/**
	 * Stores all the parameters that determine the fixed price of a product
	 * for a given role.
	 * params has the following layout
	 * params := {
	 * 		‘regular’:<price>, 
	 * 		[‘sale’:<price>,]
	 * 		[‘sale_from’:<timestamp>,]
	 *		['sale_to':<timestamp>,]
	 *		['tax_status':<'inc'|'exc'>]
	 *	}
	 */
	private $params;

	public function __construct($params){
		$this->params = array();
		if(isset($params['regular']) {
			$this->regular = $params['regular']);
		} else {
			throw new Exception("Cannot create pricing without regualr price", 1);
			$this->params = null;
		}

		if(isset($params['sale'])) $this->sale = $sale;
		if(isset($params['sale_from'])) $this->sale_from = $sale_from;
		if(isset($params['sale_to']))) $this->sale_to = $sale_to;

	}

	public function __get($name){
		if ($this->params and isset($this->params[$name])){
			return $this->params[$name]; 
		} else {
			//throw new Exception("Cannot get param: $param", 1);
			return null;
		}		
	}

	public function __set($name, $value){
		if(!$this->params){
			$this->params = array();
		}
		switch ($name) {
			case 'regular':
			case 'sale':
				if($this->validate_price($value)){
					$this->params[$name] = $value;
				}
				break;
			case 'sale_from':
			case 'sale_to':
				if($this->validate_timestamp($value)){
					$this->params[$name] = $value;
				}
				break;
			case 'tax_status':
				if($this->validate_tax_status($value)){
					$this->params[$name] = $value
				}
				break;
			default:
				throw new Exception("Invalid name: $name", 1);
				break;
		}
	}

	public function __isset($name){
		if(is_array($this->params)){
			return isset($this->params[$name]);
		} else {
			return false;
		}
	}

	public function __toString(){
		return serialize($this->params);
	}

	public static function is_valid_price($price){
		//TODO: this
		return true;
	}

	public static function validate_price($price){
		if(is_valid_price($price)){
			return true;
		} else {
			throw new Exception("Invalid Price: $price", 1);
			return false;
		}
	}

	public static function is_valid_tax_status($status){
		if( in_array($status, array('inc', 'exc'))){
			return true;
		} else {
			return false;
		}
	}

	public static function validate_tax_status($status){
		if(is_valid_tax_status($status){
			return true;
		} else {
			throw new Exception("Invalid Tax Status: $status", 1);
			return false;
		}
	}

	public static function is_valid_timestamp($timestamp){
		return true;
	}

	public static function validate_timestamp($timestamp){
		if($this->is_valid_timestamp($timestamp)){
			return true;
		} else {
			throw new Exception("Invalid timestamp: $timestamp", 1);
			return false;
		}
	}
	
	public function is_sale_active_now(){
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
	}

	/** 
	 * Finds the base price of the product currently
	 */
	public function maybe_get_current_price(){
		if(isset($this->regular)){
			if($this->is_sale_active_now() and isset($this->sale)){
				return $this->sale;
			} else {
				return $this->regular;
			}
		} else {
			return null;
		}
		//gets the dynamic pricing collector mode and finds the quantity from session data
	}	
