<?php

include_once('Lasercommerce_LifeCycle.php');

/**
* 
*/
class Lasercommerce_UI_Extensions extends Lasercommerce_LifeCycle
{
    
    /**
     * include the lasercommerce admin tab in woocommerce settings
     * 
     * @param array $settings An array specifying the settings to display in the admin page
     * @return array $settings 
     */
    public function includeAdminPage($settings){
        $pluginDir = plugin_dir_path( __FILE__ );
        include_once(LASERCOMMECE_BASE.'/lib/Lasercommerce_Admin.php');
        $settings[] = new Lasercommerce_Admin($this);
        return $settings;
    }    

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
                // if(LASERCOMMERCE_DEBUG) error_log("product options admin for $thepostid, $tier_slug");
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
                if(LASERCOMMERCE_DEBUG) error_log("called woocommerce_product_after_variable_attributes closure");

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
            // if(LASERCOMMERCE_DEBUG) error_log("calling process_product_meta_simple callback");

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
                if(LASERCOMMERCE_DEBUG) {
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
                if(LASERCOMMERCE_DEBUG) {
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
        //if(LASERCOMMERCE_DEBUG) error_log("Called maybeAddSaveTierFields: ".serialize($tiers));

        foreach($tiers as $tier_slug){
            $tier_name = isset($names[$tier_slug])?$names[$tier_slug]:$tier_slug;
            $this->addTierFields($tier_slug, $tier_name);
            $this->saveTierFields($tier_slug, $tier_name);
        } 
    }

    public function maybeAddPricingTab( $tabs ){
        if(LASERCOMMERCE_DEBUG) error_log("\ncalled maybeAddPricingTab");

        global $Lasercommerce_Tier_Tree;
        global $product;
        global $Lasercommerce_Roles_Override;

        if(!isset($product)){
            if(LASERCOMMERCE_DEBUG) error_log("-> product global not set");
            return $tabs;
        }

        $postID = $Lasercommerce_Tier_Tree->getPostID( $product );
        if(!isset($postID)){
            if(LASERCOMMERCE_DEBUG) error_log("-> no postID");
            return $tabs;
        }

        $availableTiers = $Lasercommerce_Tier_Tree->getAvailableTiers();
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
            // if(LASERCOMMERCE_DEBUG) error_log("-> visibleTiers is empty");
            return $tabs;
        }

        // if(LASERCOMMERCE_DEBUG) error_log("-> returning tabs");
        return $tabs;
    }

    public function maybeAddDynamicPricingTabs( $tabs ){
        if(LASERCOMMERCE_DEBUG) error_log("\ncalled maybeAddDynamicPricingTabs");

        global $Lasercommerce_Tier_Tree;
        global $product;

        if(!isset($product)){
            if(LASERCOMMERCE_DEBUG) error_log("-> product global not set");
            return $tabs;
        }

        $postID = $Lasercommerce_Tier_Tree->getPostID( $product );
        if(!isset($postID)){
            if(LASERCOMMERCE_DEBUG) error_log("-> no postID");
            return $tabs;
        }

        $DPRC_Table = get_post_meta($postID, 'DPRC_Table', True);
        $DPRP_Table = get_post_meta($postID, 'DPRP_Table', True);

        if(LASERCOMMERCE_HTML_DEBUG) error_log("DPRC_Table: ".serialize($DPRC_Table));
        if(LASERCOMMERCE_HTML_DEBUG) error_log("DPRP_Table: ".serialize($DPRP_Table));

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
                // if(LASERCOMMERCE_DEBUG) error_log("called maybeAddExtraPricingColumns");
                // if(LASERCOMMERCE_DEBUG) foreach ($columns as $key => $value) {
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

    public function product_admin_scripts($hook){
        // error_log("hello");
        $screen  = get_current_screen();
        // If(LASERCOMMERCE_DEBUG) error_log("admin_enqueue_scripts sees screen: $screen->id");

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

    public function lasercommerce_loop_prices(){
        $_procedure = "LASERCOMMERCE_LOOPPRICES: ";

        global $product;
        
        $visiblePrices = $this->maybeGetVisiblePricing($product);
        $majorTiers = $this->getMajorTiers();
        $majorPrices = array();
        foreach ($majorTiers as $tier) {
            if (isset($majorPrices[$tier])){
                $majorPrices[$tier] = $majorPrices[$tier];
            }
        }

        if(LASERCOMMERCE_DEBUG){
            error_log($_procedure."visiblePrices: ".serialize($visiblePrices));
            error_log($_procedure."majorTiers: ".serialize($majorTiers));
            error_log($_procedure."majorPrices: ".serialize($majorPrices));
        }

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

    public function addActionsAndFilters() {
        if(LASERCOMMERCE_DEBUG) error_log("LASERCOMMERCE_UIE: Called addActionsAndFilters");

        add_action( 'admin_enqueue_scripts', array( &$this, 'product_admin_scripts') );
        add_filter( 'woocommerce_get_settings_pages', array(&$this, 'includeAdminPage') );              

        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddPricingTab'));
        add_filter('woocommerce_product_tabs', array(&$this, 'maybeAddDynamicPricingTabs'));

        //price display
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price');
        add_action('woocommerce_after_shop_loop_item_title', array(&$this, 'lasercommerce_loop_prices'), 10, 0);
        $this->maybeAddExtraPricingColumns();

        // $this->maybeAddBulkEditOptions();

        //TODO: Make modifications to variable product bulk edit
        add_action( 
            'woocommerce_variable_product_bulk_edit_actions', 
            array(&$this, 'addVariableProductBulkEditActions')
        );
    }
}