<?php


include_once('Lasercommerce_LifeCycle.php');

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
        If(WP_DEBUG) error_log("called activate\n");
        // add_filter( 'woocommerce_get_settings_pages', array($this, 'includeAdminPage') );
        //$this->initOptions();
        // if( is_admin() ){
            // add_filter( 'woocommerce_get_settings_pages', array($this, 'includeAdminPage') );
        // }
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
    
    
    public function addPriceField($tierID, $tierName){
        $tierID = sanitize_key($tierID);
        add_action( 
            'woocommerce_product_options_pricing',  
            function() use ($tierID, $tierName){
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $this->prefix($tierID."_price"), 
                        'label' => __( "$tierName Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'data_type' => 'price' 
                    ) 
                );    
            }
        );
        //TODO: other product types
    }
    
    public function savePriceField($tierID, $tierName = ""){
        if( $tierName == "" ) $tierName = $tierID;
        $tierID = sanitize_key($tierID);
        add_action( 
            'woocommerce_process_product_meta_simple',
            function($post_id) use ($tierID, $tierName){
                $price =  "";
                if(isset($_POST[$this->prefix($tierID."_price")])){                
                    $price =  wc_format_decimal($_POST[$this->prefix($tierID."_price")]);                        
                }
                update_post_meta( 
                    $post_id, 
                    $this->prefix($tierID."_price"),
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
    public function maybeAddSavePriceFields($tiers){
        if(empty($tiers)){
            return;
        }
        if(!is_array($tiers)) {
            $tiers = array($tiers);
        }
        foreach($tiers as $tierID => $tierName){
            $this->addPriceField($tierID, $tierName);
            $this->savePriceField($tierID, $tierName);
        } 
        
    }
    
    public function encodePriceTierTree( $price_tiers ){
        //todo: this
        return json_encode( $price_tiers );
    }
    
    public function decodePriceTierTree( $price_tier_tree ){
        //todo: this
        return json_decode( $price_tier_tree );
    }
    
    public function getPriceTierTree(){
        //todo: this
        
        return array( 
            array(
                'role' => 'special_customer',
                'name' => 'Special',
                'children' => array(
                    'role' => 'wholesale_buyer',
                    'name' => 'Wholesale'
                    'children' => array(
                        array(
                            'role'  => 'mobile_operator',
                            'name'  => 'Mobile Operator'
                        ),
                        array(
                            'role'  => 'gym_owner',
                            'name'  => 'Gym'
                        ),
                        array(
                            'role'  => 'salon',
                            'name'  => 'Salon'
                        ),
                        array(
                            'role'  => 'home_studio',
                            'name'  => 'Home Studio'
                        ),
                        array(
                            'role'  => 'distributor',
                            'name'  => 'Distributor'
                            'children' => array(
                                'role' => 'international_distributor',
                                'name' => 'International Distributor',
                            )
                        )
                    )
                )
            )
        )
    }
    
    public function flattenPriceTierTreeRecursive($node = array()){
        if( !in_array('role', $node) ) return array();
        $names = array(
            $node->role => $node->name
        );
        if( in_array('children', $node) ){
            foreach( $node->children as $child ){
                array_merge($names, flattenPriceTierTreeRecursive($child) );
            }
        }
        return $names;
    }
    
    public function getPriceTierNames(){
        $tree = $this->getPriceTierTree();
        $names = array();
        foreach( $tree as $node ){
            array_merge($names, flattenPriceTierTreeRecursive($tree);
        }
    }        
    
    public function getAvailableTiers(){
        //todo: this
        $priceTierTree = $this->getPriceTierTree();
        if ( is_user_logged_in() ) {
            $currentUser = wp_get_current_user();
            $tiers = [];
            
            
        } else {
            return array();
        }   
    }
        
    
    public function getVisiblePrices( $post_id ){
    }    
    
    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //If(WP_DEBUG) error_log("called addActionsAndFilters\n");
        
        //add_filter( 'lasercommerce_encode_price_tier_tree', array(&$this, 'encodePriceTierTree') );
        //add_filter( 'lasercommerce_decode_price_tier_tree', array(&$this, 'decodePriceTierTree') );
        
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        $this->maybeAddSavePriceFields( array(  
            "special_customer" => "Sale Price",
            "wholesale_buyer" => "Wholesale", 
            "distributor" => "Distributor", 
            "expo_customer" => "Expo",
        ) );
        //TODO: Make modifications to product columns, quick edit: http://www.creativedev.in/2014/01/to-create-custom-field-in-woocommerce-products-admin-panel/
        
        //TODO: Make modifications to bulk edit
        // add_action( 'woocommerce_variable_product_bulk_edit_actions',
        
        //TODO: make modifications to product visibility based on obfuscation condition
        // add_filter('product visibility');
        // add_filter( 'woocommerce_available_variation',
        // add_filter( 'woocommerce_is_purchasable', 
        
        //TODO: make modifications to product price display
        // add_filter( 'woocommerce_get_price' 
        // add_filter( 'woocommerce_get_variation_price'
        // add_filter( 'woocommerce_get_price_html'
        // add_filter( 'woocommerce_variation_price_html', 
        // add_filter( 'woocommerce_variation_sale_price_html',
        // add_filter( 'woocommerce_variable_empty_price_html', 

        //TODO: make modifications to tax
		// add_filter( 'woocommerce_get_cart_tax',  
        // add_filter( 'option_woocommerce_calc_taxes', 
        
        //TODO: make modifications to cart
        // add_filter( 'woocommerce_calculate_totals',         
        
        
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
