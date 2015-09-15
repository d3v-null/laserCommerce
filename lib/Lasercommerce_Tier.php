<?php

/**
* 
*/
class Lasercommerce_Tier
{

    public $id;
    public $name;
    public $major;
    
    public function __construct( $id, $name='', $major=0)
    {
        $this->id = $id;
        $this->name = $name?$name:$id;
        $this->major = $major;
    }
}