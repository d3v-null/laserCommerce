<?php

/*

LaserCommerce Copyright (c) 2014, Derwent Laserphile
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Permission is granted to do so by the copyright holder, Laserphile
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

include_once('Lasercommerce_Plugin.php');

class Lasercommerce_Pricing {

	private $id;
	private $role;
	private $optionNamePrefix;// 

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
		global $Lasercommerce_Plugin;
		if(!isset($Lasercommerce_Plugin)){
			$Lasercommerce_Plugin = new Lasercommerce_Plugin();
		}
		$this->id = $id;
		$this->role = $role;
		$this->optionNamePrefix = $Lasercommerce_Plugin->getOptionNamePrefix();
	}

	private function get_meta_key($key){
		//default role
		if($this->role){
			return $this->optionNamePrefix . $this->role . '_' . $key;
		} else {
			return '_'.$key;
		}

	}

	public function __get($key){
		$defaults = array(
			'regular_price'=>'',
			'sale_price'=>'',
			'sale_price_dates_from'=>'',
			'sale_price_dates_to'=>'',
			'tax_status'=>'taxable'
		);
		if( in_array($key, array_keys($defaults) ) ){
			$meta_key = $this->get_meta_key($key);
			// if(WP_DEBUG) error_log("getting $meta_key from ".$this->id);
			$value = get_post_meta($this->id, $meta_key, true); 
			$value = $value ? $value : $defaults[$key];
		} else if(in_array($key, array('id'))) {
			$value = $this->id;
		} else {
			$value = '';//get_post_meta($this->id, $this->get_meta_key($key), true) )
		}
		// if(WP_DEBUG) error_log("get $key returned $value");
		return $value;
	}

	public function __isset($key){
		$value = $this->__get($key);
		return (bool)($value);
	}

	public function __set($key, $value){
		//validate value
		switch ($key) {
			case 'regular_price':
			case 'sale_price':
				$this->validate_price($value);
				break;
			case 'sale_price_dates_from':
			case 'sale_price_dates_to':
				$this->validate_timestamp($value);
				break;
			case 'tax_status':
				$this->validate_tax_status($value);
				break;
			default:
				throw new Exception("Invalid key: $key", 1);
				break;
		}
		// if(WP_DEBUG) error_log("set $key to $value");
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
		return join(" ", array(
			"R" . $this->regular_price, 
			"S" . $this->sale_price ,
			"F" . $this->sale_price_dates_from ,
			"T" . $this->sale_price_dates_to,
			"C" . $this->maybe_get_current_price()
		));
	}
	
	public function is_sale_active_now(){
		$sale = isset($this->sale_price)?$this->sale_price:null;
		if($sale){
			$from = isset($this->sale_price_dates_from)?$this->sale_price_dates_from:null;
			$to = isset($this->sale_price_dates_to)?$this->sale_price_dates_to:null;
			$date = new DateTime();

			$now = $date->getTimeStamp();
			if($now){
				if($from){
					if(($from) > ($now)){
						return false;
					}
				}
				if($to){
					if(($to) < ($now)){
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