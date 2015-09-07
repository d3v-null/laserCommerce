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
     * include the lasercommerce admin tab in woocommerce settings
     * 
     * @param array $settings An array specifying the settings to display in the admin page
     * @return array $settings 
     */
    public function includeAdminPage($settings){
        $pluginDir = plugin_dir_path( __FILE__ );
        include_once(LASERCOMMECE_BASE.'/lib/Lasercommerce_Admin.php');
        $settings[] = new Lasercommerce_Admin($this->getOptionNamePrefix());
        return $settings;
    }

    /**
     * Gets the role of the current user
     * @return string $role The role of the current user
     */
    public function getCurrentUserRoles(){
        trigger_error("Deprecated function called: getCurrentUserRoles", E_USER_NOTICE);
    }    

    private function getMajorTiers(){
        global $Lasercommerce_Tier_Tree;
        return $Lasercommerce_Tier_Tree->getMajorTiers();
    }

    private function maybeGetVisiblePricing($_product=''){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\ncalled maybeGetVisiblePricing");
        if($_product) {
            global $Lasercommerce_Tier_Tree;
            
            $visibleTiers = $Lasercommerce_Tier_Tree->getAvailableTiers();
            // $visibleTiers = $currentUserRoles;
            array_push($visibleTiers, ''); 
            
            $id = $Lasercommerce_Tier_Tree->getPostID( $_product );

            $pricings = array();
            foreach ($visibleTiers as $role) {
                $pricing = new Lasercommerce_Pricing($id, $role);
                if($pricing->regular_price){
                    $pricings[$role] = $pricing;
                }
            }

            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );

            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) {
            //     error_log("-> maybeGetVisiblePricing returned: ");//.serialize(array_keys($pricings)));
            //     foreach ($pricings as $key => $pricing) {
            //         error_log("$key: ". (string)$pricing);
            //     }
            // }
            
            return $pricings;
        } else { 
            // if(LASERCOMMERCE_DEBUG) error_log("product not valid");
            return null;
        }   

    }

    private function maybeGetLowestPricing($_product=''){
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );  

        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("called maybeGetLowestPricing I: ".(string)$postID." S:".serialize($_product->get_sku()));
        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = array_shift($pricings);
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetLowestPricing returned ".($pricing->__toString()));
            return $pricing;
        } else {
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetLowestPricing returned null because no visible pricing");
            return null;
        }
    }

    private function maybeGetHighestPricing($_product=''){
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );  

        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetHighestPricing I: $postID S:".serialize($_product->get_sku()));
        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = array_pop($pricings);
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetHighestPricing returned ".($pricing->__toString()));
            return $pricing;
        } else {
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetHighestPricing returned null because no visible pricing");
            return null;
        }
    }

    public function isWCPrice($price='', $_product=''){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("Called isWCPrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );
        if($_product and isset($postID)){
            $WC_price = get_post_meta($postID, '_price', True);
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> price (".$postID.") : ".$WC_price);
            if( floatval($WC_price) == floatval($price)){
                // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCPrice returned true");
                return true;
            }
        }
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCPrice returned false");
        return false;
    }

    public function isWCRegularPrice($price='', $_product=''){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("Called isWCRegularPrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );        
        if($_product and isset($postID)){
            $WC_regular_price = get_post_meta($postID, '_regular_price', True);
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> regular price (".$postID.") : ".$WC_regular_price);
            if( floatval($WC_regular_price) == floatval($price)){
                // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCRegularPrice returned true");
                return true;
            }
        }
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCRegularPrice returned false");
        return false;
    }

    public function isWCSalePrice($price='', $_product=''){
        // TODO: ignore if blank
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("Called isWCSalePrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );        
        if($_product and isset($postID)){
            $WC_sale_price = get_post_meta($postID, '_sale_price', True);
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> sale price (".$postID."): ".$WC_sale_price);
            if( floatval($WC_sale_price) == floatval($price)){
                // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCSalePrice returned true");
                return true;
            }
        }
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> isWCSalePrice returned false");
        return false;
    }

    public function maybeGetVariationPricing( $_product, $min_or_max ){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetVariationPricing:$min_or_max | S:".serialize($_product->get_sku())." ));        
        
        $meta_key = ($min_or_max == 'max' ? 'max_price_variation_id' : 'min_price_variation_id');
        $target_id = $_product->$meta_key;
        if(!$target_id){
            $_product->variable_product_sync();
            $target_id = $_product->$meta_key;
        }

        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> creating target with id: ".$target_id);
        $target = wc_get_product($target_id);

        if($target){
            $value = $this->maybeGetLowestPricing($target);
        } else {
            $value = null;
        }

        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetVariationPrice:$min_or_max returned ".(string)($value));
        return $value;
    }

    public function maybeGetVariationPrice( $price = '', $_product, $min_or_max, $display ) {
        $pricing = $this->maybeGetVariationPricing($_product, $min_or_max);
        if($pricing){
            return $pricing->maybe_get_current_price();
        } else {
            return $price;
        }
    }
    public function maybeGetVariationRegularPrice($price = '', $_product, $min_or_max, $display) {
        $pricing = $this->maybeGetVariationPricing($_product, $min_or_max);
        if($pricing){
            return $pricing->regular_price;
        } else {
            return $price;
        }
    }
    public function maybeGetVariationSalePrice($price = '', $_product, $min_or_max, $display) {
        $pricing = $this->maybeGetVariationPricing($_product, $min_or_max);
        if($pricing){
            return $pricing->sale_price;
        } else {
            return $price;
        }
    }

    /**
     * Generalization of maybeGet*Price
     */
    public function maybeGetStarPrice($star = '', $price = '', $_product = ''){
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );          
        if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("Called maybeGetStarPrice:$star | p: $price I: $postID S:".(string)($_product->get_sku()));
        //only override if it is a WC price
        $override = ($price == '' or $this->isWCPrice($price, $_product) or $this->isWCRegularPrice($price, $_product) or $this->isWCSalePrice($price, $_product));
        //TODO: Add condition for variable products
        if($_product->is_type( 'variable' )){
            $lowestPricing = $this->maybeGetVariationPricing( $_product, 'min');
        } else {
            $lowestPricing = $this->maybeGetLowestPricing($_product);
        }
        if($lowestPricing and $override) {
            switch ($star) {
                case '': 
                case 'cart':
                    $price = $lowestPricing->maybe_get_current_price();
                    // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'regular': 
                    $price = $lowestPricing->regular_price;
                    // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'sale': 
                    $price = $lowestPricing->sale_price;
                    // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");            
                    break;                    
                // default:
                //     # code...
                //     break;
            }
        }
        if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> maybeGetStarPrice:$star returned $price");
        return $price;   
    }

    public function maybeGetRegularPrice($price = '', $_product=''){
        return $this->maybeGetStarPrice('regular', $price, $_product);
    }

    public function maybeGetSalePrice($price = '', $_product = ''){ 
        return $this->maybeGetStarPrice('sale', $price, $_product);
    }

    public function maybeGetPrice($price = '', $_product = ''){
        return $this->maybeGetStarPrice('', $price, $_product);
    }

    public function maybeGetCartPrice($price = '', $_product = ''){
        return $this->maybeGetStarPrice('cart', $price, $_product);
    }

    public function maybeGetPriceInclTax($price ='', $qty, $_product){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetPriceInclTax with price: ".$price);
        return $this->maybeGetStarPrice('', $price, $_product);
    }

    public function maybeGetPriceExclTax($price ='', $qty, $_product){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetPriceExclTax with price: ".$price);
        return $this->maybeGetStarPrice('', $price, $_product);
    }

    public function maybeGetCartItemPrice( $price, $values, $cart_item_key ){
        if (LASERCOMMERCE_DEBUG and PRICE_DEBUG) {
            error_log("Called maybeGetCartItemPrice");
            error_log(" | price: ".serialize($price));
            error_log(" | values: ".serialize($values));
            error_log(" | cart_item_key: ".serialize($cart_item_key));
        }
        $quantity = isset($values['quantity'])?$values['quantity']:'';
        $product_id = isset($values['product_id'])?$values['product_id']:'';
        $variation_id = isset($values['variation_id'])?$values['variation_id']:'';

        // if isset($variation_id){
        //     $unit_price = 
        // }

        return $price;
    }

    public function maybeGetCartItemSubtotal( $subtotal, $values, $cart_item_key ) {
        if (LASERCOMMERCE_DEBUG and PRICE_DEBUG) {
            error_log("Called maybeGetCartItemSubtotal");
            error_log(" | subtotal: ".serialize($subtotal));
            error_log(" | values: ".serialize($values));
            error_log(" | cart_item_key: ".serialize($cart_item_key));
        }
        return $subtotal;
    }

    public function maybeIsPurchasable($purchasable, $_product){
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nmaybeIsPurchasable closure called | p:".(string)$purchasable." S:".(string)$_product->get_sku());
        if($_product && $_product->is_type('variable')){
            // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("is variable");
            $children = $_product->get_children();
            if( is_array($children) && !empty($children)){
                foreach ($children as $child_id) {
                    $child = $_product->get_child($child_id);
                    if ($child->is_purchasable()){
                        $purchasable = true;
                    }
                }
            } 
        } 
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("\nmaybeIsPurchasable closure returned: ".(string)$purchasable);
        return $purchasable;
    }

    public function maybeVariableProductSync( $product_id, $children ){
        //TODO: WHAT IF max_price_id is not the same as max_regular_price_id???

        // OMNISCIENT OVERRIDE BEGIN
        global $Lasercommerce_Tier_Tree;
        $omniscient_roles = $Lasercommerce_Tier_Tree->getOmniscientRoles();
        global $Lasercommerce_Roles_Override;
        $old_override = $Lasercommerce_Roles_Override;
        $Lasercommerce_Roles_Override = $omniscient_roles;

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
                        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("-> SYNC setting ". $product_id. ', '.$meta_key .' to '.$bound_pricing->id);
                    }
                }
            }

            // THIS WILL PROBABLY CAUSE ISSUES
            update_post_meta($product_id, '_price', '');
            // update_post_meta($product_id, '_price', $min_pricing->maybe_get_current_price());
        }

        $Lasercommerce_Roles_Override = $old_override;
        // OMNISCIENT OVERRIDE END

        // TODO: Maybe set _price


        //TODO: synchronize max_variation_id
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
    //     $variation_id = $Lasercommerce_Tier_Tree->getPostID( $_variation );

    //     $this->constructCleverRuse($variation_id, $this->maybeGetPrice($_variation->price, $_variation));
    //     return $variation_data;
    // }

    // public function maybeAvailableVariationPostBundle($variation_data, $_product, $_variation ){
    //     global $Lasercommerce_Tier_Tree;
    //     $variation_id = $Lasercommerce_Tier_Tree->getPostID( $_variation );

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


/**
 * Functions for filtering HTML output
 */


    public function maybeGetStarHtml($price_html, $_product, $star){
        $user = wp_get_current_user();

        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("called maybeGetStarHtml:$star");
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> html: $price_html");
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> price: ".$_product->price);
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> product: ".$_product->id);
        if(LASERCOMMERCE_DEBUG and HTML_DEBUG) error_log("-> user: ".$user->ID);
        
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
    
    public function variationIsVisible(){
        //todo: this
    }
    
    public function addActionsAndFilters() {
        // Admin filters:
        if(LASERCOMMERCE_DEBUG) error_log("LASERCOMMERCE_PLUGIN: Called addActionsAndFilters");

        add_action( 'admin_enqueue_scripts', array( &$this, 'product_admin_scripts') );
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        //helper class for tier tree functions    
        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $this->getOptionNamePrefix() );
        }     
        $this->maybeAddSaveTierFields( $Lasercommerce_Tier_Tree->getTiers(), $Lasercommerce_Tier_Tree->getNames() );

        
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

        add_filter( 'woocommerce_get_price_including_tax', array(&$this, 'maybeGetPriceInclTax'), 0, 3);
        add_filter( 'woocommerce_get_price_excluding_tax', array(&$this, 'maybeGetPriceExclTax'), 0, 3);

        add_action('woocommerce_variable_product_sync', array(&$this, 'maybeVariableProductSync'), 0, 2);

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
        
            
        
        parent::addActionsAndFilters();

    }


}
