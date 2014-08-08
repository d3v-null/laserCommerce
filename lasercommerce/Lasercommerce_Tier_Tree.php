<?php

/*
 * helper class for dealing with price tier tree
 */
class Lasercommerce_Tier_Tree {
    public function __construct($optionNamePrefix = 'lasercommerce_') {
        $this->optionNamePrefix = $optionNamePrefix;
    }
    
    public function getPrice( $postID, $role ){
        return get_post_meta( $postID, $optionNamePrefix.$role.'_price';
    }
    
    public function getPriceTierTree(){
        //todo: this
        
        return array( 
            array(
                'role' => 'special_customer',
                'name' => 'Special',
                'children' => array(
                    'role' => 'wholesale_buyer',
                    'name' => 'Wholesale'
                    'children' => array(
                        array(
                            'role'  => 'mobile_operator',
                            'name'  => 'Mobile Operator'
                        ),
                        array(
                            'role'  => 'gym_owner',
                            'name'  => 'Gym'
                        ),
                        array(
                            'role'  => 'salon',
                            'name'  => 'Salon'
                        ),
                        array(
                            'role'  => 'home_studio',
                            'name'  => 'Home Studio'
                        ),
                        array(
                            'role'  => 'distributor',
                            'name'  => 'Distributor'
                            'children' => array(
                                array(
                                    'role' => 'international_distributor',
                                    'name' => 'International Distributor',
                                )
                            )
                        )
                    )
                )
            )
        )
    }
    
    private function flattenPriceTierTreeRecursive($node = array()){
        if( !in_array('role', $node) ) return array();
        $names = array(
            $node->role => $node->name
        );
        if( in_array('children', $node) ){
            foreach( $node->children as $child ){
                array_merge($names, flattenPriceTierTreeRecursive($child) );
            }
        }
        return $names;
    }
    
    public function getPriceTierNames(){
        $tree = $this->getPriceTierTree();
        $names = array();
        foreach( $tree as $node ){
            array_merge($names, flattenPriceTierTreeRecursive($tree);
        }
    }        
    
    private function filterRolesRecursive($node, $roles){
        if( !isset($node->role) ) { //is valid array
            return array();
        }
        $tiers = ( in_array( $node->role, $roles ) ? $node->role : array() );
        if( isset($node->children ) { //has children
            foreach( $node->children as $child ){
                $tiers = array_merge($tiers, filterRolesRecursive($child, $roles));
            }
        }
        return $tiers;
    }
    
    public function getAvailableTiers($roles){
        $tree = $this->getPriceTierTree();
        if(empty($roles)) return array();
        $tiers = array();
        foreach( $tree as $child ){
            $tiers = array_merge($tiers, filterRolesRecursive($child, $roles));
        }
    }
        
    public function getVisiblePriceTiers( $post_id ){
        if ( !is_user_logged_in() ) return array();
        
        $currentUser = wp_get_current_user();
        $roles = getAvailableTiers($currentUser->roles())        
        
        $tierNames = $this->getPriceTierNames();
        $tiers = array();
        foreach($roles as $role){
            $price = getPrice( $post_id, $role );
            if( $price ) $tiers[$role] = $price;
        }
        return $tiers;
    }
}
 