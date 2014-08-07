<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//LaserCommerce Admin Page
class LaserCommerce_Admin extends WC_Settings_Page{
    public function __construct($optionNamePrefix = 'lasercommerce_') {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->optionNamePrefix = $optionNamePrefix;
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_admin_field_price_tier', array( $this, 'price_tier_setting' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
    }    
    
    public function get_sections() {
        $sections = array(
            '' => _('Advanced Pricing and Visibility', 'lasercommerce'),
        );
        
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }
    
    public function get_settings( $current_section = "" ) {
        if( $current_section == '' ) { //Advanced Pricing and Visibility
            return apply_filters( 'woocommerce_lasercommerce_pricing_visibility_settings', array(
                array( 
                    'title' => __( 'LaserCommerce Advanced Pricing and Visibility Options', 'lasercommerce' ),
                    'id'    => $this->optionNamePrefix . 'options',
                    'type'  => 'title',
                ),
                array(
                    'type'  => 'price_tiers',
                ),
                    // 'title' => __( 'Price Tiers', 'lasercommerce' ),
                    // 'id'    => $this->optionNamePrefix . 'price_tiers',
                    // 'type'  => 'text',
                    // 'default'=>'',
                    // 'desc_tip'=>'',
                // ),
                array(
                    'type' => 'sectionend',
                    'id' => $this->optionNamePrefix . 'options'
                )
            );
        }
        //TODO: sanitize price tiers
        //TODO: enter price tiers in table
    }    
    
    public function output() {
        global $current_section;
        
        if( $current_section == '' ){
            $settings = $this->get_settings();
        
            WC_Admin_settings::output_fields($settings );
        }
    }
    
    public function price_tier_setting(){
        $price_tiers = get_option($this->optionNamePrefix.'price_tiers');
        IF(WP_DEBUG) error_log("price tiers: ".serialize($price_tiers);
    
        ?>
        <tr valign="top">
            <th colspan scope="row" class="titledesc"><?php _e('Price Tiers', 'lasercommerce'); ?></th>
            <td>
                <table class="dl_price_tier widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="tb"></th>
                            <th class="role">
                                <?php _e('User Role', 'lasercommerce'); ?>
                            </th>
                            <th class="name">
                                <?php _e('Tier Name', 'lasercommerce'); ?>
                            </th>
                            <th class="parent"</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- first row -->
                        <tr class="lasercommerce firstrow">
                            <td class="tb"></th>
                            <td class="role">
                                <?php _e('Any', 'lasercommerce'); ?>
                            </td>
                            <td class="name">
                                <?php _e('Regular Price', 'woocommerce'); ?>
                            </td>
                            <td class="parent"></th>
                        </tr>
                        <?php
                        foreach($price_tiers as $role => $tier){
                        ?>
                            <tr>
                                <td 
                            
                        <?
                        }
                        ?>    
        <?php
}
?>