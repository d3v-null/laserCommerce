<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//assuming wc is installed

class LaserCommerce_Admin extends WC_Settings_API{
    public function __construct() {
        
        $this->id                   = 'DL_Advanced_Ecommerce';
        $this->method_title         = 'Laserphile Advanced Ecommerce';
        $this->method_description   = 'Configure advanced eCommerce settings';
        
        //$this->price_tier_option    = $this->id.'_price_tier_preferences';
        
        // if( is_admin() ){
            // $this->admin_includes();
        // }
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
?>