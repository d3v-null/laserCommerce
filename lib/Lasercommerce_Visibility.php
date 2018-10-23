<?php

/**
* Much of the function signatures in this file are inspired by Groups Restrict Categories. http://www.itthinx.com/plugins/groups-restrict-categories/
* I had to implement this in my own plugin because the limmitations of groups restrict categories didn't allow me to do what I needed to do,
* However I adminred the mechanics of the plugin that I decided to base my plugin vaguely off its source code
* Once I get things working properly, I will make it more unique.
* If there is any issue at all with me doing this, please let me know,
* Thanks
*/

include_once('Lasercommerce_Abstract_Child.php');

class Lasercommerce_Visibility extends Lasercommerce_Abstract_Child
{
    private $_class = "LC_VI_";

    const TERM_RESTRICTIONS_KEY = 'lc_term_restrictions';
    const TERM_RESTRICTIONS_DEFAULT = '';
    const TERM_RESTRICTIONS_STR_DELIM = '|';
    const CONTROLLED_TAXONOMIES_KEY = 'lc_controlled_taxonomies';
    const CONTROLLED_TAXONOMIES_DEFAULT = '[]';
    const ADMINISTRATOR_ACCESS_OVERRIDE_KEY = 'lc_administrator_access';
    const ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT = 1;

    const CACHE_GROUP         = 'Lasercommerce_Visibility';

    private static $tree;
    private static $instance;

    public static function init() {
        if ( self::$instance == null ) {
            self::$instance = new Lasercommerce_Visibility();
        }
    }

    public static function instance() {
        if ( self::$instance == null ) {
            self::init();
        }

        return self::$instance;
    }


    function __construct()
    {
        parent::__construct();

        self::$tree = Lasercommerce_Tier_Tree::instance();

        add_action( 'init', array( __CLASS__, 'wp_init' ), 999);

        // add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'woocommerce_is_purchasable' ), 10, 2 );

    }

    public static function wp_init() {
        // Control terms requested through get_terms().
        add_filter( 'list_terms_exclusions', array( __CLASS__, 'list_terms_exclusions' ), 10, 3 );

        // Post filters:

        // Exclude posts related to restricted taxonomy terms.
        add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );

        // Page taxonomy access restrictions (for possible custom taxonomies).
        add_filter( 'get_pages', array( __CLASS__, 'get_pages' ) );

        // Post taxonomy access restrictions.
        if ( apply_filters( 'groups_restrict_categories_filter_the_posts', false ) ) {
            add_filter( 'the_posts', array( __CLASS__, 'the_posts' ), 1, 2 );
        }

        // Filter excerpts.
        add_filter( 'get_the_excerpt', array( __CLASS__, 'get_the_excerpt' ) );

        // Filter contents.
        add_filter( 'the_content', array( __CLASS__, 'the_content' ) );

        // Controls permission to edit or delete posts.
        // add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
    }

    public static function tier_ids_satisfy_requirement( $tier_ids, $required_tier_ids ){
        if($tier_ids and $required_tier_ids){
            foreach ($required_tier_ids as $required_tier_id) {
                foreach ($tier_ids as $tier_id) {
                    if (strtolower($tier_id) == strtolower($required_tier_id)){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns true if the user can access the term.
     *
     * @param int $user_id
     * @param int $term_id
     * @return boolean
     */
    public static function user_can_read_term( $user_id, $term_id ) {
        $restricted = false;
        if(self::controls_term($term_id)){
            $term_tierIDs = self::get_term_read_tiers($term_id);

            if($term_tierIDs){
                $restricted = true;
                $user_tierIDs = array();
                if(isset(self::$tree)){
                    $user_tierIDs = self::$tree->getVisibleTierIDs();
                }
                if(self::tier_ids_satisfy_requirement($user_tierIDs, $term_tierIDs)){
                    $restricted = False;
                }
            }
        }
        return !$restricted;
    }

    /**
     * Returns all term IDs that the user is not allowed to read.
     *
     * @param int $user_id
     * @return array of int with term IDs
     */
    public static function get_user_restricted_term_ids( $user_id ) {
        //TODO: THIS
        return array();
    }

    /**
     * Filters out terms that are restricted.
     *
     * @param string $exclusions
     * @param array $args
     * @param array $taxonomies
     * @return string $exclusions with appended term ID restrictions
     */
    public static function list_terms_exclusions( $exclusions, $args, $taxonomies ) {

        $user_id = get_current_user_id();

        // admin override ?
        if ( $user_id ) {
            // if administrators can override access, don't filter
            if ( get_option( self::ADMINISTRATOR_ACCESS_OVERRIDE_KEY, self::ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT) ) {
                if ( user_can( $user_id, 'administrator' ) ) {
                    return $exclusions;
                }
            }
        }

        $restricted_term_ids = self::get_user_restricted_term_ids( $user_id );
        if ( !empty( $restricted_term_ids ) ) {
            $restricted_term_ids = array_map( 'intval', $restricted_term_ids );
            $restricted_term_ids = implode( ',', $restricted_term_ids );
            $exclusions .= ' AND t.term_id NOT IN (' . $restricted_term_ids . ')';
        }

        return $exclusions;
    }

    /**
     * Filters out posts that the user should not be able to access, based
     * on taxonomy terms with access restrictions.
     *
     * @param string $where current where conditions
     * @return string modified $where
     */
    public static function posts_where( $where ) {

        global $wpdb;

        $user_id = get_current_user_id();

        // admin override ?
        if ( $user_id ) {
            // if administrators can override access, don't filter
            if ( get_option( self::ADMINISTRATOR_ACCESS_OVERRIDE_KEY, self::ADMINISTRATOR_ACCESS_OVERRIDE_DEFAULT) ) {
                if ( user_can( $user_id, 'administrator' ) ) {
                    return $where;
                }
            }
        }

        $restricted_term_ids = self::get_user_restricted_term_ids( $user_id );

        // $restricted_term_ids are terms that the current user is not allowed
        // to access. Any post that belongs to one of those terms should be
        // filtered out.
        // The resulting query should result in getting all posts that are
        // not in ANY of the restricted categories (thus the UNION).
        if ( !empty( $restricted_term_ids ) ) {
            $where .= " AND {$wpdb->posts}.ID NOT IN ";
            $where .= " ( ";
            $union = array();
            foreach( $restricted_term_ids as $term_id ) {
                $union[] = sprintf( " SELECT object_id FROM $wpdb->term_relationships LEFT JOIN $wpdb->term_taxonomy ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id WHERE term_id = %d ", intval( $term_id ) );
            }
            $where .= implode( ' UNION ALL ', $union );
            $where .= " ) ";
        }
        return $where;
    }

    /**
     * Filter pages by their terms' access restrictions. Although pages don't
     * have any terms related by default, this should be included if there
     * are custom taxonomies related to pages.
     *
     * @param array $pages
     * @return array
     */
    public static function get_pages( $pages ) {
        $result = array();
        $user_id = get_current_user_id();
        foreach ( $pages as $page ) {
            if ( self::user_can_read( $page->ID, $user_id ) ) {
                $result[] = $page;
            }
        }
        return $result;
    }

    /**
     * Filter the excerpt by the post's related terms and their access
     * restrictions.
     *
     * @param string $output
     * @return string the original output if access is granted, otherwise ''
     */
    public static function get_the_excerpt( $output ) {
        global $post;
        $result = '';
        // only try to restrict if we have the ID
        if ( isset( $post->ID ) ) {
            if ( self::user_can_read( $post->ID ) ) {
                $result = $output;
            }
        } else {
            $result = $output;
        }
        return $result;
    }

    /**
     * Filters the content by its related terms and their access restrictions.
     *
     * @param string $output
     * @return string the original output if access is granted, otherwise ''
     */
    public static function the_content( $output ) {
        global $post;
        $result = '';
        // only try to restrict if we have the ID
        if ( isset( $post->ID ) ) {
            if ( self::user_can_read( $post->ID ) ) {
                $result = $output;
            }
        } else {
            $result = $output;
        }
        return $result;
    }

    /**
     * Returns true if all related terms allow access to the user. A single
     * related term that restricts access will result in false to be returned.
     *
     * @param int $post_id
     * @param int $user_id
     * @return boolean
     */
    public static function user_can_read( $post_id, $user_id = null ) {
        $result = true;
        if ( $user_id === null ) {
            $user_id = get_current_user_id();
        }
        $found = false;
        $maybe_result = wp_cache_get( 'user_can_read' . '_' . $post_id . '_' . $user_id, self::CACHE_GROUP, false, $found );
        if ( $found === false ) {
            foreach( self::get_controlled_taxonomies() as $taxonomy ) {
                $terms = get_the_terms( $post_id, $taxonomy );
                if ( is_array( $terms ) ) {
                    foreach( $terms as $term ) {
                        if ( !self::user_can_read_term( $user_id, $term->term_id ) ) {
                            $result = false;
                            break;
                        }
                    }
                    if ( !$result ) {
                        break;
                    }
                }
            }
            wp_cache_set( 'user_can_read' . '_' . $post_id . '_' . $user_id, $result, self::CACHE_GROUP );
        } else {
            $result = $maybe_result;
        }
        return $result;
    }

    /**
     * Returns taxonomy objects handled by this extension.
     * The lasercommerce_visibility_get_taxonomies_args filter can be used
     * to modify the query which restricts the taxonomies that are handled
     * to those which fulfill public and show_ui are true.
     *
     * @param string $output 'objects' or 'names'
     * @return array of object or string
     */
    public static function get_taxonomies( $output = 'objects' ) {
        return get_taxonomies(
            null,
            // array(
            //     // 'public' => true,
            //     // 'show_ui' => true
            //     'object_type' => array('post', 'product')
            // ),
            $output
        );
    }

    /**
     * Returns true if the term is of a taxonomy that has
     * access restrictions enabled.
     *
     * @param int $term_id
     * @return boolean
     */
    public static function controls_term( $term_id ) {

        global $wpdb;

        $controls_term = wp_cache_get( 'controls_term' . intval( $term_id ), self::CACHE_GROUP );
        if ( $controls_term === false ) {
            $taxonomy = $wpdb->get_var( $wpdb->prepare(
                "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d",
                intval( $term_id )
            ) );
            $result = self::controls_taxonomy( $taxonomy );
            $cached = wp_cache_set( 'controls_term' . intval( $term_id ), $result ? 'yes' : 'no', self::CACHE_GROUP );
        } else {
            $result = ( $controls_term === 'yes' );
        }
        return $result;
    }

    /**
     * Returns true if access restrictions are enabled for the taxonomy.
     *
     * @param string $taxonomy taxonomy name
     * @return boolean
     */
    public static function controls_taxonomy( $taxonomy ) {
        return in_array( $taxonomy, self::get_controlled_taxonomies() );
    }

    /**
     * Returns an array of taxonomy names for which access restrictions are
     * enabled.
     *
     * @return array of string
     */
    public static function get_controlled_taxonomies() {
        // $taxonomies = get_option(self::CONTROLLED_TAXONOMIES_KEY, self::CONTROLLED_TAXONOMIES_DEFAULT);
        $taxonomies = get_option(self::CONTROLLED_TAXONOMIES_KEY);
        return is_array( $taxonomies ) ? $taxonomies : self::get_taxonomies( 'names' );
    }

    /**
     * Determines taxonomies for which access restrictions are enabled.
     *
     * @param array $taxonomies taxonomy names
     */
    public static function set_controlled_taxonomies( $taxonomies ) {
        if ( is_array( $taxonomies ) ) {
            $_taxonomies = array();
            foreach( $taxonomies as $taxonomy ) {
                if ( taxonomy_exists( $taxonomy ) ) {
                    $_taxonomies[] = $taxonomy;
                }
            }
            set_option(self::CONTROLLED_TAXONOMIES_KEY, $_taxonomies);
        }
    }

    public static function validate_tierIDs($tierIDs){
        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."VALIDATE_TIERIDS",
            'args'=>"\$term_id=".serialize($term_id).", \$tierIDs=".serialize($tierIDs)
        ));
        // if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);

        $_tierIDs = array();
        if(isset(self::$tree) and is_array($tierIDs)){
            $all_tierIDs = self::$tree->getTreeTierIDs();
            // if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('all tier_ids: '.serialize($all_tierIDs), $context);
            foreach ($tierIDs as $tierID) {
                if(in_array($tierID, $all_tierIDs)){
                    $_tierIDs[] = $tierID;
                } else {
                    // if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('invalid tier_id: '.serialize($tierID), $context);
                }
            }
        }

        $context['return'] = $_tierIDs;
        // if(LASERCOMMERCE_DEBUG) self::$instance->procedureEnd('', $context);

        return $_tierIDs;
    }

    /**
     * Set the read tiers for the term.
     *
     * @param int $term_id
     * @param array $tiers
     */
    public static function set_term_read_tiers( $term_id, $tierIDs ) {
        $term_id = intval( $term_id );

        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."SET_TERM_READ",
            'args'=>"\$term_id=".serialize($term_id).", \$tierIDs=".serialize($tierIDs)
        ));
        if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);


        if(term_exists($term_id)){
            if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('term_exists', $context);
            $tierIDs = self::validate_tierIDs($tierIDs);
            if($tierIDs){
                if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('tierIDs is_array', $context);
                update_term_meta($term_id, self::TERM_RESTRICTIONS_KEY, json_encode($tierIDs) );
            } else {
                if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('tierIDs is not array'.serialize($tierIDs), $context);
            }

        }
    }

    /**
     * Set the read tiers for the term.
     *
     * @param int $term_id
     * @param string $tierIDs
     */
    public static function set_term_read_tiers_str( $term_id, $tierID_str ) {
        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."SET_TERM_READ_STR",
            'args'=>"\$term_id=".serialize($term_id).", \$tierID_str=".serialize($tierID_str)
        ));
        if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);

        if( is_string($tierID_str) ){
            if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug('tierID_str is_string', $context);
            $tierIDs = explode(self::TERM_RESTRICTIONS_STR_DELIM, $tierID_str);
            if(LASERCOMMERCE_DEBUG) self::$instance->procedureDebug("tierIDs: ".serialize($tierIDs), $context);
            self::set_term_read_tiers( $term_id, $tierIDs);
        }
    }


    /**
     * Returns an array of read tiers for the term.
     *
     * @param int $term_id
     * @return array of string with read tiers for the term, null if the term does not exist
     */
    public static function get_term_read_tiers( $term_id ) {
        $term_id = intval( $term_id );
        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."GET_TERM_READ",
            'args'=>"\$term_id=".serialize($term_id)
        ));
        if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);

        $restrictions = get_term_meta($term_id, self::TERM_RESTRICTIONS_KEY, true);
        if($restrictions){
            $decoded = json_decode($restrictions);
            if($decoded){
                return $decoded;
            } else {
                return json_decode(self::TERM_RESTRICTIONS_DEFAULT);
            }
        }
    }

    /**
     * Returns a string of read tiers for the term.
     *
     * @param int $term_id
     * @return string with read tiers for the term, null if the term does not exist
     */
    public static function get_term_read_tiers_str( $term_id ){
        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."GET_TERM_READ_STR",
            'args'=>"\$term_id=".serialize($term_id)
        ));
        if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);

        $tiers = self::get_term_read_tiers($term_id);
        if($tiers){
            return implode(self::TERM_RESTRICTIONS_STR_DELIM, $tiers);
        } else {
            return "";
        }
    }

    /**
     * Delete the read tiers for the term.
     *
     * @param int $term_id
     */
    public static function delete_term_read_tiers( $term_id ) {
        $term_id = intval( $term_id );

        $context = array_merge(self::$instance->defaultContext, array(
            'caller'=>self::$instance->_class."DEL_TERM_READ",
            'args'=>"\$term_id=".serialize($term_id)
        ));
        if(LASERCOMMERCE_DEBUG) self::$instance->procedureStart('', $context);

        delete_term_meta($term_id, self::TERM_RESTRICTIONS_KEY);
    }
}
