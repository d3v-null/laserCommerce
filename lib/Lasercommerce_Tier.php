<?php

/**
*
*/
class Lasercommerce_Tier
{

    public $id;
    public $name;
    public $major;
    public $omniscient;

    public function __construct( $id, $name='', $major=0, $omniscient=0)
    {
        $this->id = strtoupper($id);
        $this->name = $name?$name:$this->id;
        $this->major = $major;
        $this->omniscient = $omniscient;
    }

    public static function fromNode( $node ){
        if(isset($node['id'])){
            $instance = new self($node['id']);
            if(isset($node['name'])){
                $instance->name = $node['name'];
            }
            if(isset($node['major'])){
                $instance->major = $node['major'];
            }
            if(isset($node['omniscient'])){
                $instance->omniscient = $node['omniscient'];
            }
            return $instance;
        }
    }

    public function __toString(){
        return $this->id;
    }

    public function begin_tier_override(){
        $_procedure = "LC_TR_BEGIN_OVERRIDE(".$this->id."): ";
        global $Lasercommerce_Tiers_Override;
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."OLD = ".serialize($Lasercommerce_Tiers_Override));
        $this->old_override = $Lasercommerce_Tiers_Override;
        $Lasercommerce_Tiers_Override = array($this);
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."NEW = ".serialize($Lasercommerce_Tiers_Override));
    }

    public function end_tier_override(){
        $_procedure = "LC_TR_END_OVERRIDE(".$this->id."): ";
        global $Lasercommerce_Tiers_Override;
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."OLD = ".serialize($Lasercommerce_Tiers_Override));
        $Lasercommerce_Tiers_Override = $this->old_override;
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."NEW = ".serialize($Lasercommerce_Tiers_Override));
    }

    // public function get_product_price_html($_product = null, ){
    //     if( $_product == null){
    //         global $product;
    //         $_product = $product;
    //     }
    //     $this->begin_tier_override();
    //     // $price_html = $_product->get_price_html();
    //     $price = $_product->get_price();
    //     $this->end_tier_override();
    //     return $price_html;
    // }
}
