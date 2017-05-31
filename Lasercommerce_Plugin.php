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
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once(LASERCOMMERCE_BASE.'/Lasercommerce_UI_Extensions.php');
include_once(LASERCOMMERCE_BASE.'/lib/Lasercommerce_Tier_Tree.php');
include_once(LASERCOMMERCE_BASE.'/lib/Lasercommerce_Pricing.php');
include_once(LASERCOMMERCE_BASE.'/lib/Lasercommerce_Visibility.php');
include_once(LASERCOMMERCE_BASE.'/lib/Lasercommerce_Shortcodes.php');
include_once(LASERCOMMERCE_BASE.'/integrations/Lasercommerce_Integration_Memberships.php');
include_once(LASERCOMMERCE_BASE.'/integrations/Lasercommerce_Integration_Dynamic_Pricing.php');
include_once(LASERCOMMERCE_BASE.'/integrations/Lasercommerce_Integration_Gravityforms.php');
include_once(LASERCOMMERCE_BASE.'/integrations/Lasercommerce_Integration_Composite_Products.php');
include_once(LASERCOMMERCE_BASE.'/includes/data-stores/class-lc-product-variable-data-store-cpt.php');
// include_once(WOOCOMMERCE_BASE.'/includes/class-wc-product-variable.php')

/**
* Registers Wordpress and woocommerce hooks to modify prices
*/
class Lasercommerce_Plugin extends Lasercommerce_UI_Extensions {

    private static $instance;

    public static function init() {
        if ( self::$instance == null ) {
            self::$instance = new Lasercommerce_Plugin();
        }
        parent::init();
    }

    public static function instance() {
        if ( self::$instance == null ) {
            self::init();
        }

        return self::$instance;
    }

    private $_class = "LC_PL_";

    protected $tree;
    protected $visibility;
    protected $integration_dp;
    protected $integration_cp;
    protected $integration_m;
    protected $integration_gf;
    protected $shortcodes;

    public function initChildren(){
        $this->tree = Lasercommerce_Tier_Tree::instance();
        $this->visibility = Lasercommerce_Visibility::instance();
        $this->integration_dp = Lasercommerce_Integration_Dynamic_pricng::instance();
        $this->integration_cp = Lasercommerce_Integration_Composite_Products::instance();
        $this->integration_m = Lasercommerce_Integration_Memberships::instance();
        $this->integration_gf = Lasercommerce_Integration_Gravityforms::instance();
    }

    public function addShortcodes(){
        $this->shortcodes = Lasercommerce_Shortcodes::instance();
    }

    /**
    * Initializes database options
    */
    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    /**
    * Gets the plugin human readable name
    */
    public function getPluginDisplayName() {
        return 'LaserCommerce';
    }

    /**
    * Gets the filename of the main plugin
    */
    protected function getMainPluginFileName() {
        return 'lasercommerce.php';
    }

    /**
    * Gets the object associated with a product.
    * @param WC_Product|int $_product - Post object or Product ID to search for
    * @return (WC_Product|null) - Product found, or null if not found
    */
    public function getProductObject($_product){
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GET_PRODUCT_OBJECT",
        ));

        if(!is_object($_product)){
            $_product = wc_get_product($_product);
        }
        if(!isset($_product) or !$_product) {
            global $product;
            if(!isset($product) or !$product){
                if(LASERCOMMERCE_DEBUG) $this->procedureDebug("product global not set", $context);
                return Null;
            }
            $_product = $product;
        }

        return $_product;

    }

    /**
    * Gets the postID of a given simple or variable product
    *
    * @param (WC_Product|Post|int) $_product the product to be analysed
    * @return integer $postID The postID of the simple or variable product
    */
    public function getProductPostID( $_product = null ){
        $_product = $this->getProductObject($_product);

        $postID = Null;

        // Fix for WC 3.0. Product properties should not be accessed directly.
        if($_product && method_exists($_product, 'get_id')){
            $postID = $_product->get_id();
        }

        return $postID;
    }

    public function getProductSKU($_product){
        $_product = $this->getProductObject($_product);

        $sku = Null;
        if($_product){
            if(method_exists($_product, 'get_sku')){
                $sku = $_product->get_sku();
            }
        }
        return $sku;
    }

    // public function getMajorTiers(){
    //     trigger_error("Deprecated function called: getMajorTiers", E_USER_NOTICE);
    // }

    /**
    * Determines whether a given price matches the woocommerce meta for
    * a product's regular, sale or current price.
    * @param (string|float) $price - the price which is being tested to 2 decimal places
    * @param WC_Product|int $_product - The product object or PostID to test prices against.
    */
    public function whichWCPrice($price, $_product=''){
        $postID = $this->getProductPostID( $_product );
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ISWHICHWCPRICE",
            'args'=>"\$price=".serialize($price).", \$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $value = false;
        if($_product and isset($postID)){
            if($_product->is_type('variable')){
                //TODO: This
            }

            $wc_meta = get_post_meta($postID);
            $metaKeys = array(
                '_price'=>'current',
                '_regular_price'=>'regular',
                '_sale_price'=>'sale'
            );
            foreach($metaKeys as $metaKey => $return_value){
                if(isset($wc_meta[$metaKey])){
                    $WC_price = $wc_meta[$metaKey][0]; #assume meta not singular
                    $WC_cents = intval(floatval($WC_price) * 100);
                    $cents = intval(floatval($price)* 100);
                    if($WC_cents and $WC_cents == $cents ){
                        $value = $return_value;
                    }
                }
            }
        }
        $context['return'] = serialize($value);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);

        return $value;
    }

    /**
    * For a given list of visible tiers, and a given product, return an associative array of all
    * visible Lasercommerce_Pricing objects mapped by their tier key.
    * @param WC_Product|int $_product - the product object or PostID
    * @param Lasercommerce_Tier[] - an array of tiers currently visible.
    * @return Lasercommerce_Pricing[] - an array of pricings currently visible.
    */
    public function getPricing($_product='', $tiers=array()){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETPRICING",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $pricings = null;
        if($_product) {
            $postID = $this->getProductPostID( $_product );

            $pricings = array();
            if(is_array($tiers)) {
                array_push($tiers, new Lasercommerce_Tier('') );
                foreach ($tiers as $tier) {
                    $pricing = new Lasercommerce_Pricing($postID, $tier->id);
                    if($pricing->regular_price){
                        $pricings[$tier->id] = $pricing;
                    }
                }
            }
        } else {
            if(LASERCOMMERCE_DEBUG) $this->procedureDebug("product not valid", $context);
        }

        if(LASERCOMMERCE_PRICING_DEBUG){
            $retStr = '';
            foreach($pricings as $pricing){
                $retStr .= (string)($pricing) . ";";
            }
            $context['return'] = $retStr;
            $this->procedureEnd("", $context);
        }

        return $pricings;

    }

    /**
    * For a given product, return an associative array of all visible Lasercommerce_Pricing
    * objects sorted by their regular price to the current user.
    * @param WC_Product|int $_product - the product object or PostID
    * @return Lasercommerce_Pricing[] - an array of pricing objects currently visible
    */
    public function getVisiblePricing($_product=''){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETVISIBLEPRICING",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $visibleTiers = $this->tree->getVisibleTiers();
        $pricings = $this->getPricing($_product, $visibleTiers );
        uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );

        if(LASERCOMMERCE_PRICING_DEBUG){
            $retStr = '';
            foreach($pricings as $pricing){
                $retStr .= (string)($pricing) . ";";
            }
            $context['return'] = $retStr;
            $this->procedureEnd("", $context);
        }
        return $pricings;

    }

    /**
    * For a given product, return an associative array of all visible Lasercommerce_Pricing
    * objects sorted by their regular price to an omniscient  user.
    * @param WC_Product|int $_product - the product object or PostID
    * @return Lasercommerce_Pricing[] - an array of pricing objects currently visible
    */
    public function getOmniscientPricing($_product=''){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETOMNISCIENTPRICING",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $allTiers = $this->tree->getTreeTiers();
        $pricings = $this->getPricing($_product, $allTiers );
        uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );

        if(LASERCOMMERCE_PRICING_DEBUG){
            $retStr = '';
            foreach($pricings as $pricing){
                $retStr .= (string)($pricing) . ";";
            }
            $context['return'] = $retStr;
            $this->procedureEnd("", $context);
        }

        return $pricings;
    }

    /**
     * Gets the highest or lowest pricing for a given product across all visible pricing
     * @param string $star: either 'highest' or 'lowest'
     * @param WC_Product|int $_product: The WC_Product object or post_id
     * @return Lasercommerce_Pricing $pricing: the requested pricing object
     */
    public function getStarPricing( $star='', $_product=''){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETSTARPRICING|$star",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $pricings = $this->getVisiblePricing($_product);

        $pricing = null;
        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = ($star == "highest")?array_pop($pricings):array_shift($pricings);
        } else {
            // if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("no visible pricings", $context);
        }

        $context['return'] = (string)($pricing);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $pricing;
    }

    public function getLowestPricing($_product=''){ return $this->getStarPricing('lowest', $_product); }
    public function getHighestPricing($_product=''){ return $this->getStarPricing('highest', $_product); }

    /**
     * Gets the pricing object for a variable products minimum or maximum variation.
     */
    public function getVariationPricing( $_product, $min_or_max ){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETVARIATIONPRICING|$min_or_max",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $metaKey = ($min_or_max == 'max' ? '_max_price_variation_id' : '_min_price_variation_id');
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug('meta key: '.$metaKey, $context);
        $target_ids = get_post_meta($postID, $metaKey, false);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug('target ids: '.serialize($target_ids), $context);
        $target_id = null;
        if(isset($target_ids[0])) $target_id = $target_ids[0];
        if(!$target_id){
            WC_Product_Variable::sync($_product);
            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug('syncing.', $context);
            // Deprecated in WC 3.0 $_product->variable_product_sync();
            $target_id = get_post_meta($postID, $metaKey, true);
            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug('post sync target id: '.serialize($target_id), $context);
        }

        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."creating target with id: ".$target_id);
        $target = wc_get_product($target_id);
        if(LASERCOMMERCE_PRICING_DEBUG && $target) $this->procedureDebug('target acquired: '.$target_id, $context);

        $value = null;
        if($target){
            $value = $this->getLowestPricing($target);
        }

        $context['return'] = (string)($value);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);

        return $value;
    }

    // public function maybeGetVariationStarPrice( $star = '', $price = '', $_product, $min_or_max, $include_taxes){
    //     $postID = $this->getProductPostID($_product);
    //     $context = array_merge($this->defaultContext, array(
    //         'caller'=>$this->_class."GETVARIATIONSTARPRICE|$star|$min_or_max",
    //         'args'=>"\$_product=".serialize($postID).", \$price=".serialize($price)
    //     ));
    //     if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);
    //
    //     $pricing = $this->getVariationPricing($_product, $min_or_max);
    //     if($pricing){
    //         switch ($star) {
    //             case '':
    //             $price = $pricing->maybe_get_current_price();
    //             break;
    //             case 'regular':
    //             $price = $pricing->regular_price;
    //             break;
    //             case 'sale':
    //             $price = $pricing->sale_price;
    //             break;
    //         }
    //     }
    //
    //     $context['return'] = serialize($price);
    //     if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
    //     return $price;
    // }

    // public function maybeGetVariationPrice( $price = '', $_product, $min_or_max, $include_taxes ) { return $this->maybeGetVariationStarPrice( '', $price = '', $_product, $min_or_max, $include_taxes ); }
    // public function maybeGetVariationRegularPrice($price = '', $_product, $min_or_max, $include_taxes) { return $this->maybeGetVariationStarPrice( 'regular', $price = '', $_product, $min_or_max, $include_taxes ); }
    // public function maybeGetVariationSalePrice($price = '', $_product, $min_or_max, $include_taxes) { return $this->maybeGetVariationStarPrice( 'sale', $price = '', $_product, $min_or_max, $include_taxes ); }

    public function actuallyGetStarPrice($star = '', $price = '', $_product = ''){
        $postID = $this->getProductPostID($_product);
        $sku = $this->getProductSKU($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."!GETSTARPRICE|$star",
            'args'=>"\$_product=".serialize($postID).$sku.", \$price=".serialize($price)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $tierString = $this->tree->serializeVisibleTiers();
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("tierString: ".serialize($tierString), $context);

        //
        // $hash = array(
        //     $postID,
        //     $tierString
        // );

        // $cache_key  = 'lc_lowestPricings' . substr( md5( json_encode( $hash ) ), 0, 22 ) ;

        // $lowestPricing = get_transient($cache_key);
        // $lowestPricing = array();

        // if( empty($lowestPricing) ){ //if not cached
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."lowestPricing is not cached");

        if($_product->is_type( 'variable' )){
            $lowestPricing = $this->getVariationPricing( $_product, 'min');
        } else {
            $lowestPricing = $this->getLowestPricing($_product);
        }

        // set_transient($cache_key, $lowestPricing);
        // } else {
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."lowestPricing is cached");
        // }

        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("lowestPricing: ".(string)$lowestPricing, $context);

        if($lowestPricing) {
            switch ($star) {
                // case '':
                // case 'cart':
                // case 'incl_tax':
                // case 'excl_tax':
                case 'current':
                $price = $lowestPricing->maybe_get_current_price();
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("-> changing price to $star".serialize($price), $context);
                break;
                case 'regular':
                $price = $lowestPricing->regular_price;
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("-> changing price to $star".serialize($price), $context);
                break;
                case 'sale':
                $price = $lowestPricing->sale_price;
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("-> changing price to $star".serialize($price), $context);
                break;
                // default:
                //     # code...
                //     break;
            }
        } else {
            $price = '';
        }

        $context['return'] = serialize($price);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $price;
    }

    public function actuallyGetPrice($price = '', $_product) { return $this->actuallyGetStarPrice('current', $price, $_product); }
    public function actuallyGetRegularPrice($price = '', $_product) { return $this->actuallyGetStarPrice('regular', $price, $_product); }
    public function actuallyGetSalePrice($price = '', $_product) { return $this->actuallyGetStarPrice('sale', $price, $_product); }

    public function actuallyGetStarDate($star='', $date='', $_product){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."!GETSTARDATE|$star",
            'args'=>"\$_product=".serialize($postID).", \$date=".serialize($date)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        if($_product->is_type( 'variable' )){
            $lowestPricing = $this->getVariationPricing( $_product, 'min');
        } else {
            $lowestPricing = $this->getLowestPricing($_product);
        }

        if($lowestPricing) {
            switch($star){
                case 'from':
                $date = $lowestPricing->sale_price_dates_from;
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("-> changing date to $star".serialize($date), $context);
                break;
                case 'to':
                $date = $lowestPricing->sale_price_dates_from;
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("-> changing date to $star".serialize($date), $context);
                break;
            }
        }

        $date = DateTime::createFromFormat("%U", $date);
        $context['return'] = serialize($date);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $date;
    }

    public function actuallyGetDateOnSaleFrom($date = '', $_product) { return $this->actuallyGetStarDate('from', $date, $_product ); }
    public function actuallyGetDateOnSaleTo($date = '', $_product) { return $this->actuallyGetStarDate('to', $date, $_product ); }

    public function actuallyGetOnSale($on_sale, $_product) {
        if ( '' !== (string) $this->actuallyGetSalePrice('', $_product) && $this->actuallyGetRegularPrice('', $_product) > $this->actuallyGetSalePrice('', $_product) ) {
            $on_sale = true;

            if ( $this->actuallyGetDateOnSaleFrom('', $_product) && $this->actuallyGetDateOnSaleFrom('', $_product)->getTimestamp() > current_time( 'timestamp', true ) ) {
                $on_sale = false;
            }

            if ($this->actuallyGetDateOnSaleTo('', $_product) && $this->actuallyGetDateOnSaleFrom('', $_product)->getTimestamp() < current_time( 'timestamp', true ) ) {
                $on_sale = false;
            }
        } else {
            $on_sale = false;
        }
        return $on_sale;
    }

    public function actuallyGetPriceHtml($html = '', $_product) {
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."!GETPRICEHTML",
            'args'=>"\$_product=".serialize($postID).", \$html=".serialize($html)
        ));
        if(LASERCOMMERCE_HTML_DEBUG) $this->procedureStart('', $context);

        if($_product->is_type( 'variable' )){
            $lowestPricing = $this->getVariationPricing( $_product, 'min');
        } else {
            $lowestPricing = $this->getLowestPricing($_product);
        }

        if( '' === $this->actuallyGetPrice('', $_product) ) {
            $html = apply_filters( 'woocommerce_empty_price_html', '', $_product );
        } else if($_product->is_type( 'variable' )){
            $lowestPricing = $this->getVariationPricing( $_product, 'min');
            $highestPricing = $this->getVariationPricing( $_product, 'max');
            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug(
                sprintf("lowestPricing: %s, highestPricing: %s", $lowestPricing, $highestPricing),
                $context
            );

            $min_price = $lowestPricing->maybe_get_current_price();
            $max_price = $highestPricing->maybe_get_current_price();
            $min_reg_price = $lowestPricing->regular_price;
            $max_reg_price = $highestPricing->regular_price;

            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug(
                sprintf("min: %s, max: %s, min_reg: %s, max_reg: %s", $min_price, $max_price, $min_reg_price, $max_reg_price),
                $context
            );

            if ( $min_price !== $max_price ) {
                $html = apply_filters( 'woocommerce_variable_price_html', wc_format_price_range( $min_price, $max_price ) . $_product->get_price_suffix(), $_product );
            } elseif ( $_product->is_on_sale() && $min_reg_price === $max_reg_price ) {
                $html = apply_filters( 'woocommerce_variable_price_html', wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) ) . $_product->get_price_suffix(), $_product );
            } else {
                $html = apply_filters( 'woocommerce_variable_price_html', wc_price( $min_price ) . $_product->get_price_suffix(), $_product );
            }
        } else {
            if( $this->actuallyGetOnSale(false, $_product) ){
                $html = wc_format_sale_price( $this->actuallyGetRegularPrice('', $_product) , $this->actuallyGetSalePrice('', $_product) ) . $_product->get_price_suffix();
            } else {
                $html = wc_price( $this->actuallyGetPrice('', $_product) ) . $_product->get_price_suffix();
            }
        }




        $context['return'] = serialize($html);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $html;

    }

    /**
    * Generalization of maybeGet*Price
    */
    public function maybeGetStarPrice($star = '', $price = '', $_product = ''){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETSTARPRICE|$star",
            'args'=>"\$_product=".serialize($postID).", \$price=".serialize($price)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        //only override if it is a WC price

        if($price == '' || $price == 'None'){
            $override = 'current';
        } else {
            $whichWCPrice = $this->whichWCPrice($price, $_product);
            if( 'current' == $whichWCPrice ){
                $override = 'current';
            } elseif ( 'regular' == $whichWCPrice ){
                $override = 'regular';
            } elseif ('sale' == $whichWCPrice) {
                $override = 'sale';
            } else {
                $override = false;
            }
        }

        if($override) {

            if($star == 'regular') {
                $override = 'regular';
            }
            if($star == 'sale') {
                $override = 'sale';
            }

            $price = $this->actuallyGetStarPrice($override, $price, $_product);

            if($price) {$price =  round( $price, wc_get_price_decimals() );}
        }

        $context['return'] = wc_format_decimal($price);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $price;
    }

    public function maybeGetRegularPrice($price = '', $_product=''){ return $this->maybeGetStarPrice('regular', $price, $_product); }
    public function maybeGetSalePrice($price = '', $_product = ''){ return $this->maybeGetStarPrice('sale', $price, $_product); }
    public function maybeGetPrice($price = '', $_product = ''){ return $this->maybeGetStarPrice('', $price, $_product); }
    public function maybeOverrideBlankPrice($price, $_product){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."OVERRIDEBLANKPRICE",
            'args'=>"\$_product=".serialize($postID).", \$price=".serialize($price)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);
        if(!$price){
            $price = $this->maybeGetStarPrice('', $price, $_product);
        }
        $context['return'] = wc_format_decimal($price);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $price;
    }
    public function maybeGetCartPrice($price = '', $_product = ''){ return $this->maybeOverrideBlankPrice($price, $_product); }
    public function maybeGetPriceInclTax($price ='', $qty, $_product){ return $this->maybeOverrideBlankPrice($price, $_product); }
    public function maybeGetPriceExclTax($price ='', $qty, $_product){ return $this->maybeOverrideBlankPrice($price, $_product); }

    public function maybeGetCartItemPrice( $price, $values, $cart_item_key ){
        $context = array(
            'do_trace'=>true,
            'caller'=>$this->_class."GETCARTITEMPRICE",
            'args'=>"\$price=".serialize($price).", \$values=".serialize($values)
        );
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $quantity = isset($values['quantity'])?$values['quantity']:'';
        $product_id = isset($values['product_id'])?$values['product_id']:'';
        $variation_id = isset($values['variation_id'])?$values['variation_id']:'';

        if(LASERCOMMERCE_PRICING_DEBUG) {
            $message = "quantity: ".serialize($quantity);
            $message .= " | product_id: ".serialize($product_id);
            $message .= " | variation_id: ".serialize($variation_id);
            $message .= " | cart_item_key: ".serialize($cart_item_key);
            $this->procedureDebug($message, $context);
        }

        $context['return'] = wc_format_decimal($price);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $price;
    }

    public function maybeGetCartItemSubtotal( $subtotal, $values, $cart_item_key ) {
        $context = array(
            'do_trace'=>true,
            'caller'=>$this->_class."GETCARTITEMSUBTOTAL",
            'args'=>"\$subtotal=".serialize($subtotal).", \$values=".serialize($values)
        );
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        $quantity = isset($values['quantity'])?$values['quantity']:'';
        $product_id = isset($values['product_id'])?$values['product_id']:'';
        $variation_id = isset($values['variation_id'])?$values['variation_id']:'';

        if(LASERCOMMERCE_PRICING_DEBUG) {
            $message = "quantity: ".serialize($quantity);
            $message .= " | product_id: ".serialize($product_id);
            $message .= " | variation_id: ".serialize($variation_id);
            $message .= " | cart_item_key: ".serialize($cart_item_key);
            $this->procedureDebug($message, $context);
        }

        $context['return'] = wc_format_decimal($subtotal);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $subtotal;
    }

    /**
    *  Returns if the simple or variable product can be purchased by the current user
    */
    public function maybeIsPurchasable($purchasable, $_product){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."!ISPURCHASABLE",
            'args'=>"\$purchasable=".serialize($purchasable).", \$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        if($_product) {
            if($_product->is_type('variable')){
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("is variable", $context);
                $children = $_product->get_children();
                if($children){
                    foreach ($children as $child_id) {
                        $child = wc_get_product($child_id);
                        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("GOING DEEPER!", $context);

                        if($this->maybeIsPurchasable($purchasable, $child)) {
                            $purchasable = true;
                            break;
                        }
                    }
                }
            } else {
                // if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("GETTING POST STATUS", $context);
                $post_status = get_post_status($_product);
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("post_status: $post_status", $context);
                if($post_status === 'publish'){
                    // if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("GETTING VISIBLE PRICING", $context);
                    $pricings = $this->getVisiblePricing($_product);
                    if($pricings) $purchasable = true;
                }
            }
        }
        $context['return'] = serialize($purchasable);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $purchasable;
    }

    /**
    *  Returns an array of tierIDs that can purchase the given simple or variable product
    */

    public function maybeGetPurchaseTierIDs( $purchase_tierIDs, $_product){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETPURCHASETIERS",
            'args'=>"\$purchase_tierIDs=".serialize($purchase_tierIDs).", \$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        if(!is_array($purchase_tierIDs)){
            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug('Called with non-array \$purchase_tierIDs', $context);
            $purchase_tierIDs = array();
        }

        if($_product) {
            if($_product->is_type('variable')){
                $children = $_product->get_children();
                if($children){
                    foreach ($children as $child_id) {
                        $child = wc_get_product($child_id);
                        // $child = $_product->get_child($child_id);
                        $purchase_tierIDs = $this->maybeGetPurchaseTierIDs($purchase_tierIDs, $child);
                        // $child_purchase_tierIDs = $this->maybeGetPurchaseTierIDs($purchase_tierIDs, $child);
                        // foreach ($child_purchase_tierIDs as $tier) {
                        //     if(!in_array($tier, $purchase_tierIDs)){
                        //         array_push($purchase_tierIDs, $tier);
                        //     }
                        // }
                    }
                }
            } else {
                $pricings = $this->getOmniscientPricing($_product);
                $post_status = get_post_status($_product);
                if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("post_status: $post_status", $context);

                if($pricings and $post_status == 'publish'){
                    $this_purchase_tierIDs = array_keys($pricings);
                    foreach ($this_purchase_tierIDs as $tierID) {
                        if(!in_array($tierID, $purchase_tierIDs)){
                            $purchase_tierIDs[] = $tierID;
                        }
                    }
                }
            }
        }

        $context['return'] = serialize($purchase_tierIDs);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $purchase_tierIDs;
    }

    /**
     * Sync lasercommerce data from a variable product's variations with root product.
     * @param WC_Product $_product: variable product root
     */
    public function maybeVariableProductSyncData( $_product ){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."VARPRODSYNC",
            'args'=>"\$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        // OMNISCIENT OVERRIDE BEGIN
        // $omniscient_tiers = $this->tree->getOmniscientTiers();
        // global $Lasercommerce_Tiers_Override;
        // $old_override = $Lasercommerce_Tiers_Override;
        // $Lasercommerce_Tiers_Override = $omniscient_tiers;

        $children = $_product->get_visible_children();


        // set_error_handler('lc_exceptions_error_handler');
        // try {
        //     ob_start();
        //     $children = $_product->get_visible_children();
        //     if( ob_get_clean() ){
        //         throw new Exception("output from get_children", 1);
        //     }
        // } catch(Exception $e) {
        //     $this->procedureDebug("get_visible_children() stack trace: " . $e->getTraceAsString(), $context);
        // }
        // restore_error_handler();


        if($_product){
            $min_pricing = null;
            $max_pricing = null;
            foreach ($children as $child) {
                $variation = wc_get_product($child);
                $discounted_pricing = $this->getLowestPricing($variation);
                if($min_pricing == null || Lasercommerce_Pricing::sort_by_regular_price($discounted_pricing, $min_pricing) < 0){
                    $min_pricing = $discounted_pricing;
                }
                if($max_pricing == null || Lasercommerce_Pricing::sort_by_regular_price($discounted_pricing, $max_pricing) > 0){
                    $max_pricing = $discounted_pricing;
                }
            }
            foreach (array('min', 'max') as $bound) {
                $bound_pricing = ${"{$bound}_pricing"};
                if( $bound_pricing ){
                    foreach( array('price', 'regular_price', 'sale_price') as $price_type){
                        $metaKey = $bound.'_'.$price_type.'_variation_id';
                        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("SYNC setting ". $postID. ', '.$metaKey .' to '.$bound_pricing->id, $context);
                        update_post_meta( $postID, '_'.$metaKey, $bound_pricing->id);
                        // $_product->$metaKey = $bound_pricing->id;
                    }
                }
            }
            // if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("dying", $context);
            // wp_die();

            // update_post_meta($postID, '_price', '');
            // update_post_meta($postID, '_price', $min_pricing->maybe_get_current_price());
        }

        // $Lasercommerce_Tiers_Override = $old_override;
        // OMNISCIENT OVERRIDE END
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
    }

    public function add_tier_flat_to_woocommerce_get_variation_prices_hash( $hash, $display ) {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ADDTIERFLAT2WCGETVARPRICESHASH",
            // 'args'=>"\$hash=".serialize($hash)
                // .", \$display=".serialize($display)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        // if ( $display ) {
        //     $hash = array( get_option( 'woocommerce_tax_display_shop', 'excl' ), WC_Tax::get_rates() );
        // } else {
        //     $hash = array( false );
        // }

        // if(LASERCOMMERCE_PRICING_DEBUG) {
        //     error_log($_procedure."HASH PRE: ". serialize(json_encode( $hash )) );
        //     error_log($_procedure."HASH MD5 PRE: ".md5( json_encode( $hash ) ) );
        // }

        $hash['tiers'] = $this->tree->serializeVisibleTiers();


        //TODO: comment this next block once classes are working

        // $filter_names = array( 'woocommerce_variation_prices_price', 'woocommerce_variation_prices_regular_price', 'woocommerce_variation_prices_sale_price' );
        // foreach ( $filter_names as $filter_name ) {
            // error_log($_procedure.serialize(serialize($hash[$filter_name])));
            // unset( $hash[$filter_name]);
        // }

        // $hash[] = time();
        // if(LASERCOMMERCE_PRICING_DEBUG) {
        // error_log($_procedure.serialize(serialize($hash)));
        // error_log($_procedure.serialize($hash['woocommerce_variation_prices_price']));
        // }
        // if(LASERCOMMERCE_PRICING_DEBUG) {
        //     error_log($_procedure."HASH POST: ".serialize(json_encode( $hash )) );
        //     error_log($_procedure."HASH MD5 POST: ".md5( json_encode( $hash ) ) );
        // }

        // $context['return'] = serialize($hash);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $hash;
    }

    public function maybeVariationPrices( $prices_array, $_product, $display){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."VARIATIONPRICES",
            'args'=>"\$prices_array=".serialize($prices_array).", \$_product=".serialize($postID).", \$display=".serialize($display)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        // if(LASERCOMMERCE_HTML_DEBUG) {
        //     $string = implode("|",
        //         array(
        //             ("price: ".serialize($prices_array['price'])),
        //             ("regular_price: ".serialize($prices_array['regular_price'])),
        //             ("sale_price: ".serialize($prices_array['sale_price'])),
        //         )
        //     );
        //     error_log($_procedure."PRE: ".$string);
        // }

        $prices            = array();
        $regular_prices    = array();
        $sale_prices       = array();
        $tax_display_mode  = get_option( 'woocommerce_tax_display_shop' );

        foreach ( $_product->get_visible_children() as $variation_id ) {
            $variation = wc_get_product($variation_id);
            if ( $variation ) {
                $pricing = $this->getLowestPricing($variation);
                if(!$pricing) continue;
                $price         = $pricing->maybe_get_current_price();
                $regular_price = $pricing->regular_price;
                $sale_price    = $pricing->sale_price;

                // If sale price does not equal price, the product is not yet on sale
                if ( ! $pricing->is_sale_active_now() ) {
                    $sale_price = $regular_price;
                }

                // If we are getting prices for display, we need to account for taxes
                if ( $display ) {
                    $price         = $tax_display_mode == 'incl' ? wc_get_price_including_tax($variation, array( 'qty'=>1, 'price'=>$price ) ) : wc_get_price_excluding_tax($variation, array( 'qty'=>1, 'price'=>$price ) );
                    $regular_price = $tax_display_mode == 'incl' ? wc_get_price_including_tax($variation, array( 'qty'=>1, 'price'=>$regular_price ) ) : wc_get_price_excluding_tax($variation, array( 'qty'=>1, 'price'=>$regular_price ) );
                    $sale_price    = $tax_display_mode == 'incl' ? wc_get_price_including_tax($variation, array( 'qty'=>1, 'price'=>$sale_price ) ) : wc_get_price_excluding_tax($variation, array( 'qty'=>1, 'price'=>$sale_price ) );
                }

                $prices[ $variation_id ]         = $price;
                $regular_prices[ $variation_id ] = $regular_price;
                $sale_prices[ $variation_id ]    = $sale_price;
            }
        }

        asort( $prices );
        asort( $regular_prices );
        asort( $sale_prices );

        $prices_array  = array(
            'price'         => $prices,
            'regular_price' => $regular_prices,
            'sale_price'    => $sale_prices
        );

        if(LASERCOMMERCE_PRICING_DEBUG) {
            $string = implode("|",
            array(
                ("price: ".serialize($prices_array['price'])),
                ("regular_price: ".serialize($prices_array['regular_price'])),
                ("sale_price: ".serialize($prices_array['sale_price'])),
                )
            );
            $this->procedureDebug("POST: ".$string, $context);
        }

        $context['return'] = serialize($prices_array);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $prices_array;
    }

    public function maybeVariationPricesPrice( $price, $variation, $parent){
        return $this->maybeGetPrice( $price, $variation );
    }

    public function maybeVariationPricesSalePrice( $price, $variation, $parent){
        return $this->maybeGetSalePrice( $price, $variation );
    }

    public function maybeVariationPricesRegularPrice( $price, $variation, $parent){
        return $this->maybeGetRegularPrice( $price, $variation );
    }

    public function maybeGetChildren($children, $_product, $visible_only){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."GETCHILDREN",
            'args'=>"\$children=".serialize($children).", \$_product=".serialize($postID).", \$visible_only=".serialize($visible_only)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);


        // if(LASERCOMMERCE_DEBUG) {
        //     $string = implode("|",
        //         array(
        //             ("children: ".serialize($children)),
        //             ("_product: ".serialize($this->getProductSKU($_product))),
        //             ("visible_only: ".serialize($visible_only)),
        //         )
        //     );
        //     error_log($_procedure.$string);
        // }

        //correct bad behaviour of when $visible_only = true;
        if($visible_only){
            foreach ( $_product->get_children() as $child_id) {
                $variation = wc_get_product($child_id);
                // $variation = $_product->get_child( $child_id );
                if ( $variation and $variation->variation_is_visible() ) {
                    if( ! in_array($child_id, $children)){
                        if(LASERCOMMERCE_DEBUG) {
                            $this->procedureDebug("found extra child: ".$child_id, $context);
                        }
                        $children[] = $child_id;
                    }
                }
            }

        }


        //unhook self and get children without visibility requirement
        // remove_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);
        //hook self back in
        // add_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);

        $context['return'] = serialize($children);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $children;
    }


    public function maybeVariationIsVisible($visible, $variation_id, $post_id, $variation){
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."MAYBEISVARIATIONVISIBLE",
            'args'=>"\$visible=".serialize($visible).", \$variation_id=".serialize($variation_id).", \$post_id=".serialize($post_id)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        // if(LASERCOMMERCE_PRICING_DEBUG) {
        //     $string = implode("|",
        //     array(
        //         "visible: ".serialize($visible),
        //         "variation_id: ".serialize($variation_id),
        //         "post_id: ".serialize($post_id),
        //         "variation: ".serialize($variation->get_sku()),
        //         )
        //     );
        //     error_log($_procedure.$string);
        // }

        $visible = $this->maybeIsPurchasable($visible, $variation);

        $context['return'] = serialize($visible);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $visible;
    }

    public function maybeAvailableVariation($data, $_product, $variation){
        $postID = $this->getProductPostID($_product);
        $variationID = $this->getProductPostID($variation);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."MAYBEAVAILABLEVARIATION",
            'args'=>"\$data=".serialize($data).", \$_product=".serialize($postID).", \$variation=".serialize($variationID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        return $data;
    }



    /**
    * Functions for filtering HTML output
    */

    public function maybeGetStarHtml($price_html, $_product, $star){
        $postID = $this->getProductPostID($_product);
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."MAYBEGETSTARHTML|$star",
            'args'=>"\$price_html=".serialize($price_html).", \$_product=".serialize($postID)
        ));
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureStart('', $context);

        if(LASERCOMMERCE_HTML_DEBUG) {
            $user = wp_get_current_user();
            $tiers = $this->tree->serializeVisibleTiers();
            $string = implode("|",
            array(
                ("price: ".$_product->get_price()),
                ("regular_price: ".$_product->get_regular_price()),
                ("sale_price: ".$_product->get_sale_price()),
                ("product: ".$_product->get_id()),
                ("user: ".$user->ID),
                ("tiers: ".$tiers)
                )
            );
            $this->procedureDebug($string, $context);
        }

        if($star == 'price'){

        } else if($star == 'variable_price'){
            if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureDebug("variation_prices: ".serialize($_product->get_variation_prices()), $context);
        }

        $context['return'] = serialize($price_html);
        if(LASERCOMMERCE_PRICING_DEBUG) $this->procedureEnd("", $context);
        return $price_html;
    }

    public function maybeGetPriceHtml($price_html, $_product){ return $this->maybeGetStarHtml($price_html, $_product, 'price'); }
    public function maybeGetSalePriceHtml($price_html, $_product){ return $this->maybeGetStarHtml($price_html, $_product, 'sale_price'); }
    public function maybeGetVariablePriceHtml($price_html, $_product){ return $this->maybeGetStarHtml($price_html, $_product, 'variable_price'); }
    public function maybeGetVariationPriceHtml($price_html, $_product){ return $this->maybeGetStarHtml($price_html, $_product, 'variation_price'); }
    public function maybeGetVariationSalePriceHtml($price_html, $_product){ return $this->maybeGetStarHtml($price_html, $_product, 'variation_sale_price'); }
    public function maybeGetEmptyPriceHtml($price_html, $_product) { return $this->maybeGetStarHtml($price_html, $_product, 'empty_price'); }

    /**
    * Functions for generating Gravity Forms parameters and form tags
    * TODO: Move to own integration class
    */

    public function overrideVariableDataStore($store) {
        $store = "LC_Product_Variable_Data_Store_CPT";
        return $store;
    }

    /**
    *  Actions and Filters!
    */

    public function constructTraces() {
        $this->traceAction('init');

        $this->traceFilter('woocommerce_product_get_price');
        $this->traceFilter('woocommerce_product_get_regular_price');
        $this->traceFilter('woocommerce_product_get_sale_price');
        $this->traceFilter('woocommerce_product_variation_get_price');
        $this->traceFilter('woocommerce_product_variation_get_regular_price');
        $this->traceFilter('woocommerce_product_variation_get_sale_price');
        $this->traceFilter('woocommerce_get_variation_price');
        $this->traceFilter('woocommerce_get_variation_regular_price');
        $this->traceFilter('woocommerce_get_variation_sale_price');
        $this->traceFilter('woocommerce_is_purchasable');
        /* HTML STUFF */
        $this->traceFilter('woocommerce_sale_price_html');
        $this->traceFilter('woocommerce_price_html');
        $this->traceFilter('woocommerce_variable_price_html');
        $this->traceFilter('woocommerce_variation_price_html');
        $this->traceFilter('woocommerce_variation_sale_price_html');
        $this->traceFilter('woocommerce_empty_price_html');
        $this->traceFilter('woocommerce_get_price_html');
        $this->traceFilter('woocommerce_variable_sale_price_html');
        /* Tax stuff */
        $this->traceFilter('woocommerce_get_price_including_tax');
        $this->traceFilter('woocommerce_get_price_excluding_tax');
        $this->traceFilter('woocommerce_get_variation_prices_hash');

        $this->traceFilter('woocommerce_variation_prices');
        $this->traceFilter('woocommerce_available_variation');
        $this->traceFilter('woocommerce_show_variation_price');
        $this->traceFilter('woocommerce_variation_prices_price');
        $this->traceFilter('woocommerce_variation_prices_regular_price');
        $this->traceFilter('woocommerce_variation_prices_sale_price');
    }

    public function addPriceFilters() {
        add_filter( 'woocommerce_product_get_price', array(&$this, 'actuallyGetPrice'), 0, 2 );
        add_filter( 'woocommerce_product_get_regular_price', array(&$this, 'actuallyGetRegularPrice' ), 0, 2 );
        add_filter( 'woocommerce_product_get_sale_price', array(&$this, 'actuallyGetSalePrice'), 0, 2 );
        add_filter( 'woocommerce_product_get_date_on_sale_from', array(&$this, 'actuallyGetDateOnSaleFrom'), 0, 2 );
        add_filter( 'woocommerce_product_get_date_on_sale_to', array(&$this, 'actuallyGetDateOnSaleTo'), 0, 2 );
        add_filter( 'woocommerce_product_variation_get_price', array(&$this, 'actuallyGetPrice'), 0, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', array(&$this, 'actuallyGetRegularPrice' ), 0, 2 );
        add_filter( 'woocommerce_product_variation_get_sale_price', array(&$this, 'actuallyGetSalePrice'), 0, 2 );
        add_filter( 'woocommerce_product_variation_get_date_on_sale_from', array(&$this, 'actuallyGetDateOnSaleFrom'), 0, 2 );
        add_filter( 'woocommerce_product_variation_get_date_on_sale_to', array(&$this, 'actuallyGetDateOnSaleTo'), 0, 2 );
    }

    public function removePriceFilters() {
        remove_filter( 'woocommerce_product_get_price', array(&$this, 'actuallyGetPrice'), 0, 2 );
        remove_filter( 'woocommerce_product_get_regular_price', array(&$this, 'actuallyGetRegularPrice' ), 0, 2 );
        remove_filter( 'woocommerce_product_get_sale_price', array(&$this, 'actuallyGetSalePrice'), 0, 2 );
        remove_filter( 'woocommerce_product_get_date_on_sale_from', array(&$this, 'actuallyGetDateOnSaleFrom'), 0, 2 );
        remove_filter( 'woocommerce_product_get_date_on_sale_to', array(&$this, 'actuallyGetDateOnSaleTo'), 0, 2 );
        remove_filter( 'woocommerce_product_variation_get_price', array(&$this, 'actuallyGetPrice'), 0, 2 );
        remove_filter( 'woocommerce_product_variation_get_regular_price', array(&$this, 'actuallyGetRegularPrice' ), 0, 2 );
        remove_filter( 'woocommerce_product_variation_get_sale_price', array(&$this, 'actuallyGetSalePrice'), 0, 2 );
        remove_filter( 'woocommerce_product_variation_get_date_on_sale_from', array(&$this, 'actuallyGetDateOnSaleFrom'), 0, 2 );
        remove_filter( 'woocommerce_product_variation_get_date_on_sale_to', array(&$this, 'actuallyGetDateOnSaleTo'), 0, 2 );
    }

    public function addActionsAndFilters() {
        // Admin filters:

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class . "addActionsAndFilters",
        ));
        if(LASERCOMMERCE_DEBUG) $this->procedureStart('', $context);

        if(LASERCOMMERCE_DEBUG) {
            $this->constructTraces();
        }

        add_action('init', function(){
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
        });

        $this->addPriceFilters();


        //helper class for tier tree functions


        // WC 3.0 Properties
        // add_filter( 'woocommerce_product_get_price', array(&$this, 'maybeGetPrice'), 0, 2 );
        // add_filter( 'woocommerce_product_get_regular_price', array(&$this, 'maybeGetRegularPrice' ), 0, 2 );
        // add_filter( 'woocommerce_product_get_sale_price', array(&$this, 'maybeGetSalePrice'), 0, 2 );
        // add_filter( 'woocommerce_product_variation_get_price', array(&$this, 'maybeGetPrice'), 0, 2 );
        // add_filter( 'woocommerce_product_variation_get_regular_price', array(&$this, 'maybeGetRegularPrice' ), 0, 2 );
        // add_filter( 'woocommerce_product_variation_get_sale_price', array(&$this, 'maybeGetSalePrice'), 0, 2 );

        // May not need these:
        // add_filter( 'woocommerce_get_variation_price', array($this, 'maybeGetVariationPrice'), 0, 4 );
        // add_filter( 'woocommerce_get_variation_regular_price', array($this, 'maybeGetVariationRegularPrice'), 0, 4 );
        // add_filter( 'woocommerce_get_variation_sale_price', array($this, 'maybeGetVariationSalePrice'), 0, 4 );


        // add_filter( 'woocommerce_cart_product_price', array(&$this, 'maybeGetCartPrice'), 0, 2 );
        // add_filter( 'woocommerce_cart_item_price', array(&$this, 'maybeGetCartItemPrice'), 0, 3 );
        // add_filter( 'woocommerce_cart_item_subtotal', array(&$this, 'maybeGetCartItemSubtotal'), 0, 3);
        // add_filter( 'woocommerce_checkout_item_subtotal', array(&$this, 'maybeGetCartItemSubtotal'), 0, 3);
        // add_filter( 'woocommerce_is_purchasable', array(&$this, 'maybeIsPurchasable'), 1, 2);

        // add_filter( 'woocommerce_sale_price_html', array(&$this, 'maybeGetSalePriceHtml'), 0, 2);
        // add_filter( 'woocommerce_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);
        // add_filter( 'woocommerce_variable_price_html', array(&$this, 'maybeGetVariablePriceHtml'), 0, 2 );
        // add_filter( 'woocommerce_variation_price_html', array(&$this, 'maybeGetVariationPriceHtml'), 0, 2 );
        // add_filter( 'woocommerce_variation_sale_price_html', array(&$this, 'maybeGetVariationSalePriceHtml'), 0, 2 );
        // add_filter( 'woocommerce_empty_price_html', array(&$this, 'maybeGetEmptyPriceHtml'), 0, 2 );
        // add_filter( 'woocommerce_get_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);
        add_filter( 'woocommerce_get_variation_prices_hash', array(&$this, 'add_tier_flat_to_woocommerce_get_variation_prices_hash'), 0, 2 ); /* NEED */


        // TODO: THESE MAY NOT BE NECESSARY?
        add_filter( 'woocommerce_variation_prices', array(&$this, 'maybeVariationPrices'), 0, 3); /* NEED */
        // add_filter( 'woocommerce_variation_prices_price', array($this, 'maybeVariationPricesPrice'), 0, 3);
        // add_filter( 'woocommerce_variation_prices_regular_price', array($this, 'maybeVariationPricesRegularPrice'), 0, 3);
        // add_filter( 'woocommerce_variation_prices_sale_price', array($this, 'maybeVariationPricesSalePrice'), 0, 3);

        // add_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);
        // add_filter( 'woocommerce_variation_is_visible', array( &$this, 'maybeVariationIsVisible' ), 0, 4 );

        // add_filter( 'woocommerce_get_price_including_tax', array(&$this, 'maybeGetPriceInclTax'), 0, 3);
        // add_filter( 'woocommerce_get_price_excluding_tax', array(&$this, 'maybeGetPriceExclTax'), 0, 3);

        add_action('woocommerce_variable_product_sync_data', array(&$this, 'maybeVariableProductSyncData'), 0, 2);
        // Deprecated in WC 3.0
        // add_action('woocommerce_variable_product_sync', array(&$this, 'maybeVariableProductSync'), 0, 2);

        // TODO: override get_visible_children?
        // add_filter('woocommerce_product-variable_data_store', array(&$this, 'overrideVariableDataStore'), 0, 1);

        // add_filter('woocommerce_available_variation', array(&$this, 'maybeAvailableVariation'), 0, 3);

        /** Child Integration plugins */
        add_action( 'init', array(&$this->integration_dp, 'addActionsAndFilters'), 0, 0 );
        add_action( 'init', array(&$this->integration_cp, 'addActionsAndFilters'), 0, 0 );
        add_action( 'init', array(&$this->integration_m, 'addActionsAndFilters'), 0, 0 );
        add_action( 'init', array(&$this->integration_gf, 'addActionsAndFilters'), 0, 0 );

        parent::addActionsAndFilters();
    }


    //TODO: make modifications to tax?
    // add_filter( 'woocommerce_get_cart_tax',
    // add_filter( 'option_woocommerce_calc_taxes',
    // add_filter( 'woocommerce_product_is_taxable'
}
