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

if( !defined('LASERCOMMERCE_IM_DEBUG')){
    define( 'LASERCOMMERCE_IM_DEBUG', False);
}

class Lasercommerce_Integration_Memberships extends Lasercommerce_Abstract_Child {
    private $_class = "LC_IM_";

    private static $instance;

    public static $integration_target = 'WC_Memberships';
    protected static $integration_instance = null;

    public static function init() {
        if ( self::$instance == null ) {
            self::$instance = new Lasercommerce_Integration_Memberships();
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
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."WP_INIT",
        ));
        if(LASERCOMMERCE_IM_DEBUG) $this->procedureStart('', $context);
    }


    public function constructTraces() {
        $this->traceAction('wc_memberships_discounts_disable_price_adjustments');
        $this->traceAction('wc_memberships_discounts_enable_price_adjustments');
        $this->traceAction('wc_memberships_get_discounted_price');
    }

    public function addActionsAndFilters() {
        // must be called after wp_init to detect other plugins

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ADD_ACTIONS_FILTERS",
        ));
        if(LASERCOMMERCE_IM_DEBUG) $this->procedureStart('', $context);

        if(!$this->detect_target()){
            if(LASERCOMMERCE_IM_DEBUG) $this->procedureDebug('could not detect target', $context);
            return;
        }

        if(LASERCOMMERCE_IM_DEBUG) {
            $this->constructTraces();
        }

        // MEMBERSHIPS STUFF
        // add_filter( 'woocommerce_product_is_visible', array( $this, 'product_is_visible' ), 10, 2 );
        // add_filter( 'woocommerce_variation_is_visible', array( $this, 'variation_is_visible' ), 10, 2 );
        // remove_filter( 'woocommerce_product_is_visible', array('WC_Memberships_Restrictions', 'product_is_visible'));
        // remove_filter( 'woocommerce_variation_is_visible', array('WC_Memberships_Restrictions', 'variation_is_visible'));
    }

}
