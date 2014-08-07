<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//LaserCommerce Admin Page
class LaserCommerce_Admin extends WC_Settings_Page{
    public function __construct($optionNamePrefix = 'Lasercommerce_option_') {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->optionNamePrefix = $optionNamePrefix;
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        
        //$this->price_tier_option    = $this->id.'_price_tier_preferences';
    }    
    
    public function get_settings() {
        $settings = array(
            array( 
                'title' => __( 'LaserCommerce Options', 'lasercommerce' ),
                'id'    => $this->optionNamePrefix . 'options',
                'type'  => 'title',
                'desc'  => 'Configure Laserphile advanced eCommerce settings'
            ),
            array(
                'title' => __( 'Price Tiers', 'lasercommerce' ),
                'id'    => $this->optionNamePrefix . 'price_tiers',
                'type'  => 'text',
                'default'=>'',
                'desc_tip'=>'',
            ),
            array(
                'type' => 'sectionend',
                'id' => $this->optionNamePrefix . 'options'
            )
        );
        //TODO: sanitize price tiers
        //TODO: enter price tiers in table
        return apply_filters( 'lasercommerce_woocommerce_admin_settings', $settings);
    }    
}
?>