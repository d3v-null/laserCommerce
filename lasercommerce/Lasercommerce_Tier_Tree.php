<?php

/**
 * helper class for dealing with price tier tree
 */
class Lasercommerce_Tier_Tree {

    public static $rootID = 'default';

    /**
     * Constructs the helper object
     * @param $optionNamePrefix The prefix used to find price tier options
     */
    public function __construct($optionNamePrefix = 'lc_') {
        $this->optionNamePrefix = $optionNamePrefix;
    }
    
    /**
     * (Depreciated!) Gets the price of a given post for a given role
     * 
     * @param integer $postID The ID of the given product / product variation
     * @param string $role The role of the price being retrieved
     * @return string price
     */
    public function getPrice( $postID, $role ){
        return get_post_meta( $postID, $this->optionNamePrefix.$role.'_price', true);
    }

    /**
     * (Undeveloped Functionality) Gets the list of roles that are deemed omniscient - These roles can see all prices
     * 
     * @return array omniscienct_roles an array containing all of the omoniscient roles
     */
    public function getOmniscientRoles(){
        //TODO: This
        return array('administrator');
    }
    
    /**
     * Gets the tier tree in the form of an array of arrays
     *
     * @return array tier_tree The tree of price tiers
     */
    public function getTierTree(){
        //If(WP_DEBUG) error_log("Getting Tier Tree");
        //todo: this

        //$json_string = '[{"id":"special_customer","children":[{"id":"wholesale_buyer","children":[{"id":"distributor","children":[{"id":"international_distributor"}]},{"id":"mobile_operator"},{"id":"gym_owner"},{"id":"salon"},{"id":"home_studio"}]}]}]';
        $json_string = get_option($this->optionNamePrefix.'price_tiers');
        //If(WP_DEBUG) error_log("-> JSON string: $json_string");

        $tierTree = json_decode($json_string, true);
        if ( !$tierTree ) {
            
            // If(WP_DEBUG) error_log("-> could not decode ");
            return array(); //array('id'=>'administrator'));
        } 
        else {
            // If(WP_DEBUG) error_log("-> decoded: ".  serialize($tierTree));
            return $tierTree; //MIGHT HAVE TO CHANGE THIS TO $tierTree[0]
        } 
    }
    
    /**
     * Used by getRoles to geta flattened version of the Tree
     * 
     * @param array $node an array containing the node to be flattened recursively
     * @return the roles contained within $node
     */
    private function flattenTierTreeRecursive($node = array()){
        // IF(WP_DEBUG) foreach($node as $k => $v) error_log("node: ($k, ".serialize($v).")");
        if( !isset($node['id']) ) return array();
        $roles = array($node['id']);
        if( isset($node['children'] ) ){
            foreach( $node['children'] as $child ){
                //IF(WP_DEBUG) error_log("key, child: $key, ".serialize($child));
                $result = $this->flattenTierTreeRecursive($child);
                //IF(WP_DEBUG) error_log("result: ".serialize($result));
                $roles = array_merge($roles, $result);
            }
        }
        // IF(WP_DEBUG) error_log("names: ".serialize($names));
        return $roles;
    }
    
    /**
     * Gets a list of all the roles in the Tree
     *
     * @return array roles A list or roles in the tree
     */
    public function getRoles(){
        $tree = $this->getTierTree();
        $roles = array();
        foreach( $tree as $node ){
            $roles = array_merge($roles, $this->flattenTierTreeRecursive($node));
            // IF(WP_DEBUG) error_log("merge: ".serialize($names));
        }
        return $roles;
    }   

    public function getActiveRoles(){
        return array_intersect($this->getRoles(), array_keys($this->getNames()));
    }

    /**
     * Used by getAvailableTiers to recursively determine the price tiers available 
     * for a user that can view a given list of roles
     *
     * @param array $node The node to be analysed
     * @param array $roles The list of roles visible to the user
     * @return array $tiers The list of tiers available to the user
     */
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
    
    /**
     * Gets a list of the price tiers available to a user who can view the given list of roles
     *
     * @param array $roles A list of roles that the user can see
     * @return array $available_tiers the list of price tiers available to the user
     */
    public function getAvailableTiers($roles){
        $tree = $this->getTierTree();
        if(empty($roles)) return array();
        $omniscient = $this->getOmniscientRoles();
        $activeRoles = $this->getActiveRoles();
        foreach ($roles as $role) {
            if (in_array($role, $omniscient) ){
                $roles = $activeRoles;
                break;
            }
        }
        $tiers = array();
        foreach( $tree as $node ){
            foreach($this->filterRolesRecursive($node, $roles) as $role){
                $tiers[] = $role;
            }
        }
        IF(WP_DEBUG) error_log("availableTiers: ".serialize($tiers));
        return array_reverse($tiers);
    }

    public function getAncestors($roles){
        if(is_array($roles)){
            return getAvailableTiers($roles);
        } else if (is_string($roles)){
            return getAvailableTiers(array($roles));
        }
    }


    /**
     * Gets the postID of a given simple or variable product
     *
     * @param WC_Product $product the product to be analysed
     * @return integer $postID The postID of the simple or variable product
     */ 
    public function getPostID( $product ){
        if(!isset($product)) {
            //if(WP_DEBUG) error_log( '-> product not set');
            return Null;
        }
        if( $product->is_type( 'variation' ) ){
            //If(WP_DEBUG) error_log("--> variable product");
            if ( isset( $product->variation_id ) ) {
                $postID = $product->variation_id;
            } else {
                //If(WP_DEBUG) error_log("--> !!!!!! variation not set");
                $postID = Null;
            }
        } else {
            if(isset( $product->id )){
                $postID = $product->id;
            } else {
                $podtID = Null;
            }
        }
        //If(WP_DEBUG) error_log("-> postID: $postID");
        return $postID;
    }

    /** 
     * Gets the mapping of roles to human readable names
     *
     * @return array $names the mapping of roles to human readable names
     */
    public function getNames( ){
        $defaults = array(
            // '' => 'Public',
        );

        global $wp_roles;
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles(); 
        $names = $wp_roles->get_names();
        return array_merge($defaults, $names);
    }
}
 