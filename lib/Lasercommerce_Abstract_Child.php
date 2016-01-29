<?php

Class Lasercommerce_Abstract_Child {
    public function __construct() {
        global $Lasercommerce_Plugin;
        $this->plugin = $Lasercommerce_Plugin;
    }

    public function prefix_option($option){
        return $this->plugin->prefix($option);
    }

    public function unprefix_option( $option_name ){
        return $this->plugin->unPrefix( $option_name );
    }    
    
    public function get_tier_key_key(){
        return $this->plugin->tier_key_key;
    }

    public function get_tier_tree_key(){
        return $this->plugin->tier_tree_key;
    }

    public function get_option( $option_name, $default ){
        return $this->plugin->getOption( $option_name, $default );
    }    

    public function set_option( $option_name, $option_value ){
        return $this->plugin->updateOption( $option_name, $default );
    }    

}

?>