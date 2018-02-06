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

if( !defined('LASERCOMMERCE_DP_DEBUG')){
    define( 'LASERCOMMERCE_DP_DEBUG', False);
}

class Lasercommerce_Integration_Dynamic_pricng extends Lasercommerce_Abstract_Child {
    private $_class = "LC_DP_";

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
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."WP_INIT",
        ));
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);
    }

    public function patched_dp_add_price_filters(){
        $dp_instance = $this->get_integration_instance();

        //Filters the regular variation price
        add_filter( 'woocommerce_product_variation_get_price', array( $dp_instance, 'on_get_product_variation_price' ), 10, 2 );
        // add_filter( 'woocommerce_product_variation_get_price', array( $this, 'patched_dp_on_get_product_variation_price' ), 10, 2 );

        //Filters the regular product get price.
        add_filter( 'woocommerce_product_get_price', array( $dp_instance, 'on_get_price' ), 10, 2 );
        // add_filter( 'woocommerce_product_get_price', array( $this, 'patched_dp_on_get_price' ), 10, 2 );
    }

    public function patched_dp_remove_price_filters(){
        $dp_instance = $this->get_integration_instance();

        //Filters the regular variation price
        remove_filter( 'woocommerce_product_variation_get_price', array( $dp_instance, 'on_get_product_variation_price' ), 10, 2 );
        remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'patched_dp_on_get_product_variation_price' ), 10, 2 );

        //Filters the regular product get price.
        remove_filter( 'woocommerce_product_get_price', array( $dp_instance, 'on_get_price' ), 10, 2 );
        remove_filter( 'woocommerce_product_get_price', array( $this, 'patched_dp_on_get_price' ), 10, 2 );
    }

    public function patched_dp_on_get_price($base_price, $_product, $force_calculation = false){
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCHED_DP_ON_GET_PRICE",
        ));
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);


        global $product;

        $dp_instance = $this->get_integration_instance();

        $composite_ajax = did_action('wp_ajax_woocommerce_show_composited_product') | did_action('wp_ajax_nopriv_woocommerce_show_composited_product') | did_action('wc_ajax_woocommerce_show_composited_product');

	    $result_price = $base_price;

        //Cart items are discounted when loaded from session, check to see if the call to get_price is from a cart item,
        //if so, return the price on the cart item as it currently is.
        $cart_item = WC_Dynamic_Pricing_Context::instance()->get_cart_item_for_product( $_product );
        if ( ! $force_calculation && $cart_item ) {
            // TODO: Do we need to patch remove_price_filters and add_price_filters?
            $this->patched_dp_remove_price_filters();
            $cart_price = $cart_item['data']->get_price();
            $this->patched_dp_add_price_filters();
            // $cart_price = $this->actuallyGetPrice('', $cart_item['data']);
            $context['return'] = serialize($cart_price);
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureEnd("return cart price", $context);
            return $cart_price;
        }

        //Is Product check so this does not run on the cart page.  Cart items are discounted when loaded from session.
        if ((is_object($product) && $product->get_id() == $_product->get_id()) || (function_exists('is_shop') && is_shop()) || is_product() || is_tax() || $force_calculation || $composite_ajax) {
	        // $cache_id = $_product->get_id() . spl_object_hash( $_product );
            // if ( isset( $dp_instance->cached_adjustments[ $cache_id ] ) && ! empty( $dp_instance->cached_adjustments[ $cache_id ] ) ) {
			// 	return $dp_instance->cached_adjustments[ $cache_id ];
			// }

            $discount_price = false;
            $working_price  = $base_price;

            $modules = apply_filters('wc_dynamic_pricing_load_modules', $dp_instance->modules);
            foreach ($modules as $module) {
                if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug(
                    sprintf("after module %s, working: %s, discount: %s", get_class($module), serialize($working_price), serialize($discount_price)),
                    $context
                );

                if ($module->module_type == 'simple') {
                    //Make sure we are using the price that was just discounted.
                    $working_price = $discount_price ? $discount_price : $base_price;
                    $working_price = $module->get_product_working_price($working_price, $_product);
                    if ($working_price !== false) {
                        $discount_price = $module->get_discounted_price_for_shop($_product, $working_price);
                    }
                }
            }

	        if ( $discount_price !== false ) {
		        $result_price = $discount_price;
	        }

	        // $dp_instance->cached_adjustments[ $cache_id ] = $result_price;
        }

        $context['return'] = serialize($result_price);
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureEnd("", $context);
	    return $result_price;
    }

    private function patched_dp_get_discounted_price( $base_price, $_product ) {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."DP_ON_GET_DISCOUNTED_PRICE",
        ));
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);

        $dp_instance = $this->get_integration_instance();
        $id             = $_product->get_id();
        $discount_price = false;
        $working_price = $base_price;

        $modules = apply_filters( 'wc_dynamic_pricing_load_modules', $dp_instance->modules );
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug(
            sprintf("before modules working: %s, discount: %s", serialize($working_price), serialize($discount_price)),
            $context
        );
        foreach ( $modules as $module ) {
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug( sprintf("before module %s", get_class($module)), $context );
            if ( $module->module_type == 'simple' ) {
                //Make sure we are using the price that was just discounted.
                $working_price = $discount_price ? $discount_price : $base_price;
                $working_price = $module->get_product_working_price( $working_price, $_product );
                if ( floatval( $working_price ) ) {
                    $discount_price = $module->get_discounted_price_for_shop( $_product, $working_price );
                }
            }
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug(
                sprintf("after module %s, working: %s, discount: %s", get_class($module), serialize($working_price), serialize($discount_price)),
                $context
            );
        }

        if ( $discount_price ) {
            return $discount_price;
        } else {
            return $base_price;
        }
    }

    /**
     * Filters the variation price from WC_Product_Variable->get_variation_prices()
    */
    public function patched_dp_on_get_variation_prices_price( $price, $variation ) {
        return $this->patched_dp_get_discounted_price( $price, $variation );
    }


    public function patched_dp_on_get_product_variation_price( $base_price, $_product ) {
        return $this->patched_dp_on_get_price( $base_price, $_product, false );
    }

    public function patched_dp_on_get_product_is_on_sale( $is_on_sale, $_product ) {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCHED_DP_ON_GET_PRODUCT_IS_ON_SALE",
        ));

        if ( $is_on_sale ) {
            return $is_on_sale;
        }

        if ( $_product->is_type( 'variable' ) ) {
            $is_on_sale = false;
            $prices = $_product->get_variation_prices();

            $regular = array_map('strval', $prices['regular_price']);
            $actual_prices = array_map('strval', $prices['price']);

            $diff = array_diff_assoc($regular, $actual_prices);

            if (!empty($diff)) {
                $is_on_sale = true;
            }

        } else {
            $dp_instance = $this->get_integration_instance();
            $lc_price =  $this->plugin->maybeGetPrice('', $_product);
            // $dynamic_price = $this->patched_dp_on_get_price( $lc_price, $_product, true );
            $dynamic_price = $this->patched_dp_on_get_price( $lc_price, $_product, true);
            $regular_price = $_product->get_regular_price();

            if(LASERCOMMERCE_DP_DEBUG) {
                $this->procedureDebug(
                    "lc_price: ".serialize($lc_price)
                        ."; dynamic_price: ".serialize($dynamic_price)
                        ."; regular_price: ".serialize($regular_price),
                    $context
                );
            }

            if ( empty( $regular_price ) || empty( $dynamic_price ) ) {
                return $is_on_sale;
            } else {
                $is_on_sale = $regular_price != $dynamic_price;
            }
        }

        return $is_on_sale;
    }

    public function patched_dp_on_cart_loaded_from_session( $cart ) {
        $dp_instance = $this->get_integration_instance();

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCH_DP_ON_CART",
        ));

        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);

        $sorted_cart = array();
        if ( sizeof( $cart->cart_contents ) > 0 ) {
            foreach ( $cart->cart_contents as $cart_item_key => &$values ) {
                if ( $values === null ) {
                    continue;
                }

                if ( isset( $cart->cart_contents[ $cart_item_key ]['discounts'] ) ) {
                    unset( $cart->cart_contents[ $cart_item_key ]['discounts'] );
                }

                $sorted_cart[ $cart_item_key ] = &$values;
            }
        }

        if ( empty( $sorted_cart ) ) {
            return;
        }

        //Sort the cart so that the lowest priced item is discounted when using block rules.
        @uasort( $sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price' );

        $modules = apply_filters( 'wc_dynamic_pricing_load_modules', $dp_instance->modules );
        if(LASERCOMMERCE_DP_DEBUG) {
            $old_cart = WC()->cart->cart_contents;
            $this->procedureDebug("first cart: \n".serialize($old_cart), $context);
        }
        foreach ( $modules as $module ) {
            if(LASERCOMMERCE_DP_DEBUG) {
                $this->procedureDebug( "module: ".get_class($module), $context );
            }
            $module->adjust_cart( $sorted_cart );
            if(LASERCOMMERCE_DP_DEBUG) {
                if(WC()->cart->cart_contents != $old_cart){
                    $old_cart = WC()->cart->cart_contents;
                    $this->procedureDebug( "cart changed: \n".serialize($old_cart), $context );
                }
            }
        }

        //Reset the subtotal on ajax requests to force the mini cart to refresh itself.
        if ( defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) {
            $cart->subtotal = false;
        }
    }

    public function patched_dp_on_calculate_totals( $cart ) {
        $dp_instance = $this->get_integration_instance();

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCH_DP_ON_CALCULATE",
        ));

        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);

		$sorted_cart = array();
		if ( sizeof( $cart->cart_contents ) > 0 ) {
			foreach ( $cart->cart_contents as $cart_item_key => $values ) {
				if ( $values != null ) {
					$sorted_cart[ $cart_item_key ] = $values;
				}
			}
		}

		if ( empty( $sorted_cart ) ) {
			return;
		}

		//Sort the cart so that the lowest priced item is discounted when using block rules.
		uasort( $sorted_cart, 'WC_Dynamic_Pricing_Cart_Query::sort_by_price' );

		$modules = apply_filters( 'wc_dynamic_pricing_load_modules', $dp_instance->modules );
        if(LASERCOMMERCE_DP_DEBUG) {
            $old_cart = WC()->cart->cart_contents;
            $this->procedureDebug("first cart: \n".serialize($old_cart), $context);
        }
		foreach ( $modules as $module ) {
            if(LASERCOMMERCE_DP_DEBUG) {
                $this->procedureDebug("module: ".get_class($module), $context);
            }
            $module->adjust_cart( $sorted_cart );
            if(LASERCOMMERCE_DP_DEBUG) {
                if(WC()->cart->cart_contents != $old_cart){
                    $old_cart = WC()->cart->cart_contents;
                    $this->procedureDebug( "cart changed: \n". serialize($old_cart, true), $context );
                }
            };
		}
	}

    /**
     * This function will check if LC discounts should be applied first.  If so, the LC price is used as the base price that is passed back to
     * Dynamic Pricing.  Dynamic Pricing will use the result of this function as the base for all it's calcuations for the product.
     * @param $base_price
     * @param $cart_item
     *
     * @return float|null
     */
    public function on_get_price_to_discount( $base_price, $cart_item ) {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ON_GET_PRICE_DISCOUNT",
        ));

        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);

        $cart_item = WC_Dynamic_Pricing_Context::instance()->get_cart_item_for_product($cart_item['data']);

        $calculated_price = $this->plugin->actuallyGetPrice('', $cart_item['data']);

        $response = empty($calculated_price) ? $base_price : $calculated_price;

        $context['return'] = $response;
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureEnd('', $context);
        return $response;
    }

    public function patchDynamicPricing(){
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCH_DP",
        ));

        if( $this->detect_target() ){
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug("PATCHING", $context);
            $dp_instance = $this->get_integration_instance();
            $this->patchFilter(
                'woocommerce_product_is_on_sale',
                array(&$dp_instance , 'on_get_product_is_on_sale'),
                array(&$this, 'patched_dp_on_get_product_is_on_sale'),
                10,
                2
            );
            $this->patchFilter(
                'woocommerce_product_get_price',
                array(&$dp_instance , 'on_get_price'),
                array(&$this, 'patched_dp_on_get_price'),
                10,
                2
            );
            $this->patchFilter(
                'woocommerce_product_variation_get_price',
                array(&$dp_instance , 'on_get_product_variation_price'),
                array(&$this, 'patched_dp_on_get_product_variation_price'),
                10,
                2
            );
            $this->patchAction(
                'woocommerce_cart_loaded_from_session',
                array(&$dp_instance, 'on_cart_loaded_from_session'),
                array(&$this, 'patched_dp_on_cart_loaded_from_session'),
                98,
                1
            );
            // $this->patchFilter(
            //     'woocommerce_before_calculate_totals',
            //     array(&$dp_instance, 'on_calculate_totals'),
            //     array(&$this, 'patched_dp_on_calculate_totals'),
            //     98,
            //     1
            // );
            // add_filter( 'woocommerce_variation_prices_price', array( $this, 'on_get_variation_prices_price' ), 10, 3 );
            $this->patchFilter(
                'woocommerce_variation_prices_price',
                array(&$dp_instance, 'on_get_variation_prices_price'),
                array(&$this, 'patched_dp_on_get_variation_prices_price'),
                10,
                3
            );
        } else {
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug("NOT PATCHING", $context);
        }
    }

    public function patchLasercommerce() {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."PATCH_LC",
        ));

        if( $this->plugin ){
            if(LASERCOMMERCE_DEBUG) $this->procedureDebug("PATCHING", $context);
            $this->patchFilter(
                'woocommerce_product_get_price',
                array(&$this->plugin, 'actuallyGetPrice'),
                array(&$this->plugin, 'maybeGetPrice'),
                0,
                2
            );
            $this->patchFilter(
                'woocommerce_product_get_regular_price',
                array(&$this->plugin, 'actuallyGetRegularPrice'),
                array(&$this->plugin, 'maybeGetRegularPrice'),
                0,
                2
            );
            $this->patchFilter(
                'woocommerce_product_get_sale_price',
                array(&$this->plugin, 'actuallyGetSalePrice'),
                array(&$this->plugin, 'maybeGetSalePrice'),
                0,
                2
            );
            $this->patchFilter(
                'woocommerce_product_variation_get_price',
                array(&$this->plugin, 'actuallyGetPrice'),
                array(&$this->plugin, 'maybeGetPrice'),
                0,
                2
            );
            $this->patchFilter(
                'woocommerce_product_variation_get_regular_price',
                array(&$this->plugin, 'actuallyGetRegularPrice'),
                array(&$this->plugin, 'maybeGetRegularPrice'),
                0,
                2
            );
            $this->patchFilter(
                'woocommerce_product_variation_get_sale_price',
                array(&$this->plugin, 'actuallyGetSalePrice'),
                array(&$this->plugin, 'maybeGetSalePrice'),
                0,
                2
            );
        }
    }

    public function constructTraces() {
        $this->traceFilter('woocommerce_product_is_on_sale');
        $this->traceFilter('woocommerce_coupon_is_valid');
        $this->traceFilter('woocommerce_coupon_is_valid_for_product');
        $this->traceAction('woocommerce_before_calculate_totals');
        $this->traceAction('woocommerce_dynamic_pricing_apply_cartitem_adjustment');
        // $this->traceAction('woocommerce_dynamic_pricing_apply_cartitem_adjustment', 4);
        $this->traceFilter('woocommerce_get_cart_item_from_session');
        $this->traceFilter('woocommerce_cart_item_price');
        $this->traceFilter('woocommerce_dynamic_pricing_get_price_to_discount');
        $this->traceFilter('woocommerce_dynamic_pricing_is_rule_set_valid_for_user');
        $this->traceFilter('woocommerce_dynamic_pricing_get_rule_amount');
        $this->traceFilter('wc_dynamic_pricing_apply_cart_item_adjustment');
        $this->traceAction('wc_memberships_discounts_disable_price_adjustments');
        $this->traceAction('wc_memberships_discounts_enable_price_adjustments');
        $this->traceAction('wc_dynamic_pricing_apply_membership_discounts_first');

        $this->traceFilter('woocommerce_dynamic_pricing_is_cumulative');
        $this->traceFilter('wc_dynamic_pricing_get_product_pricing_rule_sets');
        $this->traceFilter('wc_dynamic_pricing_get_cart_item_pricing_rule_sets');
        $this->traceFilter('woocommerce_dynamic_pricing_process_product_discounts');
        $this->traceFilter('wc_dynamic_pricing_get_use_sale_price');
    }

    public function addActionsAndFilters() {
        // must be called after wp_init to detect other plugins

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ADD_ACTIONS_FILTERS",
        ));
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);

        if(!$this->detect_target()){
            if(LASERCOMMERCE_DP_DEBUG) $this->procedureDebug('could not detect target', $context);
            return;
        }

        if(LASERCOMMERCE_DP_DEBUG) {
            $this->constructTraces();
        }

        add_action('woocommerce_dynamic_pricing_get_price_to_discount', array(&$this, 'on_get_price_to_discount'), 0, 2);

        // add_action('wc_memberships_discounts_disable_price_adjustments', array(&$this, 'patched_dp_remove_price_filters'));
        // add_action('wc_memberships_discounts_enable_price_adjustments', array(&$this, 'patched_dp_add_price_filters'));

        $this->patchDynamicPricing();
        $this->patchLasercommerce();

        //Filter / Action research:
        //DYNAMIC PRICING
        //---------------
        //on_cart_loaded_from_session
        //
        // add_action( 'woocommerce_cart_loaded_from_session',
        //Handled by on_calculate_totals
        // add_action( 'woocommerce_before_calculate_totals',
        //Handled by on_price_html
        // add_filter( 'woocommerce_grouped_price_html',
        // add_filter( 'woocommerce_variation_price_html',
        // add_filter( 'woocommerce_sale_price_html',
        // add_filter( 'woocommerce_price_html',
        // add_filter( 'woocommerce_variation_price_html',
        // add_filter( 'woocommerce_variation_sale_price_html',
        //Handled by on_get_price
        // add_filter( 'woocommerce_get_price',
        //Filters used by ...
        // add_filter( 'woocommerce_get_price_html',
        // add_filter( 'woocommerce_get_variation_price'
        // add_filter( 'woocommerce_variable_price_html',
        // add_filter( 'woocommerce_variation_price_html',
        // add_filter( 'woocommerce_variation_sale_price_html',
        // add_filter( 'woocommerce_grouped_price_html',
        // add_filter( 'woocommerce_sale_price_html',
        // add_filter( 'woocommerce_price_html',
        // add_filter( 'woocommerce_variable_empty_price_html',
        // add_filter( 'woocommerce_order_amount_item_subtotal'

        // add_filter( 'woocommerce_product_is_on_sale', array( $this, 'on_get_product_is_on_sale' ), 10, 2 );
        // add_filter( 'woocommerce_composite_get_price', array( $this, 'on_get_composite_price' ), 10, 2 );
        // add_filter( 'woocommerce_composite_get_base_price', array( $this, 'on_get_composite_base_price' ), 10, 2 );
        // add_filter( 'woocommerce_coupon_is_valid', array( $this, 'check_cart_coupon_is_valid' ), 99, 2 );
        // add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'check_coupon_is_valid' ), 99, 4 );
    }
}
