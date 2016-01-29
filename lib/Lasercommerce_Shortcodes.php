<?php

//product category shortcodes
//dynamically generate woocommerce product category shortcode strings based on input

class Lasercommerce_Shortcodes extends Lasercommerce_Abstract_Child
{
    const _CLASS = "LC_SC_";

    function __construct(){
        parent::__construct();
        $this->tree = $this->plugin->tree;
        $this->visibility = $this->plugin->visibility;
        add_action( 'init', array( &$this, 'wp_init' ), 999);
    }

    public function proof_of_concept_shortcode($args, $content=""){
        return $this->tree->_class . $content;
    }

    public function wp_init() {
        add_shortcode('lasercommerce_shortcode_test', array(&$this, 'proof_of_concept_shortcode'));
    }

}