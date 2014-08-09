<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Lasercommerce_LifeCycle.php');
include_once('Lasercommerce_Tier_Tree.php');

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

    public function getPluginDisplayName() {
        return 'LaserCommerce';
    }

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
     * @return void
     */
    public function upgrade() {
    }
    
    public function activate(){ //overrides abstract in parent LifeCycle
        global $Lasercommerce_Plugin;
        if( !isset($Lasercommerce_Plugin) ) {
            $Lasercommerce_Plugin = &$this;
        }

        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( );
        }   
    }
    
    
    public function deactivate(){ //overrides abstract in parent LifeCycle
    }

    //include the lasercommerce admin tab in woocommerce settings
    public function includeAdminPage($settings){
        $pluginDir = plugin_dir_path( __FILE__ );
        include('Lasercommerce_Admin.php');
        $settings[] = new Lasercommerce_Admin($this->getOptionNamePrefix());
        return $settings;
    }
    
    
    public function addPriceField($role, $tierName){
        $role = sanitize_key($role);
        add_action( 
            'woocommerce_product_options_pricing',  
            function() use ($role, $tierName){
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $this->prefix($role."_price"), 
                        'label' => __( "$tierName Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'data_type' => 'price' 
                    ) 
                );    
            }
        );
        //TODO: other product types
    }
    
    public function savePriceField($role, $tierName = ""){
        if( $tierName == "" ) $tierName = $role;
        $role = sanitize_key($role);
        add_action( 
            'woocommerce_process_product_meta_simple',
            function($post_id) use ($role, $tierName){
                $price =  "";
                if(isset($_POST[$this->prefix($role."_price")])){                
                    $price =  wc_format_decimal($_POST[$this->prefix($role."_price")]);
                }
                update_post_meta( 
                    $post_id, 
                    $this->prefix($role."_price"),
                    $price
                );
            }
        );
        //TODO: other product types
        //add_action( 'woocommerce_process_product_meta_variable', 
    }
    
    /**adds text fields and form metadata handlers to product data page 
    /* @param $tiers string|array(string|array(string,string))
     */
    public function maybeAddSavePriceFields($tierNames){
        if(empty($tierNames)){
            return;
        }
        foreach($tierNames as $role => $tierName){
            $this->addPriceField($role, $tierName);
            $this->savePriceField($role, $tierName);
        } 
    }
    
    public function maybeGetSpecialPrice($specialPrice){ // "" if non-regular user
        //todo: check if this is necessary
        If(WP_DEBUG) error_log("called maybeGetSpecialPrice");
        global $Lasercommerce_Tier_Tree;
        $visibleTiers = $Lasercommerce_Tier_Tree->getVisibleTiers();
        if(!empty($visibleTiers)) {
            return ""; 
        } else {
            return $specialPrice;
        }
    }
    
    public function maybeGetRegularPrice($price){ 
        If(WP_DEBUG) error_log("called maybeGetPrice");
        //todo: this
        // minimum of available prices
        global $Lasercommerce_Tier_Tree;
        
        $visibleTiers = $Lasercommerce_Tier_Tree->getVisibleTiers();
        $callBack = function($a, $b){
            //todo: check this
            if( $a[0] == $b[0] ){
                return 0;
            }
            return( $a[0] < $b[0] );
        };
        
        If(WP_DEBUG) error_log( "visibleTiers asorted: ".serialize( uasort( $visibleTiers, $callBack) ) );
        If(WP_DEBUG) error_log( "visibleTiers sorted: ".serialize( usort( $visibleTiers,  $callBack ) ) );
        return $price;
    }
    
    public function addActionsAndFilters() {
        If(WP_DEBUG) error_log("called addActionsAndFilters");
        
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        //helper class for tier tree functions    
        global $Lasercommerce_Tier_Tree;
        
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( );
        }   
        
        $this->maybeAddSavePriceFields( $Lasercommerce_Tier_Tree->getTierNames() );
        
        //TODO: make modifications to product price display
        add_filter( 'woocommerce_get_regular_price', array(&$this, 'maybeGetRegularPrice' ) );
        add_filter( 'woocommerce_get_special_price', array(&$this, 'maybeGetSpecialPrice' ) );
        // add_filter( 'woocommerce_get_price', array($this, 'maybeGetPrice') );
        // add_filter( 'woocommerce_get_variation_price'
        // add_filter( 'woocommerce_get_price_html'
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
        
        //TODO: Make modifications to bulk edit
        // add_action( 'woocommerce_variable_product_bulk_edit_actions',
        
        
        //TODO: make modifications to product visibility based on obfuscation condition
        // add_filter('product visibility');
        // add_filter( 'woocommerce_available_variation',
        // add_filter( 'woocommerce_product_is_visible', 
        // add_filter( 'woocommerce_is_purchasable', 
        

        
        //TODO: make modifications to cart
        // add_filter( 'woocommerce_calculate_totals',         
        
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
