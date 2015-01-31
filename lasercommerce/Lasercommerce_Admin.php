<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Lasercommerce_Tier_Tree.php');


class LaserCommerce_Admin extends WC_Settings_Page{
    /**
     * Constructs the settings page class, hooking into woocommerce settings api
     * 
     * @param $optionNamePrefix 
     */
    public function __construct($optionNamePrefix = 'lc_') {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->optionNamePrefix = $optionNamePrefix;
        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $optionNamePrefix );
        }
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        // add_action( 'admin_enqueue_scripts', array($this, 'nestable_init'));
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'nestable_init' ) ); //TODO: check priority is right
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_admin_field_price_tiers', array( $this, 'price_tiers_setting' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        add_action( 'woocommerce_update_option_price_tiers', array( $this, 'price_tiers_save' ) );
    }   

    /**
     * Initializes the nestable jquery functions responsible for the drag and drop 
     * functionality in the tier tree interface
     */
    public function nestable_init(){
        wp_register_script( 'jquery-nestable-js', plugins_url('/js/jquery.nestable.js', __FILE__), array('jquery'));
        wp_register_style( 'nestable-css', plugins_url('/css/nestable.css', __FILE__));

        wp_enqueue_script( 'jquery-nestable-js' );
        wp_enqueue_style( 'nestable-css' );
    } 
    
    /**
     * Overrides the get_sections() method of the WC_Settings Api
     * used by the api to generate the sections of the pages
     */
    public function get_sections() {
        $sections = array(
            '' => __('Advanced Pricing and Visibility', 'lasercommerce'),
        );
        
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }
    
    /**
     * Returns the appropriate settings array for the given section
     *
     * @param string $current_section 
     */
    public function get_settings( $current_section = "" ) {
        if( !$current_section ) { //Advanced Pricing and Visibility
            return apply_filters( 
                'woocommerce_lasercommerce_pricing_visibility_settings', 
                array(
                    array( 
                        'title' => __( 'LaserCommerce Advanced Pricing and Visibility Options', 'lasercommerce' ),
                        'id'    => $this->optionNamePrefix . 'options',
                        'type'  => 'title',
                    ),
                    array(
                        'name'  => 'Price Tiers',
                        'type'  => 'price_tiers',
                        'id'    => $this->optionNamePrefix . 'price_tiers',
                        // 'default' => '[{"id":"special_customer","children":[{"id":"wholesale_buyer","children":[{"id":"distributor","children":[{"id":"international_distributor"}]},{"id":"mobile_operator"},{"id":"gym_owner"},{"id":"salon"},{"id":"home_studio"}]}]}]'
                    ),
                    array(
                        'type' => 'sectionend',
                        'id' => $this->optionNamePrefix . 'options'
                    )
                )
            );
        }
        //TODO: sanitize price tiers
        //TODO: enter price tiers in table
    }    
    
    /**
     * Used bt the WC_Settings_Api to output the fields in the settings array
     */
    public function output() {
        global $current_section;
        
        if( !$current_section){ //Advanced Pricing and Visibility
            $settings = $this->get_settings();
        
            WC_Admin_Settings::output_fields($settings );
        }
    }

    /**
     * Used by price_tiers_setting to recursively output the html for nestable fields 
     * this is the drag and drop field used in the configuration of the price tier
     * 
     * @param array $node The node of the tree to be displayed
     * @param array $names The array containing the mapping of roles to tier names
     */
    public function output_nestable_li($node, $names) { 
        if(isset($node['id'])) {
            ?>
                <li class="dd-item" data-id="<?php echo $node['id']; ?>">
                    <div class="dd-handle">
                        <?php echo isset($names[$node['id']])?$names[$node['id']]:$node['id']; ?>
                    </div>
                    <?php if(isset($node['children'])) { 
                    ?>
                        <ol class="dd-list">
                            <?php foreach( $node['children'] as $child ) {
                                $this->output_nestable_li($child, $names); 
                            } ?>
                        </ol>
                    <?php } ?>
                </li>
            <?php 
        }
    }

    public function output_nestable($tree, $names, $id){ ?>
        <div class="dd" id="<?php echo $id; ?>">
            <?php if( !empty($tree) ){ 
                echo '<ol class="dd-list">';
                foreach ($tree as $node) {
                    $this->output_nestable_li($node, $names);
                } 
                echo '</ol>';
            } else {
                echo '<div class="dd-empty"></div>';
            } ?>
        </div>
    <?php }
       
    /**
     * Used by the WC_Settings to output the price tiers setting html
     * note: there are lots of debugging items still left
     *
     * @param array $field Array specifying the field that is being used
     */
    public function price_tiers_setting( $field ) {
        global $Lasercommerce_Tier_Tree;
        // if (!isset($Lasercommerce_Tier_Tree)) {
        //     $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree();
        // }
        $names = $Lasercommerce_Tier_Tree->getNames();
        $availableRoles = array_keys($names);
        $usedRoles = $Lasercommerce_Tier_Tree->getRoles();
        $tree = $Lasercommerce_Tier_Tree->getTierTree();
        if(!$usedRoles){
            $unusedRoles = $availableRoles;
        } else {
            $unusedRoles = array_diff($availableRoles, $usedRoles);
        }
        IF(WP_DEBUG) error_log("-> availableRoles: ".serialize($availableRoles));
        IF(WP_DEBUG) error_log("-> tree: ".          serialize($tree));
        IF(WP_DEBUG) error_log("-> usedRoles: ".     serialize($usedRoles));
        IF(WP_DEBUG) error_log("-> unusedRoles: ".   serialize($unusedRoles));
        IF(WP_DEBUG) error_log("-> names: ".         serialize($names));
        IF(WP_DEBUG) error_log("-> field: ".         serialize($field));

        if(isset($field['id'])){
        ?>
            <h2>The Tier Tree</h2>
            <p>Drag classes to here from "Available User Roles"</p>
            <?php $this->output_nestable($tree, $names, 'nestable-used');?>
            <h2>Availible User Roles</h2>
            <div class="dd" id="nestable-unused">
                <ol class="dd-list">
                    <?php foreach( $unusedRoles as $role ) { ?>
                        <li class="dd-item" data-id="<?php echo $role; ?>">
                            <div class="dd-handle">
                                <?php echo isset($names[$role])?$names[$role]:$role; ?>
                            </div>
                        </li>
                    <?php } ?>
                </ol>
            </div>
            <input type="" name="<?php echo esc_attr( $field['id'] ); ?>" class="lc_admin_tier_tree" id="<?php echo esc_attr( $field['id'] ); ?>"  style="width:100%; max-width:600px" value="">    
        <?php }
    }
    /**
     * used by WC_Settings API to save the price tiers field from $POST data
     * to the options in the database
     *
     * @param array $field The array specifying the field being saved
     */
    public function price_tiers_save( $field ){
        if(WP_DEBUG) error_log('updating price tier! field: '.serialize($field).' POST '.serialize($_POST));
        if( isset( $_POST[ $field['id']]) ){
            if(WP_DEBUG) error_log('updating option '.$field['id'].' as '.$_POST[$field['id']]);
            update_option( $field['id'], $_POST[$field['id']]);
        }
    }
    
    /** (Unfinished) Outputs a donate box section in the admin interface
     */
    public function donationBoxSection(){
        //todo: this
    }

    /**
     * Used by WC_Settings API to save all of the fields in the current section
      */
    public function save() {
        global $current_section;
        
        if( !$current_section ) {
            $settings = $this->get_settings();
            
            WC_Admin_Settings::save_fields( $settings );
        }
    }
}
?>