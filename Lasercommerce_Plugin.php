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

if( !defined('LASERCOMMERCE_DEBUG')){
    define( 'PRICE_DEBUG', False);
    define( 'HTML_DEBUG', False);
} else {
    if(!defined('HTML_DEBUG'))
        define( 'HTML_DEBUG', LASERCOMMERCE_DEBUG);
    if(!defined('PRICE_DEBUG'))
        define( 'PRICE_DEBUG', LASERCOMMERCE_DEBUG);
        
}




include_once('Lasercommerce_LifeCycle.php');
include_once('lib/Lasercommerce_Tier_Tree.php');
include_once('lib/Lasercommerce_Pricing.php');
// include_once('Lasercommerce_UIE.php');

/**
 * Registers Wordpress and woocommerce hooks to modify prices
 */
class Lasercommerce_Plugin extends Lasercommerce_LifeCycle {

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
        global $Lasercommerce_Plugin;
        if( !isset($Lasercommerce_Plugin) ) {
            $Lasercommerce_Plugin = &$this;
        }

        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $this->getOptionNamePrefix() );
        }   
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
        include('Lasercommerce_Admin.php');
        $settings[] = new Lasercommerce_Admin($this->getOptionNamePrefix());
        return $settings;
    }
    
    //TODO: this

    /**
     * Used by maybeAddSaveTierField to add price fields to the product admin interface for a given tier
     * 
     * @param string $tier_slug The internal name for the price tier (eg. wholesale)
     * @param string $tier_name The human readable name for the price tier (eg. Wholesale)
     */
    private function addTierFields($tier_slug, $tier_name = ""){
        //sanitize tier_slug and $tier_name
        $tier_slug = sanitize_key($tier_slug);
        //TODO: maybe sanitize tier_name a little
        //$tier_name = sanitize_key($tier_name);
        if( $tier_name == "" ) $tier_name = $tier_slug;
        $prefix = $this->getOptionNamePrefix(); 
        
        // The following code was inspred by the same code in WooCommerce in order to match the style:
        // https://github.com/woothemes/woocommerce/blob/master/includes/admin/meta-boxes/class-wc-meta-box-product-data.php

        add_action( 
            'woocommerce_product_options_general_product_data',  
            function() use ($tier_slug, $tier_name, $prefix){
                global $post, $thepostid, $Lasercommerce_Tier_Tree;
                if( !isset($thepostid) ){
                    $thepostid = $post->ID;
                }
                // if(WP_DEBUG) error_log("product options admin for $thepostid, $tier_slug");
                echo '<div class="options_group pricing_extra show_if_simple">';

                $pricing = new Lasercommerce_Pricing($thepostid, $tier_slug);
                $regular_price  = (isset($pricing->regular_price)) ? esc_attr($pricing->regular_price) : '' ;
                $sale_price     = (isset($pricing->sale_price)) ? esc_attr($pricing->sale_price) : '' ;

                // Regular Price
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $prefix.$tier_slug."_regular_price", 
                        'value' => $regular_price,
                        'label' => $tier_name . ' ' . __( "Regular Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'data_type' => 'price' 
                    ) 
                );   
                // Special Price
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $prefix.$tier_slug."_sale_price", 
                        'value' => $sale_price,
                        'label' => $tier_name . ' ' . __( "Sale Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', 'lasercommerce' ) . '</a>',
                        'data_type' => 'price' 
                    ) 
                );

                $sale_price_dates_from = ( $date = $pricing->sale_price_dates_from ) ? date_i18n( 'Y-m-d', floatval($date) ) : '';
                $sale_price_dates_to = ( $date = $pricing->sale_price_dates_to ) ? date_i18n( 'Y-m-d', floatval($date) ) : '';
                $sale_price_dates_from_id = $prefix . $tier_slug . '_sale_price_dates_from';
                $sale_price_dates_to_id   = $prefix . $tier_slug . '_sale_price_dates_to';

                echo '  <p class="form-field sale_price_dates_fields_extra">
                            <label for="'.$sale_price_dates_from_id.'">' . __( 'Sale Price Dates', 'woocommerce' ) . '</label>
                            <input type="text" class="short" name="'.$sale_price_dates_from_id.'" id="'.$sale_price_dates_from_id.'" value="' . esc_attr( $sale_price_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            <input type="text" class="short" name="'.$sale_price_dates_to_id.'" id="'.$sale_price_dates_to_id.'" value="' . esc_attr( $sale_price_dates_to ) . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            <a href="#" class="cancel_sale_schedule">'. __( 'Cancel', 'woocommerce' ) .'</a>
                        </p>';

                echo "</div>";
            }
        );

        /* for variations <?php do_action( 'woocommerce_product_after_variable_attributes', $loop, $variation_data, $variation ); ?> */
        add_action(
            'woocommerce_product_after_variable_attributes', 
            function($loop, $variation_data, $variation) use ($tier_slug, $tier_name, $prefix){
                if(WP_DEBUG) error_log("called woocommerce_product_after_variable_attributes closure");

                $variation_id = $variation->ID;
                
                $regular_label = $tier_name . ' ' . __( "Regular Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')';
                $sale_label = $tier_name . ' ' . __( 'Sale Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')';
                $regular_name = 'variable_' . $tier_slug . '_regular_price[' . (string)($loop) . ']';
                $sale_name = 'variable_' . $tier_slug . '_sale_price[' . (string)($loop) . ']';

                $pricing = new Lasercommerce_Pricing($variation_id, $tier_slug);
                $regular_price  = (isset($pricing->regular_price)) ? esc_attr($pricing->regular_price) : '' ;
                $sale_price     = (isset($pricing->sale_price)) ? esc_attr($pricing->sale_price) : '' ;

                ?>
                <div class="variable_pricing">
                    <p class="form-row form-row-first">
                        <label><?php echo $regular_label; ?></label>
                        <input type="text" size="5" name="<?php echo $regular_name; ?>" value="<?php echo $regular_price; ?>" class="wc_input_price" placeholder="<?php _e( 'Variation price', 'woocommerce' ); ?>" />
                    </p>
                    <p class="form-row form-row-last">
                        <label><?php echo $sale_label; ?> <a href="#" class="sale_schedule"><?php _e( 'Schedule', 'woocommerce' ); ?></a><a href="#" class="cancel_sale_schedule" style="display:none"><?php _e( 'Cancel schedule', 'woocommerce' ); ?></a></label>
                        <input type="text" size="5" name="<?php echo $sale_name; ?>" value="<?php echo $sale_price; ?>" class="wc_input_price" />
                    </p>
                </div>  
                <?php          
            },
            0,
            3
        );
        //TODO: other product types
    }
    
    /**
     * Used by maybeAddSavePriceField to add a hook to save price fields for a given tier
     * TODO: make this work with special prices 
     * 
     * @param string $tier_slug The slug of the tier the price fields apply to  
     * @param string $tier_name The human readable name for the price tier
     */ 
    private function saveTierFields($tier_slug, $tier_name = ""){
        //sanitize tier_slug and $tier_name
        $tier_slug = sanitize_key($tier_slug);
        $tier_name = sanitize_key($tier_name);
        if( $tier_name == "" ) $tier_name = $tier_slug;
        $prefix = $this->getOptionNamePrefix(); 
        $process_product_meta_callback = function($post_id) use ($tier_slug, $tier_name, $prefix){
            // if(WP_DEBUG) error_log("calling process_product_meta_simple callback");

            global $post, $thepostid;
            if( !isset($thepostid) ){
                $thepostid = $post->ID;
            }

            $pricing = new Lasercommerce_Pricing($thepostid, $tier_slug);

            $regular_id     = $prefix.$tier_slug."_regular_price";
            $regular_price  = isset($_POST[$regular_id]) ? wc_format_decimal( $_POST[$regular_id] ) : '';
            $pricing->regular_price = $regular_price;
            
            $sale_id     = $prefix.$tier_slug."_sale_price";
            $sale_price  = isset($_POST[$sale_id]) ? wc_format_decimal( $_POST[$sale_id] ) : '';
            $pricing->sale_price = $sale_price;

            $sale_price_dates_from_id   = $prefix.$tier_slug.'_sale_price_dates_from';
            $sale_price_dates_from      = isset( $_POST[$sale_price_dates_from_id] ) ? wc_clean( $_POST[$sale_price_dates_from_id] ) : '';
            $sale_price_dates_to_id     = $prefix.$tier_slug.'_sale_price_dates_to';
            $sale_price_dates_to        = isset( $_POST[$sale_price_dates_to_id] ) ? wc_clean( $_POST[$sale_price_dates_to_id] ) : '';

            $pricing->sale_price_dates_from = $sale_price_dates_from ? strtotime($sale_price_dates_from) : '';
            $pricing->sale_price_dates_to   = $sale_price_dates_to   ? strtotime($sale_price_dates_to) : '';

            if(!$sale_price_dates_from and $sale_price_dates_to) {
                $pricing->sale_price_dates_from = strtotime( 'NOW', current_time( 'timestamp' ) ) ;
            }
        };

        add_action(  'woocommerce_process_product_meta_simple', $process_product_meta_callback );
        add_action(  'woocommerce_process_product_meta_bundle', $process_product_meta_callback );
        add_action(  'woocommerce_process_product_meta_composite', $process_product_meta_callback );

        //TODO: other product types 
        /* for variable: do_action( 'woocommerce_save_product_variation', $variation_id, $i ); */
        add_action( 
            'woocommerce_save_product_variation', 
            function($variation_id, $i=0) use ($tier_slug, $tier_name, $prefix){
                if(WP_DEBUG) {
                    error_log("called woocommerce_save_product_variation closure");
                    error_log(" -> variation_id: $variation_id" );
                    error_log(" -> i: $i" );
                    error_log(" -> tier_slug: $tier_slug");
                }

                $pricing = new Lasercommerce_Pricing($variation_id, $tier_slug);

                $variable_regular_price         = $_POST['variable_'.$tier_slug.'_regular_price'];
                $variable_sale_price            = $_POST['variable_'.$tier_slug.'_sale_price'];   
                // $variable_sale_price_dates_from = $_POST['variable_'.$tier_slug.'_sale_price_dates_from'];
                // $variable_sale_price_dates_to   = $_POST['variable_'.$tier_slug.'_sale_price_dates_to'];

                $regular_price = wc_format_decimal( $variable_regular_price[ $i ] );
                $sale_price    = $variable_sale_price[ $i ] === '' ? '' : wc_format_decimal( $variable_sale_price[ $i ] );
                if(WP_DEBUG) {
                    error_log("results:");
                    error_log(" -> regular price: ".serialize($regular_price));
                    error_log(" -> sale price: ".serialize($sale_price));
                }

                // $date_from     = wc_clean( $variable_sale_price_dates_from[ $i ] );
                // $date_to       = wc_clean( $variable_sale_price_dates_to[ $i ] );

                // Save prices

                $pricing->regular_price = $regular_price;
                $pricing->sale_price = $sale_price;

                // update_post_meta( $variation_id, $prefix.$tier_slug.'_regular_price', $regular_price );
                // update_post_meta( $variation_id, $prefix.$tier_slug.'_sale_price', $sale_price );

                // Save Dates
                // update_post_meta( $variation_id, $prefix.$tier_slug.'_sale_price_dates_from', $date_from ? strtotime( $date_from ) : '' );
                // update_post_meta( $variation_id, $prefix.$tier_slug.'_sale_price_dates_to', $date_to ? strtotime( $date_to ) : '' );            

                // if ( $date_to && ! $date_from ) {
                //     update_post_meta( $variation_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
                // }

                // // Update price if on sale
                // if ( '' !== $sale_price && '' === $date_to && '' === $date_from ) {
                //     update_post_meta( $variation_id, '_price', $sale_price );
                // } else {
                //     update_post_meta( $variation_id, '_price', $regular_price );
                // }

                // if ( '' !== $sale_price && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                //     update_post_meta( $variation_id, '_price', $sale_price );
                // }

                // if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                //     update_post_meta( $variation_id, '_price', $regular_price );
                //     update_post_meta( $variation_id, '_sale_price_dates_from', '' );
                //     update_post_meta( $variation_id, '_sale_price_dates_to', '' );
                // }

            },
            0,
            2
        );
    }
    
    /**
     * Adds text fields and form metadata handlers to product data page for given tiers
     * @param array $tiers a list of tier slugs
     * @param array $names A mapping of tier slugs to their names
     */
    public function maybeAddSaveTierFields($tiers, $names = array()){
        //if(WP_DEBUG) error_log("Called maybeAddSaveTierFields: ".serialize($tiers));

        foreach($tiers as $tier_slug){
            $tier_name = isset($names[$tier_slug])?$names[$tier_slug]:$tier_slug;
            $this->addTierFields($tier_slug, $tier_name);
            $this->saveTierFields($tier_slug, $tier_name);
        } 
    }
    
    /**
     * Gets the role of the current user
     * @return string $role The role of the current user
     */
    public function getCurrentUserRoles(){
        // if(WP_DEBUG) error_log("called getCurrentUserRoles");
        global $Lasercommerce_Roles_Override;
        if(isset($Lasercommerce_Roles_Override) and is_array($Lasercommerce_Roles_Override)){
            // if(WP_DEBUG and PRICE_DEBUG) {
            //     error_log("-> Override is: ");
            //     foreach ($Lasercommerce_Roles_Override as $value) {
            //         error_log("--> $value");
            //     }
            // }
            $roles = $Lasercommerce_Roles_Override;
        } else {
            $current_user = wp_get_current_user();
            // if(WP_DEBUG) error_log("-> current user: ".$current_user->ID);
            $roles = $current_user->roles;
        }
        // if(WP_DEBUG and PRICE_DEBUG) error_log("--> roles: ".serialize($roles));
        return $roles;
    }

    private function getMajorTiers(){
        global $Lasercommerce_Tier_Tree;
        return $Lasercommerce_Tier_Tree->getMajorTiers();
    }

    private function maybeGetVisiblePricing($_product=''){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\ncalled maybeGetVisiblePricing");
        if($_product) {
            global $Lasercommerce_Tier_Tree;
            
            $currentUserRoles = $this->getCurrentUserRoles();
            $visibleTiers = $Lasercommerce_Tier_Tree->getAvailableTiers($currentUserRoles);
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

            // if(WP_DEBUG and PRICE_DEBUG) {
            //     error_log("-> maybeGetVisiblePricing returned: ");//.serialize(array_keys($pricings)));
            //     foreach ($pricings as $key => $pricing) {
            //         error_log("$key: ". (string)$pricing);
            //     }
            // }
            
            return $pricings;
        } else { 
            // if(WP_DEBUG) error_log("product not valid");
            return null;
        }   

    }

    private function maybeGetLowestPricing($_product=''){
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );  

        // if(WP_DEBUG and PRICE_DEBUG) error_log("called maybeGetLowestPricing I: ".(string)$postID." S:".serialize($_product->get_sku()));
        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = array_shift($pricings);
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetLowestPricing returned ".($pricing->__toString()));
            return $pricing;
        } else {
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetLowestPricing returned null because no visible pricing");
            return null;
        }
    }

    private function maybeGetHighestPricing($_product=''){
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );  

        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetHighestPricing I: $postID S:".serialize($_product->get_sku()));
        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = array_pop($pricings);
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetHighestPricing returned ".($pricing->__toString()));
            return $pricing;
        } else {
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetHighestPricing returned null because no visible pricing");
            return null;
        }
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
                        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> SYNC setting ". $product_id. ', '.$meta_key .' to '.$bound_pricing->id);
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

    public function isWCPrice($price='', $_product=''){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("Called isWCPrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );
        if($_product and isset($postID)){
            $WC_price = get_post_meta($postID, '_price', True);
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> price (".$postID.") : ".$WC_price);
            if( floatval($WC_price) == floatval($price)){
                // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCPrice returned true");
                return true;
            }
        }
        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCPrice returned false");
        return false;
    }

    public function isWCRegularPrice($price='', $_product=''){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("Called isWCRegularPrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );        
        if($_product and isset($postID)){
            $WC_regular_price = get_post_meta($postID, '_regular_price', True);
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> regular price (".$postID.") : ".$WC_regular_price);
            if( floatval($WC_regular_price) == floatval($price)){
                // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCRegularPrice returned true");
                return true;
            }
        }
        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCRegularPrice returned false");
        return false;
    }

    public function isWCSalePrice($price='', $_product=''){
        // TODO: ignore if blank
        // if(WP_DEBUG and PRICE_DEBUG) error_log("Called isWCSalePrice with price: $price S:".serialize($_product->get_sku()));
        global $Lasercommerce_Tier_Tree;
        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );        
        if($_product and isset($postID)){
            $WC_sale_price = get_post_meta($postID, '_sale_price', True);
            // if(WP_DEBUG and PRICE_DEBUG) error_log("-> sale price (".$postID."): ".$WC_sale_price);
            if( floatval($WC_sale_price) == floatval($price)){
                // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCSalePrice returned true");
                return true;
            }
        }
        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> isWCSalePrice returned false");
        return false;
    }

    public function maybeGetVariationPricing( $_product, $min_or_max ){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetVariationPricing:$min_or_max | S:".serialize($_product->get_sku())." r:".serialize($this->getCurrentUserRoles()));        
        
        $meta_key = ($min_or_max == 'max' ? 'max_price_variation_id' : 'min_price_variation_id');
        $target_id = $_product->$meta_key;
        if(!$target_id){
            $_product->variable_product_sync();
            $target_id = $_product->$meta_key;
        }

        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> creating target with id: ".$target_id);
        $target = wc_get_product($target_id);

        if($target){
            $value = $this->maybeGetLowestPricing($target);
        } else {
            $value = null;
        }

        // if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetVariationPrice:$min_or_max returned ".(string)($value));
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
        if(WP_DEBUG and PRICE_DEBUG) error_log("Called maybeGetStarPrice:$star | p: $price I: $postID S:".(string)($_product->get_sku())." r:".serialize($this->getCurrentUserRoles()));
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
                    // if(WP_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'regular': 
                    $price = $lowestPricing->regular_price;
                    // if(WP_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");
                    break;
                case 'sale': 
                    $price = $lowestPricing->sale_price;
                    // if(WP_DEBUG and PRICE_DEBUG) error_log("-> changing price to $price");            
                    break;                    
                // default:
                //     # code...
                //     break;
            }
        }
        if(WP_DEBUG and PRICE_DEBUG) error_log("-> maybeGetStarPrice:$star returned $price");
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
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetPriceInclTax with price: ".$price);
        return $this->maybeGetStarPrice('', $price, $_product);
    }

    public function maybeGetPriceExclTax($price ='', $qty, $_product){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nCalled maybeGetPriceExclTax with price: ".$price);
        return $this->maybeGetStarPrice('', $price, $_product);
    }

    public function maybeGetCartItemPrice( $price, $values, $cart_item_key ){
        if (WP_DEBUG and PRICE_DEBUG) {
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
        if (WP_DEBUG and PRICE_DEBUG) {
            error_log("Called maybeGetCartItemSubtotal");
            error_log(" | subtotal: ".serialize($subtotal));
            error_log(" | values: ".serialize($values));
            error_log(" | cart_item_key: ".serialize($cart_item_key));
        }
        return $subtotal;
    }

    public function maybeIsPurchasable($purchasable, $_product){
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nmaybeIsPurchasable closure called | p:".(string)$purchasable." S:".(string)$_product->get_sku());
        if($_product && $_product->is_type('variable')){
            // if(WP_DEBUG and PRICE_DEBUG) error_log("is variable");
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
        // if(WP_DEBUG and PRICE_DEBUG) error_log("\nmaybeIsPurchasable closure returned: ".(string)$purchasable);
        return $purchasable;
    }



/**
 * Functions for tricking product bundles into thinking products have prices using a clever ruse
 */

    public function constructCleverRuse($product_id, $new_price){
        $old_price = get_post_meta($product_id, '_price', True);
        $old_prices = get_post_meta($product_id, 'prices_old', True);
        if($old_prices) {
            $old_prices = $old_prices . '|' . $old_price ;
        } else{
            $old_prices = $old_price;
        }
        // error_log("Called constructCleverRuse callback| product_id: $product_id");
        // error_log("-> constructCleverRuse old price: ". $old_price);
        // error_log("-> constructCleverRuse new price: ". $new_price);
        // error_log("-> constructCleverRuse old prices: ". $old_prices);
        update_post_meta($product_id, 'prices_old', $old_prices);
        update_post_meta($product_id, '_price', $new_price);
    }

    public function destructCleverRuse($product_id){
        // error_log("Called destructCleverRuse callback | product_id: $product_id");
        $old_prices = explode('|',get_post_meta($product_id, 'prices_old', True));
        // error_log("-> destructCleverRuse old prices: ". serialize($old_prices));  
        $old_price = array_pop($old_prices);
        // error_log("-> destructCleverRuse old price: ". $old_price);  
        $old_prices = implode('|', $old_prices);
        // error_log("-> destructCleverRuse old prices: ". serialize($old_prices));  
        $new_price = get_post_meta($product_id, '_price', True);
        // error_log("-> destructCleverRuse new price: ". $new_price); 
        update_post_meta($product_id, 'prices_old', $old_prices); 
        update_post_meta($product_id, '_price', $old_price);
    }

    public function maybeAvailableVariationPreBundle($variation_data, $_product, $_variation ){
        global $Lasercommerce_Tier_Tree;
        $variation_id = $Lasercommerce_Tier_Tree->getPostID( $_variation );

        $this->constructCleverRuse($variation_id, $this->maybeGetPrice($_variation->price, $_variation));
        return $variation_data;
    }

    public function maybeAvailableVariationPostBundle($variation_data, $_product, $_variation ){
        global $Lasercommerce_Tier_Tree;
        $variation_id = $Lasercommerce_Tier_Tree->getPostID( $_variation );

        $this->destructCleverRuse($variation_id);
        return $variation_data;
    }

    public function WooBundlesGetVariations( $product_id ){
        // error_log("Called WooBundlesGetVariations"); 
        // error_log(" | product_id: $product_id");

        $variations = array();

        $terms        = get_the_terms( $product_id, 'product_type' );
        $product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
        if ( $product_type === 'bundle' ) {
            if(class_exists('WC_PB_Core_Compatibility')){
                $product = WC_PB_Core_Compatibility::wc_get_product( $product_id );
                if ( ! $product ) {
                    return $variations;
                }

                $bundled_items = $product->get_bundled_items();

                if ( ! $bundled_items ) {
                    return $add;
                }        
                
                foreach ($bundled_items as $bundled_item_id => $bundled_item) {
                    $id                   = $bundled_item->product_id;
                    $bundled_product_type = $bundled_item->product->product_type;
                    // error_log(" | processing bundled item id: $id");

                    if($bundled_product_type == 'variable'){
                        $allowed_variations = $bundled_item->get_product_variations();
                        // error_log("  | allowed_variations: ".serialize($allowed_variations));
                        if($allowed_variations){
                            foreach ($allowed_variations as $variation) {
                                if(isset($variation['variation_id'])){
                                    $variations[] = $variation['variation_id'];
                                }
                            }
                        }
                    }
                }        
            } 
        }
        // error_log(" | returning ".serialize($variations));
        return $variations;
    }

    public function PreWooBundlesValidation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
        // error_log("called PreWooBundlesValidation callback");
        // error_log(" | add: $add");
        // error_log(" | product_id: $product_id");
        $variations = $this->WooBundlesGetVariations( $product_id );
        // error_log(" | variations: ".serialize($variations));

        foreach ($variations as $variation_id) {
            $this->constructCleverRuse($variation_id, 'X');
        }

        return $add;
    }

    public function PostWooBundlesValidation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
        // error_log("called PostWooBundlesValidation callback | add: $add");
        // error_log(" | add: $add");
        // error_log(" | product_id: $product_id");
        $variations = $this->WooBundlesGetVariations( $product_id );
        // error_log(" | variations: ".serialize($variations));

        foreach ($variations as $variation_id) {
            $this->destructCleverRuse($variation_id);
        }

        return $add;
    }

    public function overrideBundledPrices(){
        // error_log("called override_bundled_prices callback");
        // error_log(" | add: $add");
        // error_log(" | product_id: $product_id");
        $variations = $this->WooBundlesGetVariations( $product_id );
        // error_log(" | variations: ".serialize($variations));        
    }


/**
 * Functions for filtering HTML output
 */


    public function maybeGetStarHtml($price_html, $_product, $star){
        $user = wp_get_current_user();

        if(WP_DEBUG and HTML_DEBUG) error_log("called maybeGetStarHtml:$star");
        if(WP_DEBUG and HTML_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG and HTML_DEBUG) error_log("-> price: ".$_product->price);
        if(WP_DEBUG and HTML_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(WP_DEBUG and HTML_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(WP_DEBUG and HTML_DEBUG) error_log("-> product: ".$_product->id);
        if(WP_DEBUG and HTML_DEBUG) error_log("-> user: ".$user->ID);
        
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

    public function maybeAddPricingTab( $tabs ){
        if(WP_DEBUG) error_log("\ncalled maybeAddPricingTab");

        global $Lasercommerce_Tier_Tree;
        global $product;
        global $Lasercommerce_Roles_Override;

        if(!isset($product)){
            if(WP_DEBUG) error_log("-> product global not set");
            return $tabs;
        }

        $postID = $Lasercommerce_Tier_Tree->getPostID( $product );
        if(!isset($postID)){
            if(WP_DEBUG) error_log("-> no postID");
            return $tabs;
        }

        $roles = $this->getCurrentUserRoles();
        $availableTiers = $Lasercommerce_Tier_Tree->getAvailableTiers($roles);
        foreach ($availableTiers as $role) {
            $old_override = $Lasercommerce_Roles_Override;
            $Lasercommerce_Roles_Override = array($role);
            $price = $product->get_price_html();
            if($price) $visiblePrices[$role] = $price;
            $Lasercommerce_Roles_Override = $old_override;
        }

        if(isset($visiblePrices) and sizeof($visiblePrices) > 1){
            wp_register_style( 'pricing_table-css', plugins_url('/css/pricing_table.css', __FILE__));
            wp_enqueue_style( 'pricing_table-css' );

            global $wp_roles;
            if( isset($wp_roles) ){
                $names = $wp_roles->get_names();            
            } else {
                $names = array();
            }

            $tabs['Pricing'] = array(
                'title' => __('Pricing', 'Lasercommerce'),
                'priority' => 50,
                'callback' => function() use ($visiblePrices, $names) { 
                    ?>
<table class='shop_table lasercommerce pricing_table'>
    <thead>
        <tr>
            <td>
                <?php _e('Tier', 'Lasercommerce'); ?>
            </td>
            <td>
                <?php _e('Price', 'Lasercommerce'); ?>
            </td>
        </tr>
    </thead>
    <?php foreach($visiblePrices as $tier => $price) { ?>
    <tr>
        <td>
            <?php echo isset($names[$tier])?$names[$tier]:$tier; ?>
        </td>
        <td>
            <?php echo $price; ?>
        </td>
    </tr>
    <?php } ?>
</table>
                    <?php
                }
            );
        } else {
            // if(WP_DEBUG) error_log("-> visibleTiers is empty");
            return $tabs;
        }

        // if(WP_DEBUG) error_log("-> returning tabs");
        return $tabs;
    }

    public function maybeAddDynamicPricingTabs( $tabs ){
        if(WP_DEBUG) error_log("\ncalled maybeAddDynamicPricingTabs");

        global $Lasercommerce_Tier_Tree;
        global $product;

        if(!isset($product)){
            if(WP_DEBUG) error_log("-> product global not set");
            return $tabs;
        }

        $postID = $Lasercommerce_Tier_Tree->getPostID( $product );
        if(!isset($postID)){
            if(WP_DEBUG) error_log("-> no postID");
            return $tabs;
        }

        $DPRC_Table = get_post_meta($postID, 'DPRC_Table', True);
        $DPRP_Table = get_post_meta($postID, 'DPRP_Table', True);

        if(WP_DEBUG and HTML_DEBUG) error_log("DPRC_Table: ".serialize($DPRC_Table));
        if(WP_DEBUG and HTML_DEBUG) error_log("DPRP_Table: ".serialize($DPRP_Table));

        if( $DPRC_Table != "" or $DPRP_Table != "" ){
            $tabs['dynamic_pricing'] = array(
                'title' => __('Dynamic Pricing', 'LaserCommerce'),
                'priority' => 50,
                'callback' => function() use ($DPRC_Table, $DPRP_Table) {
                    if( $DPRC_Table != "" ){
                        echo "<h2>" . __('Category Pricing Rules') . "</h2>";
                        echo $DPRC_Table;
                    }
                    if( $DPRC_Table != ""){
                        echo "<h2>" . __('Product Pricing Rules') . "</h2>";
                        echo $DPRP_Table;
                    }
                }
            );
        }

        return $tabs;

    }

    public function maybeAddExtraPricingColumns(){
        $prefix = $this->getOptionNamePrefix();
        global $Lasercommerce_Tier_Tree;
        $roles = $Lasercommerce_Tier_Tree->getMajorTiers();
        add_filter( 
            'manage_edit-product_columns', 
            function($columns) use ($prefix, $roles){
                // if(WP_DEBUG) error_log("called maybeAddExtraPricingColumns");
                // if(WP_DEBUG) foreach ($columns as $key => $value) {
                //     error_log("$key => $value");
                // }
                global $Lasercommerce_Tier_Tree;
                $names = $Lasercommerce_Tier_Tree->getNames();
                
                $new_cols = array();
                foreach ($roles as $role) { 
                    $new_cols[$prefix.$role] = isset($names[$role])?$names[$role]:$role;
                }
                $pos = array_search('price', array_keys($columns)) + 1;
                return array_slice($columns, 0, $pos) + $new_cols + array_slice($columns, $pos);
            },
            99
        );
        add_action(
            'manage_product_posts_custom_column', 
            function( $column ) use ($prefix, $roles){
                global $post;

                if ( empty( $the_product ) || $the_product->id != $post->ID ) {
                    $the_product = wc_get_product( $post );
                } 

                global $Lasercommerce_Tier_Tree;
                // $roles = $Lasercommerce_Tier_Tree->getMajorTiers();

                if( substr($column, 0, strlen($prefix)) === $prefix ){
                    $remainder = substr($column, strlen($prefix));
                    if(in_array($remainder, $roles)){
                        global $Lasercommerce_Roles_Override;
                        $Lasercommerce_Roles_Override = array($remainder);
                        echo $the_product->get_price_html();
                        unset($GLOBALS['Lasercommerce_Roles_Override']);
                    } else {
                        echo '<span class="na">&ndash;</span>';
                    }
                }
            }
        );
    }

    public function maybeAddBulkEditOptions(){
        global $Lasercommerce_Tier_Tree;
        $roles = $Lasercommerce_Tier_Tree->getMajorTiers();
        add_action(
            'woocommerce_product_bulk_edit_end',
            function() use ($roles) {
                global $Lasercommerce_Tier_Tree;
                $names = $Lasercommerce_Tier_Tree->getNames();

                foreach ($roles as $role) {
                    $regular_price_id = $role."_regular_price";
                    $sale_price_id = $role."_sale_price";
                    $role_name = isset($names[$role])?$names[$role]:$role;
                    ?>
<div class="inline-edit-group">
    <label class="alignleft">
        <span class="title"><?php echo $role_name;?> <?php _e( 'Price', 'woocommerce' ); ?></span>
        <span class="input-text-wrap">
            <select class="change_regular_price change_to change_<?php echo $regular_price_id;?>" name="change_<?php echo $regular_price_id;?>">
            <?php
                $options = array(
                    ''  => __( '— No Change —', 'woocommerce' ),
                    '1' => __( 'Change to:', 'woocommerce' ),
                    '2' => __( 'Increase by (fixed amount or %):', 'woocommerce' ),
                    '3' => __( 'Decrease by (fixed amount or %):', 'woocommerce' )
                );
                foreach ($options as $key => $value) {
                    echo '<option value="' . esc_attr( $key ) . '">' . $value . '</option>';
                }
            ?>
            </select>
        </span>
    </label>
    <label class="change-input">
        <input type="text" name="_<?php echo $regular_price_id;?>" class="text regular_price <?php echo $regular_price_id;?>" placeholder="<?php echo sprintf( __( 'Enter price (%s)', 'woocommerce' ), get_woocommerce_currency_symbol() ); ?>" value="" />
    </label>
</div>

<div class="inline-edit-group">
    <label class="alignleft">
        <span class="title"><?php echo $role_name;?> <?php _e( 'Sale', 'woocommerce' ); ?></span>
        <span class="input-text-wrap">
            <select class="change_sale_price change_to change_<?php echo $sale_price_id;?>" name="change_<?php echo $sale_price_id;?>">
            <?php
                $options = array(
                    ''  => __( '— No Change —', 'woocommerce' ),
                    '1' => __( 'Change to:', 'woocommerce' ),
                    '2' => __( 'Increase by (fixed amount or %):', 'woocommerce' ),
                    '3' => __( 'Decrease by (fixed amount or %):', 'woocommerce' ),
                    '4' => __( 'Decrease regular price by (fixed amount or %):', 'woocommerce' )
                );
                foreach ( $options as $key => $value ) {
                    echo '<option value="' . esc_attr( $key ) . '">' . $value . '</option>';
                }
            ?>
            </select>
        </span>
    </label>
    <label class="change-input">
        <input type="text" name="_<?php echo $sale_price_id;?>" class="text sale_price <?php echo $sale_price_id;?>" placeholder="<?php echo sprintf( __( 'Enter sale price (%s)', 'woocommerce' ), get_woocommerce_currency_symbol() ); ?>" value="" />
    </label>
</div>
                    <?php                    
                }
            }
        );
    }

    public function lasercommerce_loop_prices(){
        global $product;
        error_log("called lasercommerce_loop_prices");

        $visiblePrices = $this->maybeGetVisiblePricing($product);
        error_log(" -> visiblePrices: ".serialize($visiblePrices));
        $majorTiers = $this->getMajorTiers();
        error_log(" -> majorTiers: ".serialize($majorTiers));

        $majorPrices = array();
        foreach ($majorTiers as $tier) {
            if (isset($majorPrices[$tier])){
                $majorPrices[$tier] = $majorPrices[$tier];
            }
        }
        error_log(" -> majorPrices: ".serialize($majorPrices));

        foreach($majorPrices as $tier => $price) {
            if($price_html = $product->get_price_html($price)) { ?>
                <span class="price">
                    <?php 
                        echo $price_html; 
                        echo $tier;
                    ?>
                </span>
            <?php }
        }
    }

    public function product_admin_scripts($hook){
        // error_log("hello");
        $screen  = get_current_screen();
        // If(WP_DEBUG) error_log("admin_enqueue_scripts sees screen: $screen->id");

        if( in_array($screen->id, array('product'))){
            wp_register_script( 
                'jquery-date-picker-field-extra-js', 
                plugins_url('/js/jquery.date-picker-field-extra.js', __FILE__), 
                array('jquery', 'wc-admin-meta-boxes' ),
                0.1
            );
            wp_enqueue_script( 'jquery-date-picker-field-extra-js' );
        }        
    }


    public function addVariableProductBulkEditActions(){
        //todo: this
    }
    
    public function variationIsVisible(){
        //todo: this
    }
    
    public function addActionsAndFilters() {
        // Admin filters:

        if(WP_DEBUG) error_log("\n\n\n\n\n\n\nCalled addActionsAndFilters");

        add_action( 'admin_enqueue_scripts', array( &$this, 'product_admin_scripts') );
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        //helper class for tier tree functions    
        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( );
        }   
        $this->maybeAddSaveTierFields( $Lasercommerce_Tier_Tree->getRoles(), $Lasercommerce_Tier_Tree->getNames() );

        
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

        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddPricingTab'));
        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddDynamicPricingTabs'));

        //price display
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price');
        add_action('woocommerce_after_shop_loop_item_title', array(&$this, 'lasercommerce_loop_prices'), 10, 0);
        
        add_action('woocommerce_variable_product_sync', array(&$this, 'maybeVariableProductSync'), 0, 2);

        add_filter( 'woocommerce_available_variation', array(&$this, 'maybeAvailableVariationPreBundle'), 9, 3);
        add_filter( 'woocommerce_available_variation', array(&$this, 'maybeAvailableVariationPostBundle'), 11, 3);

        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'PreWooBundlesValidation' ), 9, 6 );
        // add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'woo_bundles_validation' ), 10, 6 );
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'PostWooBundlesValidation' ), 11, 6 );


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
        
        $this->maybeAddExtraPricingColumns();

        // $this->maybeAddBulkEditOptions();

        //TODO: Make modifications to variable product bulk edit
        add_action( 
            'woocommerce_variable_product_bulk_edit_actions', 
            array(&$this, 'addVariableProductBulkEditActions')
        );
            
        
        

    }


}
