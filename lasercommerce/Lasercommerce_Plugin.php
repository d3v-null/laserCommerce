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
     * asd
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
    
    public function addPriceField($role, $tierName = ""){
        $role = sanitize_key($role);
        if( $tierName == "" ) $tierName = $role;
        $prefix = $this->getOptionNamePrefix(); 
        add_action( 
            'woocommerce_product_options_pricing',  
            function() use ($role, $tierName, $prefix){
                woocommerce_wp_text_input( 
                    array( 
                        'id' => $prefix.$role."_price", 
                        'label' => __( "$tierName Price", 'lasercommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')', 
                        'data_type' => 'price' 
                    ) 
                );    
            }
        );
        //TODO: other product types
    }
    
    public function savePriceField($role, $name = ""){
        $role = sanitize_key($role);
        if( $name == "" ) $name = $role;
        $prefix = $this->getOptionNamePrefix(); 
        add_action( 
            'woocommerce_process_product_meta_simple',
            function($post_id) use ($role, $name, $prefix){
                if(WP_DEBUG) error_log("filtered woocommerce_process_product_meta_simple. role: $role, tiername: $name, prefix: $prefix");
                $price =  "";
                if(isset($_POST[$prefix.$role."_price"])){                
                    $price =  wc_format_decimal($_POST[$prefix . $role . "_price"]);
                }
                update_post_meta( 
                    $post_id, 
                    $prefix.$role."_price",
                    $price
                );
            }
        );
        //TODO: other product types
        //add_action( 'woocommerce_process_product_meta_variable', 
    }
    
    public function addVariablePriceFields($roles=array()){
        //TODO: this
        //TODO: tiers validation
        $prefix = $this->getOptionNamePrefix(); 
        if ($roles) add_action(
            'woocommerce_product_after_variable_attributes',
            function( $loop, $variation_data, $variation) use ($roles, $prefix){
                foreach( $roles as $role ){
                    $var_price = $variation_data[$prefix.$role."_price"];
                    if($var_price) if(WP_DEBUG) error_log("$role: ".$var_price[0]);
                }
            },
            999,
            3
        );
    }
    
    /**adds text fields and form metadata handlers to product data page 
    /* @param $tiers string|array(string|array(string,string))
     */
    public function maybeAddSavePriceFields($roles, $names){
        if(WP_DEBUG) error_log("called maybeAddSavePriceFields on roles: ".serialize($roles).", names: ".serialize($names));
        if(empty($roles)){
            return;
        }
        foreach($roles as $role){
            $name = array_key_exists($role, $names)?$names[$role]:$role;
            $this->addPriceField($role, $name);
            $this->savePriceField($role, $name);
        } 
        $this->addVariablePriceFields($roles);
    }
    
    private function getCurrentUserRoles(){
        $current_user = wp_get_current_user();
        if(WP_DEBUG) error_log("-> current user: ".$current_user->ID);
        $roles = $current_user->roles;
        if(WP_DEBUG) error_log("--> roles: ".serialize($roles));
        return $roles;
    }
    
    public function maybeGetSalePrice($price = '', $_product = ''){ // "" if non-regular user
        if(WP_DEBUG) error_log("called maybeGetSalePrice");
        if(WP_DEBUG) error_log("-> price: $price");
        //todo: check if this is necessary
        if( !isset($_product->id) ){ 
            global $product;
            if ( !isset($product) ){ 
                If(WP_DEBUG) error_log("->! product global not set");                
                return $price;
            }
            $_product = $product;
        }    
        if(WP_DEBUG) error_log("-> productID: $_product->id");
        
        global $Lasercommerce_Tier_Tree;
        
        $visibleTiers = array();
        if( $_product->is_type( 'simple' ) ){
            $visibleTiers = $Lasercommerce_Tier_Tree->getVisibleTiersSimple(
                $_product->id, 
                $this->getCurrentUserRoles()
            );
        } else {
            If(WP_DEBUG) error_log("-> !!!!!!!!!!!!!!!! type is variable!");
        }
        if( empty($visibleTiers) ) {
            return $price;
        } else {
            return '';
        }
    }
    
    public function maybeGetPrice($price = '', $_product = ''){ 
        if(WP_DEBUG) error_log('');
        if(WP_DEBUG) error_log("called maybeGetPrice");
        if(WP_DEBUG) error_log("-> price: $price");
        //todo: make this read off settings, minimum price OR lowest available price
        //todo: extend to variable
        
        if( !isset($_product->id) ){ 
            global $product;
            if ( !isset($product) ){ 
                If(WP_DEBUG) error_log("->! product global not set");
                return $price;
            }
            $_product = $product;
        }    
        
        global $Lasercommerce_Tier_Tree;

        $postID = $Lasercommerce_Tier_Tree->getPostID( $_product );
        if(!$postID){
            return $price;
        }

        $visibleTiers = $Lasercommerce_Tier_Tree->getVisibleTiersSimple(
            $postID,
            $this->getCurrentUserRoles()
        );
        if( empty($visibleTiers) ) return $price;

        asort( $visibleTiers );
        $lowest = array_values($visibleTiers);//$lowest = array_values($visibleTiers)[0];
        if(WP_DEBUG) error_log("-> returned price: ".serialize($lowest));
        return $lowest[0];
    }
    
    public function maybeGetPriceHtml($price_html, $_product){
        if(WP_DEBUG) error_log('');
        if(WP_DEBUG) error_log("called maybeGetPriceHtml");
        if(WP_DEBUG) error_log("-> html: $price_html");
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        
        return $price_html;
    }
    
    public function maybeGetCartPrice($price, $_product){
        if(WP_DEBUG) error_log('');
        if(WP_DEBUG) error_log("called maybeGetCartPrice");
        if(WP_DEBUG) error_log("-> price: ".serialize($price));
        if(WP_DEBUG) error_log("-> product: ".$_product->id);
        
        return $price;    
    }
    
    public function maybeAddPricingTab( $tabs ){
        if(WP_DEBUG) error_log("\n\n\n\n");
        if(WP_DEBUG) error_log("called maybeAddPricingTab");

        $current_user = wp_get_current_user();
        $roles = $current_user->roles;  
        if(empty($roles)){
            if(WP_DEBUG) error_log("-> roles are empty");
            return $tabs;
        }

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

        $visibleTiers = $Lasercommerce_Tier_Tree->getVisibleTiersSimple(
            $postID,
            $roles
        );


        if(!empty($visibleTiers)){
            wp_register_style( 'pricing_table-css', plugins_url('/css/pricing_table.css', __FILE__));
            wp_enqueue_style( 'pricing_table-css' );


            $regular_price = $product->get_regular_price();
            if(WP_DEBUG) error_log("-> reg: $regular_price ");
            if(isset($regular_price)){
                $visibleTiers['customer'] = $regular_price;
            }

            global $wp_roles;
            if( isset($wp_roles) ){
                $names = $wp_roles->get_names();            
            } else {
                $names = array();
            }
            $names['customer'] = 'RRP';
            $names['special_customer'] = 'SSP';

            $tabs['Pricing'] = array(
                'title' => __('Pricing', 'Lasercommerce'),
                'priority' => 50,
                'callback' => function() use ($visibleTiers, $names) { 
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
    <?php foreach($visibleTiers as $tier => $price) { ?>
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
        
        $this->maybeAddSavePriceFields( $Lasercommerce_Tier_Tree->getRoles(), $Lasercommerce_Tier_Tree->getNames() );
        
        //TODO: make modifications to product price display
        // add_filter( 'woocommerce_get_regular_price', array(&$this, 'maybeGetRegularPrice' ) ); - doesn't do anything
        add_filter( 'woocommerce_get_sale_price', array(&$this, 'maybeGetSalePrice'), 999, 2 );
        add_filter( 'woocommerce_get_price', array(&$this, 'maybeGetPrice'), 999, 2 );
        add_filter( 'woocommerce_get_price_html', array(&$this, 'maybeGetPriceHtml'), 999, 2 );
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
        add_filter( 'woocommerce_cart_product_price', array(&$this, 'maybeGetCartPrice'), 999, 2 );
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // add_filter( 'woocommerce_calculate_totals', 
        // 
        
        //add_action( 'admin_head',
        
        // add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddPricingTab'));
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
