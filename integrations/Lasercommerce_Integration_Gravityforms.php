<?php
class Lasercommerce_Integration_Gravityforms extends Lasercommerce_Abstract_Child {
    private $_class = "LC_GF_";

    private static $instance;
    public static $integration_target = 'GFForms';

    public static function init() {
        if ( self::$instance == null ) {
            self::$instance = new Lasercommerce_Integration_Gravityforms();
        }
    }

    public static function instance() {
        if ( self::$instance == null ) {
            self::init();
        }

        return self::$instance;
    }

    protected $plugin;

    function __construct(){
        parent::__construct();
        $this->plugin = Lasercommerce_Plugin::instance();
        $this->tree = Lasercommerce_Tier_Tree::instance();
        add_action( 'init', array( &$this, 'wp_init' ), 999);
    }

    public function detect_target(){
        return class_exists(self::$integration_target);
    }

    public function wp_init() {
        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."WP_INIT",
        ));
        if(LASERCOMMERCE_DP_DEBUG) $this->procedureStart('', $context);
    }


    public function gform_user_tier_string_paramter( $value=null ){
        $_procedure = $this->_class."GFORM_TIER_STRING_PARAM: ";

        $tierString = $this->tree->serializeVisibleTiers();
        if(LASERCOMMERCE_GF_DEBUG) {error_log($_procedure."tierString: $tierString");}

        return $tierString;
    }

    public function gform_user_is_wholesale($value=null){
        $_procedure = $this->_class."GFORM_IS_WHOLESALE: ";

        $isWholesale = $this->tree->tierNameVisible('Wholesale');
        if(LASERCOMMERCE_GF_DEBUG) {error_log($_procedure."isWholesale: $isWholesale");}

        if($isWholesale){
            return "YES";
        } else {
            return "NO";
        }
    }

    public function gform_user_is_logged_in($value=null){
        $_procedure = $this->_class."GFORM_IS_AUTH: ";

        $isLoggedIn = is_user_logged_in();
        if(LASERCOMMERCE_GF_DEBUG) {error_log($_procedure."isLoggedIn: $isLoggedIn");}

        if($isLoggedIn){
            return "YES";
        } else {
            return "NO";
        }
    }

    public function gf_setup_custom_merge_tag($tag, $value, $label = null){
        if(!$label) $label = $tag;
        add_filter(
            'gform_custom_merge_tags',
            function($merge_tags, $form_id, $fields, $elemend_id) use ($tag, $label, $value) {
                $merge_tags[] = array('label' => $label, 'tag' => "{"."$tag"."}");

                return $merge_tags;
            },
            10,
            4
        );
        add_filter(
            'gform_replace_merge_tags',
            function($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format) use ($tag, $value){
                $text = str_replace('{'.$tag.'}', $value, $text);

                return $text;
            },
            10,
            7
        );
        add_filter(
            'gform_field_content',
            function($field_content, $field, $value, $lead_id, $form_id) use ($tag, $value) {
                if (strpos($field_content, '{'.$tag.'}') !== false) {
                    $field_content = str_replace('{'.$tag.'}', $value, $field_content);
                }

                return $field_content;
            },
            10,
            5
        );
    }

    public function gf_setup_dynamic_parameter($tag, $_value){
        $_procedure = $this->_class."GFORM_SET_PARAM: ";

        if(LASERCOMMERCE_GF_DEBUG) error_log($_procedure."setting $tag to $_value");

        add_filter(
            "gform_field_value_$tag",
            function($value) use($_value){
                return $_value;
            },
            10,
            1
        );
    }

    public function gf_setup_dynamic_meta_parameter($metaKey){
        // For a given key, configure Gravity forms to save the key to the users profile
        $user_id = get_current_user_id();
        $meta_value = get_user_meta($user_id, $metaKey, true);
        $this->gf_setup_dynamic_parameter("user_$metaKey", $meta_value);
    }

    public function gf_setup_lc_tags(){
        // Sets up Gravity Forms to save certain fields to the user profile when submitting a form
        $this->gf_setup_custom_merge_tag('user_is_wholesale', $this->gform_user_is_wholesale(), 'Is Wholesale');
        $this->gf_setup_custom_merge_tag('user_tier_string', $this->gform_user_tier_string_paramter(), 'User Tier String');
        $this->gf_setup_custom_merge_tag('user_is_logged_in', $this->gform_user_is_logged_in(), 'User Logged In');
        // $this->gf_setup_dynamic_parameter('param_test', 'it works');
        $this->gf_setup_dynamic_parameter('user_tier_string', $this->gform_user_tier_string_paramter());
        // TODO: make this configurable in admin interface
        foreach (array('pref_method', 'business_type', 'interest_level', 'how_hear_about', 'tans_per_wk') as $key) {
            $this->gf_setup_dynamic_meta_parameter($key);
        }
    }

    public function addActionsAndFilters() {
        // must be called after wp_init to detect other plugins

        $context = array_merge($this->defaultContext, array(
            'caller'=>$this->_class."ADD_ACTIONS_FILTERS",
        ));
        if(LASERCOMMERCE_GF_DEBUG) $this->procedureStart('', $context);

        if(!$this->detect_target()){
            if(LASERCOMMERCE_GF_DEBUG) $this->procedureDebug('could not detect target', $context);
            return;
        }

        add_filter('gform_field_value_user_tier_string', array(&$this, 'gform_user_tier_string_paramter'), 0, 1);
        $this->gf_setup_lc_tags();
    }
}
?>
