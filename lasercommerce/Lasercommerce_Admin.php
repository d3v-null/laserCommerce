<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//LaserCommerce Admin Page
class LaserCommerce_Admin extends WC_Settings_Page{
    public function __construct() {
        $this->id            = 'lasercommerce';
        $this->label         = __('Laserphile Advanced Ecommerce', 'lasercommerce');
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        
        //$this->price_tier_option    = $this->id.'_price_tier_preferences';
    }    
    
    public function get_settings() {
        $settings = array(
            array( 
                'type' => 'title',
                'title' => __( 'LaserCommerce Options', 'lasercommerce' ),
                'id' => $this->id,
                'desc' => 'description'
            ),
            array(
                'type' => 'sectioned',
                'id' =>
        )
        
        return apply_filters( 'lasercommerce_woocommerce_admin_settings', $settings);
    }
    
    function init() {
        $this->init_form_fields();
        $this->init_settings();
        $this->obfuscate    = $this->get_option( 'obfuscate'    );
        
    }
    
    function init_form_fields(){
        $this->form_fields = array(
            'obfuscate' => array(
                'title'         => __('Restrict Visibility', 'lasercommerce'),
                'type'          => 'checkbox',
                'label'         => __('Restrict product visibility', 'lasercommerce'),
                'description'   => __('Restrict the visibility of products to users who have appropriate roles', 'lasercommerce'),
                'default'       => 'no'
            ), 
        );
    }
    
}

new LaserCommerce_Admin();
?>