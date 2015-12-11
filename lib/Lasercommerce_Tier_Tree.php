<?php

include_once('Lasercommerce_Tier.php');

/**
 * helper class for dealing with price tier tree
 */
class Lasercommerce_Tier_Tree {
    public $_class = "LC_TT_";

    public static $rootID = 'default';

    private $decoded_trees = array();
    private $cached_visible_tiers = array();
    private $treeTiers;

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
     * Gets the tier tree in the form of an array of arrays
     *
     * @return array tier_tree The tree of price tiers
     */
    public function getTierTree($json_string = ''){
        $_procedure = $this->_class."GET_TIER_TREE: ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");


        if(!$json_string) $json_string = get_option($this->prefix_option($this->get_tier_tree_key()));
        // if(LASERCOMMERCE_DEBUG) error_log($_procedure."JSON string: $json_string");

        if(isset($this->decoded_trees[$json_string])){
            $tierTree = $this->decoded_trees[$json_string];
            // if(LASERCOMMERCE_DEBUG) error_log($_procedure."found cached: ".serialize($tierTree));
        } else {
            $tierTree = json_decode($json_string, true);
            if ( !$tierTree ) {
                if(LASERCOMMERCE_DEBUG) error_log($_procedure."could not decode");
                $tierTree = array(); //array('id'=>'administrator'));
            } 
            else {
                // if(LASERCOMMERCE_DEBUG) error_log($_procedure."decoded: ".serialize($tierTree));
            } 
        }

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old;         

        return $tierTree;
    }
    
    /**
     * Used by getTreeTiers to geta flattened version of the Tree
     * 
     * @param array $node an array containing the node to be flattened recursively
     * @return the tiers contained within $node
     */
    private function flattenTierTree($node = array()){
        $_procedure = $this->_class."FLATTEN_TREE_RECURSIVE: ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        // if(LASERCOMMERCE_DEBUG) {
        //     error_log($_procedure."node");
        //     if(is_array($node)) foreach($node as $k => $v) error_log($_procedure." ($k, ".serialize($v).")");
        // }

        if( !isset($node['id']) ) return array();

        $tiers = array();
        $tier = Lasercommerce_Tier::fromNode($node);
        if($tier){
            $tiers[] = $tier;
        }

        if( isset($node['children'] ) ){
            foreach( $node['children'] as $child ){
                // if(LASERCOMMERCE_DEBUG) error_log($_procedure."child: ".serialize($child));
                $result = $this->flattenTierTree($child);
                // if(LASERCOMMERCE_DEBUG) error_log($_procedure."result: ".serialize($result));
                $tiers = array_merge($tiers, $result);
            }
        }
        unset($node['children']);

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $tiers;
    }

    public function getTreeTiers(){
        $_procedure = $this->_class."GET_TIERS: ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        if(isset($this->treeTiers)){
            $tiers = $this->treeTiers;
        } else {   
            $tree = $this->getTierTree();
            $tiers = array();
            foreach( $tree as $node ){
                $tiers = array_merge($tiers, $this->flattenTierTree($node));
            }
        }
        $this->treeTiers = $tiers;

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $tiers;
    }

    public function getTier($tierID){
        $tiers = $this->getTreeTiers();
        foreach ($tiers as $tier) {
            if(strtoupper($tierID) === strtoupper($tier->id)){
                return $tier;
            }
        }
    }    

    public function getTierID($tier){
        if(is_string($tier)) $tier = $this->getTier($tier);
        return $tier->id;
    }

    public function getTierName($tier){
        if(is_string($tier)) $tier = $this->getTier($tier);
        return $tier->name;
    }

    public function getTierMajor($tier){
        if(is_string($tier)) $tier = $this->getTier($tier);
        return $tier->major;        
    }

    public function getTierOmniscient($tier){
        if(is_string($tier)) $tier = $this->getTier($tier);
        return $tier->omniscient;        
    }

    public function getTierIDs($tiers){
        $tierIDs = array();
        if(is_array($tiers)) foreach ($tiers as $tier) {
            $tierID = $this->getTierID($tier);
            if($tierID) $tierIDs[] = $tierID;
        }
        return $tierIDs;
    }    

    public function getTiers($tierIDs){
        $tiers = array();
        if(is_array($tierIDs)) foreach ($tierIDs as $tierID) {
            $tier = $this->getTier($tierID);
            if($tier) $tiers[] = $tier;
        }
        return $tiers;
    }

    /**
     * Gets a list of all the tier IDs in the provided list of tiers.
     * if no list provided, get list of all ids in the Tree
     *
     * @return array tiers A list or tiers in the tree
     */
    public function getTreeTierIDs(){
        return $this->getTierIDs($this->getTreeTiers());
    }  

    public function getActiveTiers(){
        trigger_error("Deprecated function called: getActiveTiers, use getTreeTiers instead", E_USER_NOTICE);;
    }

    private function filterRolesRecursive($node, $roles){
        trigger_error("Deprecated function called: filterRolesRecursive, use filterTiersRecursive instead", E_USER_NOTICE);;
    }

    /**
     * Gets the list of roles that are deemed omniscient - These roles can see all prices
     * 
     * @return array omniscienct_roles an array containing all of the omoniscient roles
     */
    public function getOmniscientRoles(){
        trigger_error("Deprecated function called: getOmniscientRoles, use getOmniscientTiers instead", E_USER_NOTICE);;
    }

    public function getOmniscientTiers($tiers = array()){
        $_procedure = $this->_class."GET_OMNISCIENT_TIERS: ";

        // if(LASERCOMMERCE_DEBUG) error_log($_procedure."tiers: ".serialize($tiers) );

        if(!$tiers) $tiers = $this->getTreeTiers();
        
        $omniTiers = array();
        if(is_array($tiers)) foreach ($tiers as $tier) {
            if($this->getTierOmniscient($tier)){
                $omniTiers[] = $tier;
            }
        }
        return $omniTiers;
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
        $_procedure = $this->_class."FILTER_TIERS_RECURSIVE: ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        if( !isset($node['id']) ) { //is valid array
            return array();
        }

        $visibleTiers = array();
        if( isset($node['children'] ) ) { //has children
            foreach( $node['children'] as $child ){
                $visibleTiers = array_merge($visibleTiers, $this->filterTiersRecursive($child, $tiers));
            }
        }
        unset($node['children']);
        
        // IF(WP_DEBUG) error_log("recusrive for node: ".$node['id']);
        // IF(WP_DEBUG) error_log("-> good node: ".in_array( $node['id'], $tiers ));
        // IF(WP_DEBUG) error_log("-> good children: ".!empty($tiers));
        
        if(!empty($visibleTiers) or in_array( strtoupper($node['id']), $this->getTierIDs($tiers) )){
            if(LASERCOMMERCE_DEBUG) error_log($_procedure."adding node: ".$node['id'] );
            $tier = Lasercommerce_Tier::fromNode($node);
            if($tier){
                $visibleTiers[] = $tier;
            }

        }
        // IF(WP_DEBUG) error_log("-> tiers:  ".serialize($visibleTiers));

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        return $visibleTiers;
    }

    public function getAvailableTiers($user = Null){
        trigger_error("Deprecated function called: getAvailableTiers, use getVisibleTiers instead", E_USER_NOTICE);;
    }

    public function parseUserTierString($string){
        if($string and is_string($string)){
            return explode('|', $string);
        } else {
            return array();
        }
    }
    
    /**
     * Returns an array of tier objects that the user has directly been assigned
     */
    public function getUserTiers($user = Null){
        $_procedure = $this->_class."GET_USER_TIERS: ";

        global $Lasercommerce_Tiers_Override;
        if(isset($Lasercommerce_Tiers_Override) and is_array($Lasercommerce_Tiers_Override)){
            if(LASERCOMMERCE_PRICING_DEBUG) {
                error_log($_procedure."Override is: ");
                if(is_array($Lasercommerce_Tiers_Override)) foreach ($Lasercommerce_Tiers_Override as $value) {
                    error_log($_procedure." $value");
                }
            }
            $tiers = $Lasercommerce_Tiers_Override;
        } else {
            if(is_numeric($user)){
                // $user = get_user_by('id', $user);
                $user_id = $user;
            } else {
                if(!$user){
                    $user = wp_get_current_user();
                } 
                $user_id = $user->ID;
            }

            // if(LASERCOMMERCE_DEBUG) error_log($_procedure."user_id: ".serialize($user_id));
            $tier_key = $this->plugin->getOption($this->plugin->tier_key_key);
            $user_tier_string = get_user_meta($user_id, $tier_key, true);
            $default_tier = $this->plugin->getOption($this->plugin->default_tier_key);
            if(!$user_tier_string){
                // if(LASERCOMMERCE_DEBUG) error_log($_procedure."using default");
                $user_tier_string = $default_tier;
            }
            // if(LASERCOMMERCE_DEBUG) error_log($_procedure."user_tier_string: ".serialize($user_tier_string));
            $tierIDs = $this->parseUserTierString($user_tier_string);
            $tiers = $this->getTiers($tierIDs);
        }        
        return $tiers;
    }

    public function serializeTiers($tiers = array()){
        return implode("|", $this->getTierIDs($tiers));
    }

    /**
     * Gets a list of the price tiers available to a user
     *
     * @param array $user a user or userID
     * @return array $available_tiers the list of price tiers available to the user
     */
    public function getVisibleTiers($user = Null){
        $_procedure = $this->_class."GET_VISIBLE_TIERS: ";

        global $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace_old = $lasercommerce_pricing_trace;
        $lasercommerce_pricing_trace .= $_procedure; 
        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."BEGIN");

        $tiers = $this->getUserTiers($user);
        if(empty($tiers)) {
            return array();
        }

        if($this->getOmniscientTiers($tiers)){
            $tiers = $this->getTreeTiers();
        }

        $tier_flat = $this->serializeTiers($tiers);
        // if(LASERCOMMERCE_DEBUG) error_log($_procedure."tier_flat: ".serialize($tier_flat));
        if(isset($this->cached_visible_tiers[$tier_flat])){
            $visibleTiers = $this->cached_visible_tiers[$tier_flat];
        } else {
            $tree = $this->getTierTree();
            $visibleTiers = array();
            foreach( $tree as $node ){
                $visibleTiers = array_merge($visibleTiers, $this->filterTiersRecursive($node, $tiers));
            }
            $this->cached_visible_tiers[$tier_flat] = $visibleTiers; 
        }

        // if(LASERCOMMERCE_DEBUG) error_log($_procedure."visibleTiers: ".serialize($visibleTiers));

        if(LASERCOMMERCE_PRICING_DEBUG) error_log($lasercommerce_pricing_trace."END");
        $lasercommerce_pricing_trace = $lasercommerce_pricing_trace_old; 

        //is this necessary any more??
        return array_reverse($visibleTiers);
    }

    public function serializeVisibleTiers(){
        return $this->serializeTiers($this->getVisibleTiers());
    }

    public function getAncestors($roles){
        trigger_error("Deprecated function called: getAncestors. Not used.", E_USER_NOTICE);;
    }


    public function getMajorTiers($tiers = null){
        if(!$tiers) $tiers = $this->getTreeTiers();
        $majorTiers = array();
        if(is_array($tiers)) foreach ($tiers as $tier) {
            if(is_string($tier)) $tier = $this->getTier($tier);
            if($tier->major){
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
        trigger_error("Deprecated function called: getProductPostID.", E_USER_NOTICE);;
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
 