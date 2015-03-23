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

include_once('Lasercommerce_LifeCycle.php');
include_once('Lasercommerce_Tier_Tree.php');
include_once('Lasercommerce_Pricing.php');

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
                global $post, $thepostid;
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
    private function getCurrentUserRoles(){
        // if(WP_DEBUG) error_log("called getCurrentUserRoles");
        global $Lasercommerce_Roles_Override;
        if(isset($Lasercommerce_Roles_Override) and is_array($Lasercommerce_Roles_Override)){
            // if(WP_DEBUG) {
            //     // error_log("-> Override is: ");
            //     foreach ($Lasercommerce_Roles_Override as $value) {
            //         // error_log("--> $value");
            //     }
            // }
            $roles = $Lasercommerce_Roles_Override;
        } else {
            $current_user = wp_get_current_user();
            // if(WP_DEBUG) error_log("-> current user: ".$current_user->ID);
            $roles = $current_user->roles;
            // if(WP_DEBUG) error_log("--> roles: ".serialize($roles));
        }
        return $roles;
    }

    private function maybeGetVisiblePricing($_product=''){
        // if(WP_DEBUG) error_log("calledalled maybeGetVisiblePricing");
        if($_product) {
            global $Lasercommerce_Tier_Tree;
            
            $currentUserRoles = $this->getCurrentUserRoles();
            $visibleTiers = $Lasercommerce_Tier_Tree->getAvailableTiers($currentUserRoles);
            // $visibleTiers = $currentUserRoles;
            array_push($visibleTiers, ''); 
            
            $id = isset( $_product->variation_id ) ? $_product->variation_id : $_product->id;

            $pricings = array();
            foreach ($visibleTiers as $role) {
                $pricing = new Lasercommerce_Pricing($id, $role);
                if($pricing->regular_price){
                    $pricings[$role] = $pricing;
                }
            }

            // if(WP_DEBUG) {
            //     error_log("-> maybeGetVisiblePricing returned: ");//.serialize(array_keys($pricings)));
            //     foreach ($pricings as $key => $pricing) {
            //         error_log("$key: ". (string)$pricing);
            //     }
            // }
            
            return $pricings;
        } else { 
            //if(WP_DEBUG) error_log("product not valid");
            return null;
        }   

    }

    private function maybeGetLowestPricing($_product=''){
        // if(WP_DEBUG) error_log("called maybeGetLowestPricing");
        $pricings = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricings)){
            uasort( $pricings, 'Lasercommerce_Pricing::sort_by_regular_price' );
            $pricing = array_pop($pricings);
            // if(WP_DEBUG) error_log("maybeGetLowestPricing returned ".($pricing->__toString()));
            return $pricing;
        } else {
            // if(WP_DEBUG) error_log("maybeGetLowestPricing return null");
            return null;
        }
    }

    /**
     * Gets the regular price of the given simple product, used inw oocommerce_get_regular_price
     * @param mixed $price The regular price as seen by woocommerce core
     * @param mixed $_product The product object
     * @return mixed $price The regular price overridden by this plugin
     */
    public function maybeGetRegularPrice($price = '', $_product=''){
        //TODO: detect if the price to override is woocommerce price
        // if(WP_DEBUG) error_log("maybeGetRegularPrice called with price: $price");
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->regular_price;
        } 
        // if(WP_DEBUG) error_log("maybeGetRegularPrice returned $price");
        return $price;
    }

    public function maybeGetSalePrice($price = '', $_product = ''){ 
        //TODO: detect if the price to override is woocommerce price
        // if(WP_DEBUG) error_log("maybeGetSalePrice called with price: $price");
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->sale_price;
        } 
        // if(WP_DEBUG) error_log("maybeGetSalePrice returned $price");
        return $price;
    }
    
    public function maybeGetPrice($price = '', $_product = ''){ 
        if(WP_DEBUG) error_log("maybeGetPrice p: $price S:".serialize($_product->get_sku())." r:".serialize($this->getCurrentUserRoles()));
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->maybe_get_current_price();
        } 
        // else {
        //     $price = '';
        // }
        if(WP_DEBUG) error_log("maybeGetPrice returned $price");
        return $price;        
    }

    public function maybeGetCartPrice($price = '', $_product = ''){
        // if(WP_DEBUG) error_log("maybeGetCartPrice called with price: $price");
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->maybe_get_current_price();
        } else {
            $price = '';
        }
        //if(WP_DEBUG) error_log("maybeGetCartPrice returned $price");
        return $price;    
    }

    public function maybeGetPriceInclTax($price ='', $qty, $_this){
        // if(WP_DEBUG) error_log("called maybeGetPriceInclTax");
        // if(WP_DEBUG) error_log("price: ".$price);
        return $price;
    }

    public function maybeGetPriceExclTax($price ='', $qty, $_this){
        // if(WP_DEBUG) error_log("called maybeGetPriceExclTax");
        // if(WP_DEBUG) error_log("price: ".$price);
        return $price;
    }

    public function maybeGetVariationPrice( $price, $product, $min_or_max, $display ) {
        // if(WP_DEBUG) error_log("maybeGetVariationPrice:$min_or_max called with price: $price");        
        $min_price = null;
        $max_price = null;
        if(isset($product->children) && !empty($product->children)){
            foreach ($product->children as $child) {
                $variation = $product->get_child($child);
                $discounted_price = $this->maybeGetPrice($price, $variation);
                if($min_price == null || $discounted_price < $min_price){
                    $min_price = $discounted_price;
                }
                if($max_price == null || $discounted_price > $max_price){
                    $max_price = $discounted_price;
                }
            }
        }

        if( $min_or_max == 'min'){
            return $min_price != null ? $min_price : $price;
        } else {
            return $max_price != null ? $max_price : $price;
        }
    }

    public function maybeGetVariationRegularPrice($price, $product, $min_or_max, $display) {
        // if(WP_DEBUG) error_log("maybeGetVariationPrice:$min_or_max called with price: $price");        
        $min_price = null;
        $max_price = null;
        if(isset($product->children) && !empty($product->children)){
            foreach ($product->children as $child) {
                $variation = $product->get_child($child);
                $discounted_price = $this->maybeGetRegularPrice($price, $variation);
                if($min_price == null || $discounted_price < $min_price){
                    $min_price = $discounted_price;
                }
                if($max_price == null || $discounted_price > $max_price){
                    $max_price = $discounted_price;
                }
            }
        }

        if( $min_or_max == 'min'){
            return $min_price != null ? $min_price : $price;
        } else {
            return $max_price != null ? $max_price : $price;
        }
    }

    public function maybeGetVariationSalePrice($price, $product, $min_or_max, $display) {
        // if(WP_DEBUG) error_log("maybeGetVariationPrice:$min_or_max called with price: $price");        
        $min_price = null;
        $max_price = null;
        if(isset($product->children) && !empty($product->children)){
            foreach ($product->children as $child) {
                $variation = $product->get_child($child);
                $discounted_price = $this->maybeGetSalePrice($price, $variation);
                if($min_price == null || $discounted_price < $min_price){
                    $min_price = $discounted_price;
                }
                if($max_price == null || $discounted_price > $max_price){
                    $max_price = $discounted_price;
                }
            }
        }

        if( $min_or_max == 'min'){
            return $min_price != null ? $min_price : $price;
        } else {
            return $max_price != null ? $max_price : $price;
        }
    }

    public function maybeVariableProductSync( $product_id, $children ){
        
    }

    public function maybeIsPurchasable($purchasable, $_product){
        // if(WP_DEBUG) error_log("maybeIsPurchasable closure called | p:$purchasable");
        if($_product && $_product->is_type('variable')){
            // if(WP_DEBUG) error_log("is variable");
            $children = $_product->get_children();
            if( is_array($children) && !empty($children)){
                foreach ($children as $child_id) {
                    $child = $_product->get_child($child_id);
                    if ($child->is_purchasable()){
                        return true;
                    }
                }
            } 
        } 
        return $purchasable;
    }

    public function maybeGetPriceHtml($price_html, $_product){
        $user = wp_get_current_user();
        if(WP_DEBUG) error_log("");
        if(WP_DEBUG) error_log("");
        if(WP_DEBUG) error_log("called maybeGetPriceHtml");
        if(WP_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(WP_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        if(WP_DEBUG) error_log("-> user: ".$user->ID);
        
        return $price_html;
    }    

    public function maybeGetSalePriceHtml($price_html, $_product){
        $user = wp_get_current_user();
        if(WP_DEBUG) error_log("");
        if(WP_DEBUG) error_log("");
        if(WP_DEBUG) error_log("called maybeGetSalePriceHtml");
        if(WP_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(WP_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        if(WP_DEBUG) error_log("-> user: ".$user->ID);
        
        return $price_html;
    }

    public function maybeAddPricingTab( $tabs ){
        // if(WP_DEBUG) error_log("\n\n\n\n");
        // if(WP_DEBUG) error_log("called maybeAddPricingTab");

        global $Lasercommerce_Tier_Tree;
        global $product;
        if(!isset($product)){
            if(WP_DEBUG) error_log("-> product global not set");
        }

        $postID = $Lasercommerce_Tier_Tree->getPostID( $product );
        if(!isset($postID)){
            if(WP_DEBUG) error_log("-> no postID");
            return $tabs;
        }

        $visiblePrices = array();
        $visiblePricing = $this->maybeGetVisiblePricing($product);
        if($visiblePricing){
            foreach ($visiblePricing as $role => $pricing) {
                if($pricing){
                    $regular = $pricing->regular_price;
                    if($regular){
                        $visiblePrices[$role] = $regular;
                    }
                }
            }  
        } else {
            return $tabs;
        }

        if(!empty($visiblePrices)){
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
            <?php echo esc_attr($price); ?>
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

    public function maybeAddExtraPricingColumns(){
        $prefix = $this->getOptionNamePrefix();
        add_filter( 
            'manage_edit-product_columns', 
            function($columns) use ($prefix){
                // if(WP_DEBUG) error_log("called maybeAddExtraPricingColumns");
                // if(WP_DEBUG) foreach ($columns as $key => $value) {
                //     error_log("$key => $value");
                // }
                global $Lasercommerce_Tier_Tree;
                $names = $Lasercommerce_Tier_Tree->getNames();
                $roles = $Lasercommerce_Tier_Tree->getActiveRoles();
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
            function( $column ) use ($prefix){
                global $post;

                if ( empty( $the_product ) || $the_product->id != $post->ID ) {
                    $the_product = wc_get_product( $post );
                } 

                global $Lasercommerce_Tier_Tree;
                $roles = $Lasercommerce_Tier_Tree->getActiveRoles();

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
        add_filter( 'woocommerce_is_purchasable', array(&$this, 'maybeIsPurchasable'), 0, 2);

        add_filter( 'woocommerce_sale_price_html', array(&$this, 'maybeGetSalePriceHtml'), 0, 2);
        add_filter( 'woocommerce_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);     
        add_filter( 'woocommerce_variable_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2 );
        add_filter( 'woocommerce_variation_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2 );
        add_filter( 'woocommerce_variation_sale_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2 );
        add_filter( 'woocommerce_empty_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2 );

        add_filter( 'woocommerce_get_price_including_tax', array(&$this, 'maybeGetPriceInclTax'), 0, 3);
        add_filter( 'woocommerce_get_price_excluding_tax', array(&$this, 'maybeGetPriceExclTax'), 0, 3);

        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddPricingTab'));
        



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

        //TODO: Make modifications to variable product bulk edit
        add_action( 
            'woocommerce_variable_product_bulk_edit_actions', 
            array(&$this, 'addVariableProductBulkEditActions')
        );
            
        
        
        //TODO: make modifications to product visibility based on obfuscation condition
        // add_filter('product visibility');
        // add_filter( 'woocommerce_available_variation',
        // add_filter( 'woocommerce_product_is_visible', 
        // add_filter( 'woocommerce_is_purchasable', 
        

        
        //TODO: make modifications to cart
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // 
        
        //add_action( 'admin_head',
        
        // add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }


}
