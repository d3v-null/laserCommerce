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

include_once(LASERCOMMECE_BASE.'/Lasercommerce_UI_Extensions.php');
include_once(LASERCOMMECE_BASE.'/lib/Lasercommerce_Tier_Tree.php');
include_once(LASERCOMMECE_BASE.'/lib/Lasercommerce_Pricing.php');
// include_once('Lasercommerce_UIE.php');

/**
 * Registers Wordpress and woocommerce hooks to modify prices
 */
class Lasercommerce_Plugin extends Lasercommerce_UI_Extensions {

    private $_class = "LC_PL_";

    public $tier_tree_key = 'tier_tree';
    public $tier_key_key = 'tier_key';
    public $default_tier_key = 'default_tier';

    public function initTree(){
        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $this->getOptionNamePrefix() );
        }     
        $this->tree = $Lasercommerce_Tier_Tree;
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            // 'price_tiers' => array(
                // __('Enter Price Tiers','lasercommerce'), 
                // serialize(array('customer'=>'Customer'))
            // )
            /////////////////////////////////////////////
            //'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            //'Donated' => array(__('I have donated to this plugin', 'my-awesome-plugin'), 'false', 'true'),
            //'CanSeeSubmitData' => array(__('Can See Submission data', 'my-awesome-plugin'),
            //                            'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }
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
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }
    
    /** 
     * (Not used) Runs at install
     */
    protected function otherInstall(){ //overrides abstract in parent LifeCycle
        if(LASERCOMMERCE_DEBUG) error_log("LASERCOMMERCE_PLUGIN: Called otherInstall");  
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * 
     * @return void
     */
    public function upgrade() {
    }
    
    /**
     * Overrides abstract method in parent LifeCycle class
     */
    public function activate(){ 
        if(LASERCOMMERCE_DEBUG) error_log("LASERCOMMERCE_PLUGIN: Called activate");
    }
    
    /**
     * Overrides abstract method in parent LifeCycle class
     */    
    public function deactivate(){ //overrides abstract in parent LifeCycle
    }

    /**
     * Gets the role of the current user
     * @return string $role The role of the current user
     */
    public function getCurrentUserRoles(){
        trigger_error("Deprecated function called: getCurrentUserRoles", E_USER_NOTICE);
    }    

    /**
     * Gets the postID of a given simple or variable product
     *
     * @param WC_Product $_product the product to be analysed
     * @return integer $postID The postID of the simple or variable product
     */ 
    public function getProductPostID( $_product = null ){
        $_procedure = $this->_class."GET_PRODUCT_POST_ID: ";

        if(!isset($_product) or !$_product) {
            global $product;
            if(!isset($product) or !$product){
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."product global not set");
                return Null;
            } else {
                $_product = $product;
            }
        }
        if( $_product->is_type( 'variation' ) ){
            if ( isset( $_product->variation_id ) ) {
                $postID = $_product->variation_id;
            } else {
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."variation not set");
                $postID = Null;
            }
        } else {
            if(isset( $_product->id )){
                $postID = $_product->id;
            } else {
                $podtID = Null;
            }
        }
        return $postID;
    }

    public function getMajorTiers(){
        trigger_error("Deprecated function called: getMajorTiers", E_USER_NOTICE);
    }

    public function isWCStarPrice($star='', $price='', $_product=''){
        $_procedure = $this->_class."ISWCSTARPRICE|$star: ";
        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        $postID = $this->getProductPostID( $_product );
        $value = false;
        if($_product and isset($postID)){
            switch ($star) {
                case '':
                    $WC_price = get_post_meta($postID, '_price', True);
                    break;
                case 'regular':
                    $WC_price = get_post_meta($postID, '_regular_price', True);
                    break;
                case 'sale':
                    $WC_price = get_post_meta($postID, '_sale_price', True);
            }
            // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."price (".$postID.") : ".$WC_price);
            if( floatval($WC_price) == floatval($price)){
                $value = true;
            }
        }
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."returned ".serialize($value));

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $value;        
    }

    public function isWCPrice($price='', $_product=''){ return $this->isWCStarPrice('', $price, $_product); }
    public function isWCRegularPrice($price='', $_product=''){ return $this->isWCStarPrice('regular', $price, $_product); }
    public function isWCSalePrice($price='', $_product=''){ return $this->isWCStarPrice('sale', $price, $_product); }    

    public function maybeGetVisiblePricing($_product=''){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETVISIBLEPRICING($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        if($_product) {
            $postID = $this->getProductPostID( $_product );

            $visibleTiers = $this->tree->getVisibleTiers();
            array_push($visibleTiers, new Lasercommerce_Tier('') ); 

            $pricings = array();
            if(is_array($visibleTiers)) foreach ($visibleTiers as $tier) {
                $pricing = new Lasercommerce_Pricing($postID, $tier->id);
                if($pricing->regular_price){
                    $pricings[$tier->id] = $pricing;
                }
            }

            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            
            $value = $pricings;
        } else { 
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."product not valid");
            $value = null;
        }   

        if(LASERCOMMERCE_PRICING_DEBUG and $value) {
            // $string = "";
            foreach ($value as $key => $pricing) {
                $string = ("$key: ".(string)($pricing));
                error_log($_procedure.$string);
            }
            // error_log($_procedure."returned ".$string);
        }

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $value;

    }

    public function maybeGetStarPricing( $star='', $_product=''){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETSTARPRICING|$star($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        $postID = $this->getProductPostID( $_product );  

        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."I: $postID S:".serialize($_product->get_sku()));

        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = ($star == "highest")?array_pop($pricings):array_shift($pricings);
            $value = $pricing;
        } else {
            // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."no visible pricings");
            $value = null;
        }

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."returned ".(string)($value));

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old;         

        return $value;        
    }

    public function maybeGetLowestPricing($_product=''){ return $this->maybeGetStarPricing('lowest', $_product); }
    public function maybeGetHighestPricing($_product=''){ return $this->maybeGetStarPricing('highest', $_product); }

    public function maybeGetVariationPricing( $_product, $min_or_max ){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETVARIATIONPRICING|$min_or_max($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."S:".serialize($_product->get_sku()));        
        
        $meta_key = ($min_or_max == 'max' ? 'max_price_variation_id' : 'min_price_variation_id');
        $target_id = $_product->$meta_key;
        if(!$target_id){
            $_product->variable_product_sync();
            $target_id = $_product->$meta_key;
        }

        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."creating target with id: ".$target_id);
        $target = wc_get_product($target_id);

        if($target){
            $value = $this->maybeGetLowestPricing($target);
        } else {
            $value = null;
        }

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."returned ".(string)($value));

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $value;
    }

    public function maybeGetVariationStarPrice( $star = '', $price = '', $_product, $min_or_max, $display){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETVARIATIONSTARPRICE|$star|$min_or_max($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        $pricing = $this->maybeGetVariationPricing($_product, $min_or_max);
        if($pricing){
            switch ($star) {
                case '':
                    $price = $pricing->maybe_get_current_price();
                    break;
                case 'regular':
                    $price = $pricing->regular_price;
                    break;
                case 'sale':
                    $price = $pricing->sale_price;
                    break;
            }
        } 

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure." returned $price");

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $price;       
    }

    public function maybeGetVariationPrice( $price = '', $_product, $min_or_max, $display ) { return $this->maybeGetVariationStarPrice( '', $price = '', $_product, $min_or_max, $display ); }
    public function maybeGetVariationRegularPrice($price = '', $_product, $min_or_max, $display) { return $this->maybeGetVariationStarPrice( 'regular', $price = '', $_product, $min_or_max, $display ); }
    public function maybeGetVariationSalePrice($price = '', $_product, $min_or_max, $display) { return $this->maybeGetVariationStarPrice( 'sale', $price = '', $_product, $min_or_max, $display ); }

    /**
     * Generalization of maybeGet*Price
     */
    public function maybeGetStarPrice($star = '', $price = '', $_product = ''){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETSTARPRICE|$star($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        $postID = $this->getProductPostID( $_product );   

        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."p: $price I: $postID S:".(string)($_product->get_sku()));
        //only override if it is a WC price
        $override = ($price == '' or $this->isWCPrice($price, $_product) or $this->isWCRegularPrice($price, $_product) or $this->isWCSalePrice($price, $_product));
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."override: ".serialize($override));

        if($_product->is_type( 'variable' )){
            $lowestPricing = $this->maybeGetVariationPricing( $_product, 'min');
        } else {
            $lowestPricing = $this->maybeGetLowestPricing($_product);
        }
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."lowestPricing: ".(string)$lowestPricing);

        if($lowestPricing and $override) {
            switch ($star) {
                case '': 
                case 'cart':
                    $price = $lowestPricing->maybe_get_current_price();
                    // if(LASERCOMMERCE_PRICING_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'regular': 
                    $price = $lowestPricing->regular_price;
                    // if(LASERCOMMERCE_PRICING_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'sale': 
                    $price = $lowestPricing->sale_price;
                    // if(LASERCOMMERCE_PRICING_DEBUG) error_log("-> changing price to $price");            
                    break;                    
                // default:
                //     # code...
                //     break;
            }
        }
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure." returned $price");

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $price;   
    }

    public function maybeGetRegularPrice($price = '', $_product=''){ return $this->maybeGetStarPrice('regular', $price, $_product); }
    public function maybeGetSalePrice($price = '', $_product = ''){ return $this->maybeGetStarPrice('sale', $price, $_product); }
    public function maybeGetPrice($price = '', $_product = ''){ return $this->maybeGetStarPrice('', $price, $_product); }
    public function maybeGetCartPrice($price = '', $_product = ''){ return $this->maybeGetStarPrice('cart', $price, $_product); }
    public function maybeGetPriceInclTax($price ='', $qty, $_product){ return $this->maybeGetStarPrice('', $price, $_product); }
    public function maybeGetPriceExclTax($price ='', $qty, $_product){ return $this->maybeGetStarPrice('', $price, $_product); }

    public function maybeGetCartItemPrice( $price, $values, $cart_item_key ){
        $_procedure = $this->_class."GETCARTITEMPRICE: ";
        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        // if (LASERCOMMERCE_PRICING_DEBUG) {
        //     error_log($_procedure);
        //     error_log(" | price: ".serialize($price));
        //     error_log(" | values: ".serialize($values));
        //     error_log(" | cart_item_key: ".serialize($cart_item_key));
        // }
        $quantity = isset($values['quantity'])?$values['quantity']:'';
        $product_id = isset($values['product_id'])?$values['product_id']:'';
        $variation_id = isset($values['variation_id'])?$values['variation_id']:'';

        // if isset($variation_id){
        //     $unit_price = 
        // }

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $price;
    }

    public function maybeGetCartItemSubtotal( $subtotal, $values, $cart_item_key ) {
        $_procedure = $this->_class."GETCARTITEMSUBTOTAL: ";

        // if (LASERCOMMERCE_PRICING_DEBUG) {
        //     error_log($_procedure);
        //     error_log(" | subtotal: ".serialize($subtotal));
        //     error_log(" | values: ".serialize($values));
        //     error_log(" | cart_item_key: ".serialize($cart_item_key));
        // }
        return $subtotal;
    }

    public function maybeIsPurchasable($purchasable, $_product){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."ISPURCHASABLE($postID): ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");
        // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."p:".(string)$purchasable." S:".(string)$_product->get_sku());

        if($_product) {
            if($_product->is_type('variable')){
                // if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."is variable");
                $children = $_product->get_children();
                if($children){
                    foreach ($children as $child_id) {
                        $child = $_product->get_child($child_id);
                        if($this->maybeIsPurchasable($purchasable, $child)) $purchasable = true;
                        break;
                    }
                } 
            } else {
                $pricings = $this->maybeGetVisiblePricing($_product);
                $post_status = get_post_status($_product);
                if($pricings and $post_status != 'publish' ) $purchasable = true;
            }
        } 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."returned: ".(string)$purchasable);

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $purchasable;
    }

    public function maybeVariableProductSync( $product_id, $children ){
        //TODO: WHAT IF max_price_id is not the same as max_regular_price_id???

        // OMNISCIENT OVERRIDE BEGIN
        // $omniscient_tiers = $this->tree->getOmniscientTiers();
        // global $Lasercommerce_Tiers_Override;
        // $old_override = $Lasercommerce_Tiers_Override;
        // $Lasercommerce_Tiers_Override = $omniscient_tiers;

        $_product = wc_get_product($product_id);

        if($_product){
            $min_pricing = null;
            $max_pricing = null;            
            foreach ($children as $child) {
                $variation = $_product->get_child($child);
                $discounted_pricing = $this->maybeGetLowestPricing($variation);
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
                        $meta_key = $bound.'_'.$price_type.'_variation_id';
                        update_post_meta( $product_id, '_'.$meta_key, $bound_pricing->id);
                        // $_product->$meta_key = $bound_pricing->id;
                        // if(LASERCOMMERCE_PRICING_DEBUG) error_log("-> SYNC setting ". $product_id. ', '.$meta_key .' to '.$bound_pricing->id);
                    }
                }
            }

            // THIS WILL PROBABLY CAUSE ISSUES
            update_post_meta($product_id, '_price', '');
            // update_post_meta($product_id, '_price', $min_pricing->maybe_get_current_price());
        }

        // $Lasercommerce_Tiers_Override = $old_override;
        // OMNISCIENT OVERRIDE END

        // TODO: Maybe set _price

        //TODO: synchronize max_variation_id
    }    

    public function maybeVariationPrices( $prices_array, $_product, $display){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."VARIATIONPRICES($postID): ";

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

        foreach ( $_product->get_children( true ) as $variation_id ) {
            if ( $variation = $_product->get_child( $variation_id ) ) {
                $pricing = $this->maybeGetLowestPricing($variation);
                if(!$pricing) continue;
                $price         = $pricing->maybe_get_current_price();
                $regular_price = $pricing->regular_price;
                $sale_price    = $variation->sale_price;

                // If sale price does not equal price, the product is not yet on sale
                if ( ! $pricing->is_sale_active_now() ) {
                    $sale_price = $regular_price;
                }

                // If we are getting prices for display, we need to account for taxes
                if ( $display ) {
                    $price         = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $price ) : $variation->get_price_excluding_tax( 1, $price );
                    $regular_price = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $regular_price ) : $variation->get_price_excluding_tax( 1, $regular_price );
                    $sale_price    = $tax_display_mode == 'incl' ? $variation->get_price_including_tax( 1, $sale_price ) : $variation->get_price_excluding_tax( 1, $sale_price );
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

        // if(LASERCOMMERCE_DEBUG) {
        //     $string = implode("|", 
        //         array(
        //             ("price: ".serialize($prices_array['price'])),
        //             ("regular_price: ".serialize($prices_array['regular_price'])),
        //             ("sale_price: ".serialize($prices_array['sale_price'])),
        //         )
        //     );
        //     error_log($_procedure."POST: ".$string);
        // }        

        return $prices_array;
    }

    public function maybeGetChildren($children, $_product, $visible_only){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETCHILDREN($postID): ";

        // if(LASERCOMMERCE_DEBUG) {
        //     $string = implode("|", 
        //         array(
        //             ("children: ".serialize($children)),
        //             ("_product: ".serialize($_product->get_sku())),
        //             ("visible_only: ".serialize($visible_only)),
        //         )
        //     );
        //     error_log($_procedure.$string);
        // }

        //correct bad behaviour of when $visible_only = true;
        if($visible_only){

            foreach ( $_product->get_children(false) as $key => $child_id) {
                $variation = $_product->get_child( $child_id );
                if ( $variation and $variation->variation_is_visible() ) {
                    $children = array_merge($children, array($child_id));
                }
            }
            
        }

        //unhook self and get children without visibility requirement
        // remove_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);
        //hook self back in
        // add_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);


        return $children;
    }

    
    public function maybeVariationIsVisible($visible, $variation_id, $post_id, $variation){
        $_procedure = $this->_class."GETCHILDREN($variation_id): ";

        if(LASERCOMMERCE_DEBUG) {
            $string = implode("|", 
                array(
                    "visible: ".serialize($visible),
                    "variation_id: ".serialize($variation_id),
                    "post_id: ".serialize($post_id),
                    "variation: ".serialize($variation->get_sku()),
                )
            );
            error_log($_procedure.$string);
        }

        $visible = $this->maybeIsPurchasable($visible, $variation);

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($_procedure."returned: ".(string)$visible);

        return $visible;
    }   

    /**
     * Functions for filtering HTML output
     */    

    public function maybeGetStarHtml($price_html, $_product, $star){
        $postID = $this->getProductPostID($_product);
        $_procedure = $this->_class."GETSTARHTML|$star($postID): ";

        $user = wp_get_current_user();

        if(LASERCOMMERCE_HTML_DEBUG) {
            $string = implode("|", 
                array(
                    ("html: $price_html"),
                    // ("price: ".$_product->price),
                    // ("regular_price: ".$_product->regular_price),
                    // ("sale_price: ".$_product->sale_price),
                    // ("product: ".$_product->id),
                    // ("user: ".$user->ID)
                )
            );
            error_log($_procedure.$string);
        }

        return $price_html;
    }

    public function maybeGetPriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'price');
    }    

    public function maybeGetSalePriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'sale_price');
    }

    public function maybeGetVariablePriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'variable_price');
    }

    public function maybeGetVariationPriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'variation_price');
    }

    public function maybeGetVariationSalePriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'variation_sale_price');
    }

    public function maybeGetEmptyPriceHtml($price_html, $_product){
        return $this->maybeGetStarHtml($price_html, $_product, 'empty_price');
    }
    
    public function addActionsAndFilters() {
        // Admin filters:
        global $lasercommerce_pricing_trace;
        $_procedure = $this->_class."ADDACTIONS: ";
        $lasercommerce_pricing_trace = $_procedure;
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."Called addActionsAndFilters");  
        
        //helper class for tier tree functions    

        
        //Price / Display filters:
        add_filter( 'woocommerce_get_price', array(&$this, 'maybeGetPrice'), 0, 2 );
        add_filter( 'woocommerce_get_regular_price', array(&$this, 'maybeGetRegularPrice' ), 0, 2 ); 
        add_filter( 'woocommerce_get_sale_price', array(&$this, 'maybeGetSalePrice'), 0, 2 );
        add_filter( 'woocommerce_get_variation_price', array($this, 'maybeGetVariationPrice'), 0, 4 );
        add_filter( 'woocommerce_get_variation_regular_price', array($this, 'maybeGetVariationRegularPrice'), 0, 4 );
        add_filter( 'woocommerce_get_variation_sale_price', array($this, 'maybeGetVariationSalePrice'), 0, 4 );
        add_filter( 'woocommerce_cart_product_price', array(&$this, 'maybeGetCartPrice'), 0, 2 );
        add_filter( 'woocommerce_cart_item_price', array(&$this, 'maybeGetCartItemPrice'), 0, 3 );
        add_filter( 'woocommerce_cart_item_subtotal', array(&$this, 'maybeGetCartItemSubtotal'), 0, 3);
        add_filter( 'woocommerce_checkout_item_subtotal', array(&$this, 'maybeGetCartItemSubtotal'), 0, 3);
        add_filter( 'woocommerce_is_purchasable', array(&$this, 'maybeIsPurchasable'), 0, 2);

        add_filter( 'woocommerce_sale_price_html', array(&$this, 'maybeGetSalePriceHtml'), 0, 2);
        add_filter( 'woocommerce_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);     
        add_filter( 'woocommerce_variable_price_html', array(&$this, 'maybeGetVariablePriceHtml'), 0, 2 );
        add_filter( 'woocommerce_variation_price_html', array(&$this, 'maybeGetVariationPriceHtml'), 0, 2 );
        add_filter( 'woocommerce_variation_sale_price_html', array(&$this, 'maybeGetVariationSalePriceHtml'), 0, 2 );
        add_filter( 'woocommerce_empty_price_html', array(&$this, 'maybeGetEmptyPriceHtml'), 0, 2 );
        add_filter( 'woocommerce_get_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);     

        add_filter( 'woocommerce_variation_prices', array(&$this, 'maybeVariationPrices'), 0, 3);
        add_filter( 'woocommerce_get_children', array(&$this, 'maybeGetChildren'), 0, 3);
        add_filter( 'woocommerce_variation_is_visible', array( $this, 'maybeVariationIsVisible' ), 0, 4 );

        add_filter( 'woocommerce_get_price_including_tax', array(&$this, 'maybeGetPriceInclTax'), 0, 3);
        add_filter( 'woocommerce_get_price_excluding_tax', array(&$this, 'maybeGetPriceExclTax'), 0, 3);

        add_action('woocommerce_variable_product_sync', array(&$this, 'maybeVariableProductSync'), 0, 2);


        parent::addActionsAndFilters();

        // MEMBERSHIPS STUFF

            // add_filter( 'woocommerce_product_is_visible', array( $this, 'product_is_visible' ), 10, 2 );
            // add_filter( 'woocommerce_variation_is_visible', array( $this, 'variation_is_visible' ), 10, 2 );

        // remove_filter( 'woocommerce_product_is_visible', array('WC_Memberships_Restrictions', 'product_is_visible'));
        // remove_filter( 'woocommerce_variation_is_visible', array('WC_Memberships_Restrictions', 'variation_is_visible'));

        // BUNDLES STUFF



        // add_filter( 'woocommerce_available_variation', array(&$this, 'maybeAvailableVariationPreBundle'), 9, 3);
        // add_filter( 'woocommerce_available_variation', array(&$this, 'maybeAvailableVariationPostBundle'), 11, 3);

        // add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'PreWooBundlesValidation' ), 9, 6 );
        // add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'woo_bundles_validation' ), 10, 6 );
        // add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'PostWooBundlesValidation' ), 11, 6 );

        // add_filter( 'woocommerce_product_is_visible', 


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
        
        
        //TODO: make modifications to tax
        // add_filter( 'woocommerce_get_cart_tax',  
        // add_filter( 'option_woocommerce_calc_taxes', 
        // add_filter( 'woocommerce_product_is_taxable'
        
        //TODO: Make modifications to product columns, quick edit: http://www.creativedev.in/2014/01/to-create-custom-field-in-woocommerce-products-admin-panel/
        
    }

/**
 * Functions for tricking product bundles into thinking products have prices using a clever ruse
 */

    // public function constructCleverRuse($product_id, $new_price){
    //     $old_price = get_post_meta($product_id, '_price', True);
    //     $old_prices = get_post_meta($product_id, 'prices_old', True);
    //     if($old_prices) {
    //         $old_prices = $old_prices . '|' . $old_price ;
    //     } else{
    //         $old_prices = $old_price;
    //     }
    //     // error_log("Called constructCleverRuse callback| product_id: $product_id");
    //     // error_log("-> constructCleverRuse old price: ". $old_price);
    //     // error_log("-> constructCleverRuse new price: ". $new_price);
    //     // error_log("-> constructCleverRuse old prices: ". $old_prices);
    //     update_post_meta($product_id, 'prices_old', $old_prices);
    //     update_post_meta($product_id, '_price', $new_price);
    // }

    // public function destructCleverRuse($product_id){
    //     // error_log("Called destructCleverRuse callback | product_id: $product_id");
    //     $old_prices = explode('|',get_post_meta($product_id, 'prices_old', True));
    //     // error_log("-> destructCleverRuse old prices: ". serialize($old_prices));  
    //     $old_price = array_pop($old_prices);
    //     // error_log("-> destructCleverRuse old price: ". $old_price);  
    //     $old_prices = implode('|', $old_prices);
    //     // error_log("-> destructCleverRuse old prices: ". serialize($old_prices));  
    //     $new_price = get_post_meta($product_id, '_price', True);
    //     // error_log("-> destructCleverRuse new price: ". $new_price); 
    //     update_post_meta($product_id, 'prices_old', $old_prices); 
    //     update_post_meta($product_id, '_price', $old_price);
    // }

    // public function maybeAvailableVariationPreBundle($variation_data, $_product, $_variation ){
    //     global $Lasercommerce_Tier_Tree;
    //     $variation_id = $Lasercommerce_Tier_Tree->getProductPostID( $_variation );

    //     $this->constructCleverRuse($variation_id, $this->maybeGetPrice($_variation->price, $_variation));
    //     return $variation_data;
    // }

    // public function maybeAvailableVariationPostBundle($variation_data, $_product, $_variation ){
    //     global $Lasercommerce_Tier_Tree;
    //     $variation_id = $Lasercommerce_Tier_Tree->getProductPostID( $_variation );

    //     $this->destructCleverRuse($variation_id);
    //     return $variation_data;
    // }

    // public function WooBundlesGetVariations( $product_id ){
    //     // error_log("Called WooBundlesGetVariations"); 
    //     // error_log(" | product_id: $product_id");

    //     $variations = array();

    //     $terms        = get_the_terms( $product_id, 'product_type' );
    //     $product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
    //     if ( $product_type === 'bundle' ) {
    //         if(class_exists('WC_PB_Core_Compatibility')){
    //             $product = WC_PB_Core_Compatibility::wc_get_product( $product_id );
    //             if ( ! $product ) {
    //                 return $variations;
    //             }

    //             $bundled_items = $product->get_bundled_items();

    //             if ( ! $bundled_items ) {
    //                 return $add;
    //             }        
                
    //             foreach ($bundled_items as $bundled_item_id => $bundled_item) {
    //                 $id                   = $bundled_item->product_id;
    //                 $bundled_product_type = $bundled_item->product->product_type;
    //                 // error_log(" | processing bundled item id: $id");

    //                 if($bundled_product_type == 'variable'){
    //                     $allowed_variations = $bundled_item->get_product_variations();
    //                     // error_log("  | allowed_variations: ".serialize($allowed_variations));
    //                     if($allowed_variations){
    //                         foreach ($allowed_variations as $variation) {
    //                             if(isset($variation['variation_id'])){
    //                                 $variations[] = $variation['variation_id'];
    //                             }
    //                         }
    //                     }
    //                 }
    //             }        
    //         } 
    //     }
    //     // error_log(" | returning ".serialize($variations));
    //     return $variations;
    // }

    // public function PreWooBundlesValidation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
    //     // error_log("called PreWooBundlesValidation callback");
    //     // error_log(" | add: $add");
    //     // error_log(" | product_id: $product_id");
    //     $variations = $this->WooBundlesGetVariations( $product_id );
    //     // error_log(" | variations: ".serialize($variations));

    //     foreach ($variations as $variation_id) {
    //         $this->constructCleverRuse($variation_id, 'X');
    //     }

    //     return $add;
    // }

    // public function PostWooBundlesValidation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
    //     // error_log("called PostWooBundlesValidation callback | add: $add");
    //     // error_log(" | add: $add");
    //     // error_log(" | product_id: $product_id");
    //     $variations = $this->WooBundlesGetVariations( $product_id );
    //     // error_log(" | variations: ".serialize($variations));

    //     foreach ($variations as $variation_id) {
    //         $this->destructCleverRuse($variation_id);
    //     }

    //     return $add;
    // }

    // public function overrideBundledPrices(){
    //     // error_log("called override_bundled_prices callback");
    //     // error_log(" | add: $add");
    //     // error_log(" | product_id: $product_id");
    //     $variations = $this->WooBundlesGetVariations( $product_id );
    //     // error_log(" | variations: ".serialize($variations));        
    // }

}
