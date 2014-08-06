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

    //include the admin page in woocommerce settings
    public function includeAdminPage($settings){
        $pluginDir = plugin_dir_path( __FILE__ );
        include('Lasercommerce_Admin.php');
        $settings[] = new Lasercommerce_Admin($this->getOptionNamePrefix());
        return $settings;
    }
    
    
    //tester:
    // public function logProductDataTabs($tabs){ //delete
        // If(WP_DEBUG) error_log('entering LogProductTabs');
        // foreach( $tabs as $k => $v ){
            // If(WP_DEBUG) error_log('key: $k, value: '.serialize($v));
        // }
        // return $tabs;
    // }
    
    // public function woocommerce_general_product_data_test_custom_field(){
        // global $woocommerce, $post;
        // echo '<div class="options_group pricing show_if_simple show_if_external">';
        // woocommerce_wp_checkbox(
            // array(
                // 'id' => 'product_checkbox',
                // 'wrapper_class' => 'checkbox_class',
                // 'label' => __('Checkbox for product', 'woocommerce'),
                // 'description' => __('Description about checkbox', 'woocommerce' )
            // )
        // );
        // echo '</div>';
    // }
    
    // public function maybeAddPriceFields($product_data_tabs, $tiers) {
        // if(!empty($tiers) && !empty($product_data_tabs)){
            
            
            // if(is_array($tiers)){
                // foreach( $tiers as $tier ){
                    
                
    // }
    
    public function addPriceField($tier){
        //TODO: $tier error checking
        add_action( 
            'woocommerce_product_options_pricing',  
            function() use ($tier){
                woocommerce_wp_text_input( 
                    array( 
                        'id' => "laserphile_$tier"."_price", 
                        'label' => __( "$tier Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 'data_type' => 'price' 
                    ) 
                );    
            }
        );
    }
    
    public function maybeAddPriceFields($tiers){
        if(empty($tiers)) return;
        
        if(is_array($tiers)){
            foreach($tiers as $tier){
                $this->addPriceField($tier);
            }
        } else {
            $this->addPriceField($tier);
        }
    }
    
    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        If(WP_DEBUG) error_log("called addActionsAndFilters\n");
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );        
        
        //TODO: Make modifications to product admin
        $this->maybeAddPriceFields( array(  "wholesale_buyer", "distributor", "expo customer" ) );
        //$this->maybeAddPriceFields( explode(', ', $this->getOption('price_tiers')) );
        
        
        //add_filter( 'woocommerce_product_data_tabs', 
        // add_filter( 'woocommerce_product_data_tabs', array(&$this, 'logProductDataTabs'), 999, 1 );
        // add_action( 'woocommerce_product_options_general_product_data', array(&$this, 'woocommerce_general_product_data_test_custom_field' ) );
        
        
        //$product_admin_page = ?;
        //$product_admin_price_section = ?;
        
        // for( explode(', ', $this->getOption('price_tiers')) as $tier){
            
            // //add_settings_field(???);
        // }
        
        //add_filter('product visibility');
        
        //TODO: register product price hooks
        //TODO: register cart hooks
        
        
        
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
