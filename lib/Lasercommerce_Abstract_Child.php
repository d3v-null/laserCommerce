<?php

Class Lasercommerce_Abstract_Child extends Lasercommerce_Debugger{
    public function __construct() {
    }

    public function prefix_option($option){
        return $this->prefix($option);
    }

    public function unprefix_option( $option_name ){
        return $this->unPrefix( $option_name );
    }

    public function get_option( $option_name, $default = null ){
        return $this->getOption( $option_name, $default );
    }

    public function set_option( $option_name, $option_value ){
        return $this->updateOption( $option_name, $option_value );
    }

}

?>
