<?php

/**
 * Helper class for specifying the price of a product including the parameters that
 * determine a product's fixed prices, and the rules that determine it's dynamic pricing.
 */

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

include_once('../Lasercommerce_Pricing.php');

class Lasercommerce_Price_Spec {
	
	/**
	 * The array specifying the pricing of a product, here is it's format:
	 *
	 * 	pricing := {
 	 *		<role>:<pricing>,
	 *		...
	 *	}
	 */
	private $pricing;

	public $optionNamePrefix;
	public $id;

	/**
	 * fills this class' price spec array with data from a given product's metadata
	 * @param mixed $postID The postID of the product
	 * @param string $tier_slug The tier of the price_spec
	 */
	public function __construct($postID){
		global $Lasercommerce_Plugin;
		if(!isset($Lasercommerce_Plugin)){
			// Then dependency lasercommerce is not configured correctly
			// TODO: Handle this 
			$Lasercommerce_Plugin = new Lasercommerce_Plugin();
		}

		$this->optionNamePrefix = $Lasercommerce_Plugin->getOptionNamePrefix(); 

        $this->postID = sanitize_key($postID);

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
				$this->pricing[$role] = new Lasercommerce_Pricing(unserialize($params));
			}
		}
		$this->dynamics = array();
		if(isset($price_spec['dynamics'])){
			foreach ($price_spec['dynamics'] as $dynamicID => $dynamic) {
				$this->dynamics[$dynamicID] = new Lasercommerce_Dynamic(unserialize($dynamic));
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

		// if(WP_DEBUG) error_log("saving price spec.");
		// if(WP_DEBUG) error_log(" -> pricing: ".esc_attr(serialize($this->pricing)));
		// if(WP_DEBUG) error_log(" -> dynamics ".esc_attr(serialize($this->dynamics)));


		foreach ($this->pricing as $role => $pricing) {
			$price_spec['pricing'][$role] = "$pricing";
		}

		foreach ($this->dynamics as $dynamicID => $dynamic) {
			$price_spec['dynamics'][$dynamicID] = $dynamic.__toString();
		}

		update_post_meta( $this->postID, $this->get_meta_name(), serialize($price_spec) );
	}

	public static function is_valid_pricing($pricing){
		// if(WP_DEBUG) error_log("type of pricing: ".gettype($pricing));
		// if(WP_DEBUG) error_log("class of pricing: ".get_class($pricing));
		// if(WP_DEBUG) error_log("pricing methods: ".serialize(get_class_methods($pricing)));

		return true;
		return $pricing instanceof Lasercommerce_Pricing;
	}

	public static function validate_pricing($pricing){
		if(Lasercommerce_Price_Spec::is_valid_pricing($pricing)){
			return true;
		} else {
			throw new Exception("Invalid pricing object: ", 1);
			return false;
		}
	}

	public function pricing_isset($role){
		return isset($this->pricing[$role]);
	}

	public function maybe_get_pricing($role){
		if($this->pricing_isset($role)){
			return $this->pricing[$role];
		} else {
			return new Lasercommerce_Pricing(array());
		}
	}

	public function maybe_set_pricing($role, $pricing){
		if($this->validate_pricing($pricing)){
			$this->pricing[$role] = $pricing;
		}
	}

	public function maybe_get_default_pricing(){
		$regular 	= get_post_meta( $this->postID, '_regular_price', true);
		$sale 		= get_post_meta( $this->postID, '_sale_price', true);
		$sale_price_dates_from 	= get_post_meta( $this->postID, '_sale_price_dates_from', true);
		$sale_price_dates_to	= get_post_meta( $this->postID, '_sale_price_dates_to', true);

		$params = array();
		if($regular and $regular != '') $params['regular'] = $regular;
		if($sale and $sale != '') $params['sale'] = $sale;
		if($sale_price_dates_from and $sale_price_dates_from != '') $params['sale_price_dates_from'] = $sale_price_dates_from;
		if($sale_price_dates_to and $sale_price_dates_to != '') $params['sale_price_dates_to'] = $sale_price_dates_to;
		// if(WP_DEBUG) error_log("maybe_get_default_pricing returned ".serialize($params));
		return new Lasercommerce_Pricing($params);
	}
}