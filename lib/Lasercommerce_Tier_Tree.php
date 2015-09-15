<?php

/**
 * helper class for dealing with price tier tree
 */
class Lasercommerce_Tier_Tree {
    public $_class = "LC_TIER_TREE_";

    public static $rootID = 'default';

    /**
     * Constructs the helper object
     */
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

    /**
     * (Undeveloped Functionality) Gets the list of roles that are deemed omniscient - These roles can see all prices
     * 
     * @return array omniscienct_roles an array containing all of the omoniscient roles
     */
    public function getOmniscientRoles(){
        //TODO: This
        trigger_error("Deprecated function called: getOmniscientRoles, use getOmniscientTiers instead", E_USER_NOTICE);;
    }

    public function getOmniscientTiers(){
        //TODO: This
        return array('administrator');
    }
    
    /**
     * Gets the tier tree in the form of an array of arrays
     *
     * @return array tier_tree The tree of price tiers
     */
    public function getTierTree($json_string = ''){
        $_procedure = $this->_class."GET_TIER_TREE: ";
        if(LASERCOMMERCE_DEBUG) error_log($_procedure);

        if(!$json_string) $json_string = get_option($this->prefix_option($this->get_tier_tree_key()));
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."JSON string: $json_string");

        $tierTree = json_decode($json_string, true);
        if ( !$tierTree ) {
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."could not decode");
            return array(); //array('id'=>'administrator'));
        } 
        else {
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."decoded: ".serialize($tierTree));
            return $tierTree; 
        } 
    }
    
    /**
     * Used by getTiers to geta flattened version of the Tree
     * 
     * @param array $node an array containing the node to be flattened recursively
     * @return the tiers contained within $node
     */
    private function flattenTierTree($node = array()){
        $_procedure = $this->_class."FLATTEN_TREE_RECURSIVE: ";

        if(LASERCOMMERCE_DEBUG) {
            error_log($_procedure."node");
            if(is_array($node)) foreach($node as $k => $v) error_log($_procedure." ($k, ".serialize($v).")");
        }

        if( !isset($node['id']) ) return array();

        $tiers = array( $node );
        if( isset($node['children'] ) ){
            foreach( $node['children'] as $child ){
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."child: ".serialize($child));
                $result = $this->flattenTierTree($child);
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."result: ".serialize($result));
                $tiers = array_merge($tiers, $result);
            }
        }
        unset($node['children']);
        return $tiers;
    }

    public function getTiers(){
        $_procedure = $this->_class."GET_TIERS: ";
        $tree = $this->getTierTree();
        $tiers = array();
        foreach( $tree as $node ){
            $tiers = array_merge($tiers, $this->flattenTierTree($node));
        }
        return $tiers;
    }

    public function getIDs($tiers){
        $tierIDs = array();
        if(is_array($tiers)) foreach ($tiers as $tier) {
            if(isset($tier['id'])){
                $tierIDs[] = $tier['id'];
            }
        }
        return $tierIDs;
    }    

    /**
     * Gets a list of all the tier IDs in the Tree
     *
     * @return array tiers A list or tiers in the tree
     */
    public function getTierIDs(){
        return $this->getIDs($this->getTiers());
    }  

    public function getActiveTiers(){
        trigger_error("Deprecated function called: getActiveTiers, use getTiers instead", E_USER_NOTICE);;
    }

    private function filterRolesRecursive($node, $roles){
        trigger_error("Deprecated function called: filterRolesRecursive, use filterTiersRecursive instead", E_USER_NOTICE);;
    }

    /**
     * Used by getVisibleTiers to recursively determine the price tiers visible 
     * for a user that can view a given list of tiers
     *
     * @param array $node The node to be analysed
     * @param array $tiers The list of tiers visible to the user
     * @return array $visibleTiers The list of tiers visible to the user
     */
    private function filterTiersRecursive($node, $tiers){
        if( !isset($node['id']) ) { //is valid array
            return array();
        }
        $visibleTiers = array();
        if( isset($node['children'] ) ) { //has children
            foreach( $node['children'] as $child ){
                array_merge($visibleTiers, $this->filterTiersRecursive($child, $tiers));
            }
        }
        unset($node['children']);
        
        // IF(WP_DEBUG) error_log("recusrive for node: ".$node['id']);
        // IF(WP_DEBUG) error_log("-> good node: ".in_array( $node['id'], $tiers ));
        // IF(WP_DEBUG) error_log("-> good children: ".!empty($tiers));
        
        if(!empty($visibleTiers) or in_array( $node['id'], $this->getIDs($visibleTiers) )){
            // IF(WP_DEBUG) error_log("--> adding role: ".$node['id'] );
            $visibleTiers[] = $node;
        }
        // IF(WP_DEBUG) error_log("-> tiers:  ".serialize($visibleTiers));
        return $visibleTiers;
    }

    public function getAvailableTiers($user = Null){
        trigger_error("Deprecated function called: getAvailableTiers, use getVisibleTiers instead", E_USER_NOTICE);;
    }
    
    /**
     * Gets a list of the price tiers available to a user
     *
     * @param array $user a user or userID
     * @return array $available_tiers the list of price tiers available to the user
     */
    public function getVisibleTiers($user = Null){
        $_procedure = $this->_class."GET_AVAILABLE_TIERS: ";
        global $Lasercommerce_Tiers_Override;
        if(isset($Lasercommerce_Tiers_Override) and is_array($Lasercommerce_Tiers_Override)){
            if(LASERCOMMERCE_PRICE_DEBUG) {
                error_log($_procedure."Override is: ");
                if(is_array($Lasercommerce_Tiers_Override)) foreach ($Lasercommerce_Tiers_Override as $value) {
                    error_log($_procedure." $value");
                }
            }
            $tiers = $Lasercommerce_Tiers_Override;
        } else {
            if(!$user){
                $user = wp_get_current_user();
            } elseif(is_numeric($user)){
                $user = get_user_by('id', $user);
            } else {
                // assert( $user instanceof WP_User);
            }
            // if(LASERCOMMERCE_DEBUG) error_log("-> user: ".$current_user->ID);
            if(isset($user->roles)){
                $roles = $user->roles;
            } else {
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."called with bad user");
                $roles = array();
            }
        }
        // if(LASERCOMMERCE_DEBUG and PRICE_DEBUG) error_log("--> roles: ".serialize($roles));

        $tree = $this->getTierTree();
        if(empty($roles)) return array();
        $omniscient = $this->getOmniscientRoles();
        $tierIDs = $this->getTierIDs();
        foreach ($roles as $role) {
            if (in_array($role, $omniscient) ){
                $roles = $tierIDs;
                break;
            }
        }
        $visibleTiers = array();
        foreach( $tree as $node ){
            foreach($this->filterTiersRecursive($node, $roles) as $role){
                $visibleTiers[] = $role;
            }
        }
        if(LASERCOMMERCE_DEBUG) error_log($_procedure."visibleTiers: ".serialize($visibleTiers));
        return array_reverse($visibleTiers);
    }

    public function getAncestors($roles){
        trigger_error("Deprecated function called: getAncestors. Not used.", E_USER_NOTICE);;
    }


    public function getMajorTiers($tiers = null){
        if(!$tiers) $tiers = $this->getTiers();
        $majorTiers = array();
        if(is_array($tiers)) foreach ($tiers as $tier) {
            if(isset($tiers['major']) and $tiers['major']){
                $majorTiers[] = $tier;
            }
        }
        return $majorTiers;
    }



    /**
     * Gets the postID of a given simple or variable product
     *
     * @param WC_Product $product the product to be analysed
     * @return integer $postID The postID of the simple or variable product
     */ 
    public function getProductPostID( $product ){
        trigger_error("Deprecated function called: getAncestors.", E_USER_NOTICE);;
    }

    /** 
     * Gets the mapping of roles to human readable names
     *
     * @return array $names the mapping of roles to human readable names
     */
    public function getNames( ){
        // $defaults = array(
        //     // '' => 'Public',
        // );

        // global $wp_roles;
        // if ( ! isset( $wp_roles ) )
        //     $wp_roles = new WP_Roles(); 
        // $names = $wp_roles->get_names();
        // return array_merge($defaults, $names);
        trigger_error("Deprecated function called: getNames.", E_USER_NOTICE);;

    }
}
 