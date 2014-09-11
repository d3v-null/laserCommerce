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
        If(WP_DEBUG) error_log("Getting Tier Tree");
        //todo: this

        //$json_string = '[{"id":"special_customer","children":[{"id":"wholesale_buyer","children":[{"id":"distributor","children":[{"id":"international_distributor"}]},{"id":"mobile_operator"},{"id":"gym_owner"},{"id":"salon"},{"id":"home_studio"}]}]}]';
        $json_string = get_option('lasercommerce_price_tiers');
        If(WP_DEBUG) error_log("-> JSON string: $json_string");

        $tierTree = json_decode($json_string, true);
        if ( !$tierTree ) {
            
            If(WP_DEBUG) error_log("-> could not decode ");
            return array(array('id'=>'administrator'));
        } 
        else {
            If(WP_DEBUG) error_log("-> decoded: ".  serialize($tierTree[0]));
            return $tierTree;
        } 


/*        return array( 
            array(
                'id' => 'special_customer',
                'name' => 'Special',
                'children' => array(
                    array(
                        'id' => 'wholesale_buyer',
                        'name' => 'Wholesale',
                        'children' => array(
                            array(
                                'id'  => 'mobile_operator',
                                'name'  => 'Mobile Operator'
                            ),
                            array(
                                'id'  => 'gym_owner',
                                'name'  => 'Gym'
                            ),
                            array(
                                'id'  => 'salon',
                                'name'  => 'Salon'
                            ),
                            array(
                                'id'  => 'home_studio',
                                'name'  => 'Home Studio'
                            ),
                            array(
                                'id'  => 'distributor',
                                'name'  => 'Distributor',
                                'children' => array(
                                    array(
                                        'id' => 'international_distributor',
                                        'name' => 'International Distributor',
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );*/
    }
    
    private function flattenTierTreeRecursive($node = array()){
        //IF(WP_DEBUG) foreach($node as $k => $v) error_log("node: ($k, ".serialize($node).")");
        if( !isset($node['id']) ) return array();
        $ids = array($node['id']);
        if( isset($node['children'] ) ){
            foreach( $node['children'] as $child ){
                //IF(WP_DEBUG) error_log("key, child: $key, ".serialize($child));
                $result = $this->flattenTierTreeRecursive($child);
                //IF(WP_DEBUG) error_log("result: ".serialize($result));
                $ids = array_merge($ids, $result);
            }
        }
        // IF(WP_DEBUG) error_log("names: ".serialize($names));
        return $ids;
    }
    
    public function getRoles(){
        $tree = $this->getTierTree();
        $ids = array();
        foreach( $tree as $node ){
            $ids = array_merge($ids, $this->flattenTierTreeRecursive($node));
            // IF(WP_DEBUG) error_log("merge: ".serialize($names));
        }
        return $ids;
    }   
    
    private function filterRolesRecursive($node, $roles){
        if( !isset($node['id']) ) { //is valid array
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
        
        // IF(WP_DEBUG) error_log("recusrive for node: ".$node['id']);
        // IF(WP_DEBUG) error_log("-> good node: ".in_array( $node['id'], $roles ));
        // IF(WP_DEBUG) error_log("-> good children: ".!empty($tiers));
        
        if(!empty($tiers) or in_array( $node['id'], $roles )){
            // IF(WP_DEBUG) error_log("--> adding role: ".$node['id'] );
            $tiers[] = $node['id'];
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
 