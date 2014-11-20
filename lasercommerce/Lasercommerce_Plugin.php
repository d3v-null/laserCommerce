<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Lasercommerce_LifeCycle.php');
include_once('Lasercommerce_Tier_Tree.php');
include_once('Lasercommerce_Price_Spec.php');

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
            // TODO
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
     * TODO: make this work with special prices
     * 
     * @param string $tier_slug The internal name for the price tier (eg. wholesale)
     * @param string $tier_name The human readable name for the price tier (eg. Wholesale)
     */
    private function addTierFields($tier_slug, $tier_name = ""){
        //sanitize tier_slug and $tier_name
        $tier_slug = sanitize_key($tier_slug);
        $tier_name = sanitize_key($tier_name);
        if( $tier_name == "" ) $tier_name = $tier_slug;
        $prefix = $this->getOptionNamePrefix(); 
        
        // The following code was inspred by WooCommerce:
        // https://github.com/woothemes/woocommerce/blob/master/includes/admin/meta-boxes/class-wc-meta-box-product-data.php

        add_action( 
            'woocommerce_product_options_pricing',  
            function() use ($tier_slug, $tier_name, $prefix){
                global $post, $thepostid;
                if( !isset($thepostid) ){
                    $thepostid = $post->ID;
                }

                echo '<div class="options_group show_if_simple">';

                $price_spec = new Lasercommerce_Price_Spec($thepostid);
                $pricing = $price_spec->maybe_get_pricing($tier_slug);

                $regular_price  = (isset($pricing->regular)) ? esc_attr($pricing->regular) : '' ;
                $sale_price     = (isset($pricing->sale)) ? esc_attr($pricing->sale) : '' ;

                // Regular Price
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $prefix.$tier_slug."_regular_price", 
                        'value' => $regular_price,
                        'label' => __( "$tier_name Regular Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'data_type' => 'price' 
                    ) 
                );   
                // Special Price
               woocommerce_wp_text_input( 
                    array( 
                        'id' => $prefix.$tier_slug."_sale_price", 
                        'value' => $sale_price,
                        'label' => __( "$tier_name Sale Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', 'lasercommerce' ) . '</a>',
                        'data_type' => 'price' 
                    ) 
                );

                // Special active
                $sale_from    = ( isset($pricing->sale_from) and $pricing->sale_from ) ? date_i18n( 'Y-m-d', floatval($pricing->sale_from) ) : '';
                $sale_to      = ( isset($pricing->sale_to) and $pricing->sale_to ) ? date_i18n( 'Y-m-d', floatval($pricing->sale_to) ) : '';
                $sale_from_id = $prefix . $tier_slug . '_sale_price_dates_from';
                $sale_to_id   = $prefix . $tier_slug . '_sale_price_dates_to';

                echo '  <p class="form-field sale_price_dates_fields">
                            <label for="'.$sale_from_id.'">' . __( 'Sale Price Dates', 'woocommerce' ) . '</label>
                            <input type="text" class="short" name="'.$sale_from_id.'" id="'.$sale_from_id.'" value="' . esc_attr( $sale_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            <input type="text" class="short" name="'.$sale_to_id.'" id="'.$sale_to_id.'" value="' . esc_attr( $sale_to ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            <a href="#" class="cancel_sale_schedule">'. __( 'Cancel', 'woocommerce' ) .'</a>
                        </p>';
                // TODO: test above javascript is working

                // TODO: output dynamic pricing rules

                echo "</div>";
            }
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
        add_action( 
            'woocommerce_process_product_meta_simple',
            function($post_id) use ($tier_slug, $tier_name, $prefix){
                global $post, $thepostid;
                if( !isset($thepostid) ){
                    $thepostid = $post->ID;
                }

                $price_spec = new Lasercommerce_Price_Spec($thepostid);
                $pricing = $price_spec->maybe_get_pricing($tier_slug);

                $regular_id     = $prefix.$tier_slug."_regular_price";
                $regular_price  = isset($_POST[$regular_id]) ? wc_format_decimal( $_POST[$regular_id] ) : '';
                $pricing->regular = $regular_price;
                
                $sale_id     = $prefix.$tier_slug."_sale_price";
                $sale_price  = isset($_POST[$sale_id]) ? wc_format_decimal( $_POST[$sale_id] ) : '';
                $pricing->sale = $sale_price;

                $sale_from_id   = $prefix.$tier_slug.'_sale_price_dates_from';
                $sale_from      = isset( $_POST[$sale_from_id] ) ? wc_clean( $_POST[$sale_from_id] ) : '';
                $pricing->sale_from = $sale_from;

                $sale_to_id     = $prefix.$tier_slug.'_sale_price_dates_to';
                $sale_to        = isset( $_POST[$sale_to_id] ) ? wc_clean( $_POST[$sale_to_id] ) : '';
                $pricing->sale_to = $sale_to;

                $price_spec->maybe_set_pricing($tier_slug, $pricing);
                $price_spec->save();
            }
        );
        //TODO: other product types
        //add_action( 'woocommerce_process_product_meta_variable', 
    }
    
    /**
     * Adds text fields and form metadata handlers to product data page for given tiers
     * @param array $tiers a list of tier slugs
     * @param array $names A mapping of tier slugs to their names
     */
    public function maybeAddSaveTierFields($tiers, $names = array()){
        if(WP_DEBUG) error_log("Called maybeAddSaveTierFields: ".serialize($tiers));
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
        $current_user = wp_get_current_user();
        if(WP_DEBUG) error_log("-> current user: ".$current_user->ID);
        $roles = $current_user->roles;
        if(WP_DEBUG) error_log("--> roles: ".serialize($roles));
        return $roles;
    }

    private function maybeGetVisiblePricing($_product=''){
        if(WP_DEBUG) error_log("calledalled maybeGetVisiblePricing");
        if($_product) {
            $id = isset( $_product->variation_id ) ? $_product->variation_id : $_product->id;
            $price_spec = new Lasercommerce_Price_Spec($id);
            $pricing = array(
                'default' => $price_spec->maybe_get_default_pricing()
            );
            foreach( $this->getCurrentUserRoles() as $role ){
                $pricing[$role] = $price_spec->maybe_get_pricing($role);
            }
            if(WP_DEBUG) error_log("-> returned ".serialize(array_keys($pricing)));
            return $pricing;
        } else { 
            if(WP_DEBUG) error_log("product not valid");
            return null;
        }   

    }

    private function maybeGetLowestPricing($_product=''){
        if(WP_DEBUG) error_log("called maybeGetLowestPricing");
        $pricing = $this->maybeGetVisiblePricing($_product);

        if(!empty($pricing)){
            uasort( $pricing, 'Lasercommerce_Pricing::sort_by_regular' );
            $pricing = array_shift($pricing);
            if(WP_DEBUG) error_log("returned ".(string)($pricing));
            return $pricing;
        } else {
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
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->regular;
        } 
        if(WP_DEBUG) error_log("maybeGetRegularPrice returned $price");
        return $price;
    }

    public function maybeGetSalePrice($price = '', $_product = ''){ 
        //TODO: detect if the price to override is woocommerce price
        $lowestPricing = $this->maybeGetLowestPricing($_product);
        if($lowestPricing){
            $price = $lowestPricing->sale;
        } 
        if(WP_DEBUG) error_log("maybeGetSalePrice returned $price");
        return $price;
    }
    
    public function maybeGetPrice($price = '', $_product = ''){ 
        $lowestTier = $this->maybeGetLowestPricing($_product);
        if($lowestTier){
            $price = $lowestTier->maybe_get_current_price();
        } else {
            $price = '';
        }
        if(WP_DEBUG) error_log("maybeGetPrice returned $price");
        return $price;        
    }

    public function maybeGetCartPrice($price = '', $_product = ''){
        $lowestTier = $this->maybeGetLowestPricing($_product);
        if($lowestTier){
            $price = $lowestTier->maybe_get_current_price();
        } else {
            $price = '';
        }
        if(WP_DEBUG) error_log("maybeGetCartPrice returned $price");
        return $price;    
    }

    public function maybeGetPriceInclTax($price ='', $qty, $_this){
        if(WP_DEBUG) error_log("called maybeGetPriceInclTax");
        if(WP_DEBUG) error_log("price: ".$price);
        return $price;
    }

    public function maybeGetPriceExclTax($price ='', $qty, $_this){
        if(WP_DEBUG) error_log("called maybeGetPriceExclTax");
        if(WP_DEBUG) error_log("price: ".$price);
        return $price;
    }
    
    public function maybeGetPriceHtml($price_html, $_product){
        if(WP_DEBUG) error_log("called maybeGetPriceHtml");
        if(WP_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(WP_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        
        return $price_html;
    }    

    public function maybeGetSalePriceHtml($price_html, $_product){
        if(WP_DEBUG) error_log("called maybeGetSalePriceHtml");
        if(WP_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG) error_log("-> regular_price: ".$_product->regular_price);
        if(WP_DEBUG) error_log("-> sale_price: ".$_product->sale_price);
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        
        return $price_html;
    }
    
    public function maybeAddPricingTab( $tabs ){
        if(WP_DEBUG) error_log("\n\n\n\n");
        if(WP_DEBUG) error_log("called maybeAddPricingTab");

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

        $visiblePricing = $this->maybeGetVisiblePricing($product);
        if($visiblePricing){
            foreach ($visiblePricing as $role => $pricing) {
                if($pricing){
                    $regular = $pricing->regular;
                    if($regular){
                        $visiblePrices = $regular;
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
            if(WP_DEBUG) error_log("-> visibleTiers is empty");
            return $tabs;
        }

        if(WP_DEBUG) error_log("-> returning tabs");
        return $tabs;
    }

    public function addVariableProductBulkEditActions(){
        //todo: this
    }
    
    public function variationIsVisible(){
        //todo: this
    }
    
    public function addActionsAndFilters() {
        if(WP_DEBUG) error_log("called addActionsAndFilters");
        if(WP_DEBUG) error_log('');
        if(WP_DEBUG) error_log('');
        
        
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        //helper class for tier tree functions    
        global $Lasercommerce_Tier_Tree;
        
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( );
        }   
        
        $this->maybeAddSaveTierFields( $Lasercommerce_Tier_Tree->getRoles(), $Lasercommerce_Tier_Tree->getNames() );

        
        //THE CRUX:
        add_filter( 'woocommerce_get_price', array(&$this, 'maybeGetPrice'), 0, 2 );
        add_filter( 'woocommerce_get_regular_price', array(&$this, 'maybeGetRegularPrice' ), 0, 2 ); 
        add_filter( 'woocommerce_get_sale_price', array(&$this, 'maybeGetSalePrice'), 0, 2 );

        add_filter( 'woocommerce_sale_price_html', array(&$this, 'maybeGetSalePriceHtml'), 0, 2);
        add_filter( 'woocommerce_price_html', array(&$this, 'maybeGetPriceHtml'), 0, 2);     
        add_filter( 'woocommerce_cart_product_price', array(&$this, 'maybeGetCartPrice'), 9, 2 );

        add_filter( 'woocommerce_get_price_including_tax', array(&$this, 'maybeGetPriceInclTax'), 0, 3);
        add_filter( 'woocommerce_get_price_excluding_tax', array(&$this, 'maybeGetPriceExclTax'), 0, 3);

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

        // add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddPricingTab'));
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
