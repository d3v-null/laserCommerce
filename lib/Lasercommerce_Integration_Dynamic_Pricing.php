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

class Lasercommerce_Integration_Dynamic_pricng extends Lasercommerce_Abstract_Child {
    const _CLASS = "LC_IDP_";

    private static $instance;

    public static $integration_target = 'WC_Dynamic_Pricing';
    protected static $integration_instance = null;

    public static function init() {
        if ( self::$instance == null ) {
            self::$instance = new Lasercommerce_Integration_Dynamic_pricng();
        }
    }

    public static function instance() {
        if ( self::$instance == null ) {
            self::init();
        }

        return self::$instance;
    }

    protected $plugin;

    function __construct(){
        parent::__construct();
        $this->plugin = Lasercommerce_Plugin::instance();
        add_action( 'init', array( &$this, 'wp_init' ), 999);
    }

    public function detect_target(){
        return class_exists(self::$integration_target);
    }

    public function get_integration_instance(){
        if ( self::$integration_instance == null ){
            if( $this->detect_target() ){
                self::$integration_instance = call_user_func(array(
                    (self::$integration_target),
                    'instance'
                ));
            }
        }
        return self::$integration_instance;
    }

    public function wp_init() {
        $_procedure = self::_CLASS . "WP_INIT: ";
        if( $this->detect_target() ){
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION TARGET DETECTED");
        } else {
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION TARGET NOT DETECTED");
        }
        // $integration_instance = $this->get_integration_instance();
        // if( $integration_instance !== null ){
        //     if(LASERCOMMERCE_DEBUG) {
        //         error_log($_procedure."INTEGRATION INSTANCE OBTAINED");
        //         error_log($_procedure."INTEGRATION INSTANCE PLUGIN: ".serialize($integration_instance->plugin_url()));
        //     }
        //
        // } else {
        //     if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION INSTANCE NOT OBTAINED");
        // }
    }

    public function patched_dp_on_get_product_is_on_sale( $is_on_sale, $product ) {
        $_procedure = self::_CLASS . "PATCHED_DP_ON_GET_PRODUCT_IS_ON_SALE: ";

        if ( $is_on_sale ) {
            return $is_on_sale;
        }

        if ( $product->is_type( 'variable' ) ) {
            $is_on_sale = false;
            $prices = $product->get_variation_prices();
            if ( $prices['price'] !== $prices['regular_price'] ) {
                $is_on_sale = true;
            }
        } else {
            $dynamic_pricing_instance = $this->get_integration_instance();
            $dynamic_price = $dynamic_pricing_instance->on_get_price( $this->plugin->maybeGetPrice('', $product), $product, true );
            $regular_price = $product->get_regular_price();

            if ( empty( $regular_price ) || empty( $dynamic_price ) ) {
                return $is_on_sale;
            } else {
                $is_on_sale = $regular_price != $dynamic_price;
            }
        }

        return $is_on_sale;
    }

    public function patchDynamicPricing(){
        $_procedure = self::_CLASS . "PATCH: ";

        if( $this->detect_target() ){
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."PATCHING");

            // add_filter('woocommerce_product_is_on_sale', array(&$this, 'maybeProductIsOnSaleStart'), 0, 2);
            // add_filter('woocommerce_product_is_on_sale', array(&$this, 'maybeProductIsOnSaleEnd'), 999, 2);
            $dynamic_pricing_instance = $this->get_integration_instance();
            remove_filter( 'woocommerce_product_is_on_sale', array(&$dynamic_pricing_instance , 'on_get_product_is_on_sale'), 10, 2 );
            add_filter('woocommerce_product_is_on_sale', array(&$this, 'patched_dp_on_get_product_is_on_sale'), 10, 2);
        } else {
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."NOT PATCHING");
        }
    }

    public function addActionsAndFilters() {
        // must be called after wp_init to detect other plugins

        $_procedure = self::_CLASS . "ADD_ACTIONS_FILTERS: ";
        if( $this->detect_target() ){
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION TARGET DETECTED");
        } else {
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION TARGET NOT DETECTED");
        }

        // $integration_instance = $this->get_integration_instance();
        // if( $integration_instance !== null ){
        //     if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION INSTANCE OBTAINED");
        // } else {
        //     if(LASERCOMMERCE_DEBUG) error_log($_procedure."INTEGRATION INSTANCE NOT OBTAINED");
        // }

        $this->patchDynamicPricing();
    }

    //TODO: move Dynamic Pricing integration stuff here
}
