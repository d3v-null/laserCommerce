<?php

/*
 * helper class for dealing with price tier tree
 */
class Lasercommerce_Tier_Tree {
    public function __construct($optionNamePrefix = 'lasercommerce_') {
        $this->optionNamePrefix = $optionNamePrefix;
    }
    
    public function getPrice( $postID, $role ){
        return get_post_meta( $postID, $this->optionNamePrefix.$role.'_price', true);
    }
    
    public function getTierTree(){
        //todo: this
        
        return array( 
            array(
                'role' => 'special_customer',
                'name' => 'Special',
                'children' => array(
                    array(
                        'role' => 'wholesale_buyer',
                        'name' => 'Wholesale',
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
                                'name'  => 'Distributor',
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
        );
    }
    
    private function flattenTierTreeRecursive($node = array()){
        //IF(WP_DEBUG) foreach($node as $k => $v) error_log("node: ($k, ".serialize($node).")");
        if( !isset($node['role']) ) return array();
        $names = array();
        $names[$node['role']] = $node['name'];
        if( isset($node['children'] ) ){
            foreach( $node['children'] as $child ){
                //IF(WP_DEBUG) error_log("key, child: $key, ".serialize($child));
                $result = $this->flattenTierTreeRecursive($child);
                //IF(WP_DEBUG) error_log("result: ".serialize($result));
                $names = array_merge($names, $result);
            }
        }
        // IF(WP_DEBUG) error_log("names: ".serialize($names));
        return $names;
    }
    
    public function getTierNames(){
        $tree = $this->getTierTree();
        $names = array();
        foreach( $tree as $node ){
            $names = array_merge($names, $this->flattenTierTreeRecursive($node));
            // IF(WP_DEBUG) error_log("merge: ".serialize($names));
        }
        return $names;
    }        
    
    private function filterRolesRecursive($node, $roles){
        if( !isset($node['role']) ) { //is valid array
            return array();
        }
        $tiers = array();
        if( isset($node['children'] ) ) { //has children
            foreach( $node['children'] as $child ){
                foreach($this->filterRolesRecursive($child, $roles) as $role){
                    $tiers[] = $role;
                }
            }
        }
        
        // IF(WP_DEBUG) error_log("recusrive for node: ".$node['role']);
        // IF(WP_DEBUG) error_log("-> good node: ".in_array( $node['role'], $roles ));
        // IF(WP_DEBUG) error_log("-> good children: ".!empty($tiers));
        
        if(!empty($tiers) or in_array( $node['role'], $roles )){
            // IF(WP_DEBUG) error_log("--> adding role: ".$node['role'] );
            $tiers[] = $node['role'];
        }
        // IF(WP_DEBUG) error_log("-> tiers:  ".serialize($tiers));
        return $tiers;
    }
    
    public function getAvailableTiers($roles){
        $tree = $this->getTierTree();
        if(empty($roles)) return array();
        $tiers = array();
        foreach( $tree as $node ){
            foreach($this->filterRolesRecursive($node, $roles) as $role){
                $tiers[] = $role;
            }
        }
        //IF(WP_DEBUG) error_log("availableTiers: ".serialize($tiers));
        return $tiers;
    }
        
    public function getVisibleTiersSimple($postID, $roles){

        $availableTiers = $this->getAvailableTiers($roles);        
        
        $visibleTiers = array();
        foreach( $availableTiers as $role ){
            $price = $this->getPrice($postID, $role);
            if( $price ) $visibleTiers[$role] = $price;
            If(WP_DEBUG) error_log("--> $role price: $price");
        }
        
        return $visibleTiers;
    }
}
 