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
}