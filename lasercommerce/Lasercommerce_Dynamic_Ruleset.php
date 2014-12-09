<?php

include_once('Lasercommerce_Tier_Tree.php');

/**
 * Lasercommerce Dynamic Rulesset Class
 * 
 * inspired by woocommerce/includes/abstracts/abstract-wc-product.php
 */
class Lasercommerce_Dynamic_Ruleset
{	
	/** @var int The ruleset (post) ID */
	public $id;

	/** @var object The actual post object */
	public $post;

	/** 
	 * Constructor gets the post object and sets the ID for the ruleset
	 *
	 * @param int $rule ruleset ID
	 */ 
	public function __construct($rule) {
		if( is_numeric($rule)){
			$this->id 	= absint($rule);
			$this->post = get_post( $this->id );
		} else {

		}
	}

	/**
	 * __isset magic function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get magic function.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		$defaults = array(
			'applicator_type'=>'prod',
			'applicator_params'=>array(),
			'collector_type'=>'prod',
			'collector_params' => array(),
			'active_from' => '',
			'active_to' => '',
			'include_roles' => array(),
			'include_ancestors' => array()
			'include_descendents' => array(),
			'exclude_roles' => array(),
			'exclude_ancestors' => array(),
			'exclude_roles' => array()
		);

		if( in_array($key, array_keys($defaults))){
			$value = ( $value = get_post_meta($this->id, '_'.$key, true)) ? $value : $defaults[$key] ;
		} else {
			$value = get_post_meta( $this->id, '_' . $key, true );
		}
		return $value;
	}

	/**
	 * Get the rule's post data.
	 *
	 * @return object
	 */
	public function get_post_data() {
		return $this->post;
	}	
}

