<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once('Lasercommerce_Tier_Tree.php');

//LaserCommerce Admin Page
class LaserCommerce_Admin extends WC_Settings_Page{
    public function __construct($optionNamePrefix = 'lasercommerce_') {
        $this->id            = 'lasercommerce';
        $this->label         = __('LaserCommerce', 'lasercommerce');
        $this->optionNamePrefix = $optionNamePrefix;
        global $Lasercommerce_Tier_Tree;
        if( !isset($Lasercommerce_Tier_Tree) ) {
            $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree( $optionNamePrefix );
        }
        
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page' ), 20 );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'nestable_init' ) ); //TODO: check priority is right
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'woocommerce_admin_field_price_tiers', array( $this, 'price_tiers_setting' ) );
        add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
        add_action( 'woocommerce_update_option_price_tiers', array( $this, 'price_tiers_save' ) );
    }   

    public function nestable_init(){
        wp_register_script( 'jquery-nestable-js', plugins_url('/js/jquery.nestable.js', __FILE__), array('jquery'));
        wp_register_style( 'nestable-css', plugins_url('/css/nestable.css', __FILE__));

        wp_enqueue_script( 'jquery-nestable-js' );
        wp_enqueue_style( 'nestable-css' );
    } 
    
    public function get_sections() {
        $sections = array(
            '' => __('Advanced Pricing and Visibility', 'lasercommerce'),
        );
        
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }
    
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
                        'default' => '[{"id":"special_customer","children":[{"id":"wholesale_buyer","children":[{"id":"distributor","children":[{"id":"international_distributor"}]},{"id":"mobile_operator"},{"id":"gym_owner"},{"id":"salon"},{"id":"home_studio"}]}]}]'
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
    
    public function output() {
        global $current_section;
        
        if( !$current_section){ //Advanced Pricing and Visibility
            $settings = $this->get_settings();
        
            WC_Admin_Settings::output_fields($settings );
        }
    }

    public function output_nestable($node, $names) { 
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
                                $this->output_nestable($child, $names); 
                            } ?>
                        </ol>
                    <?php } ?>
                </li>
            <?php 
        }
    }
       
    public function price_tiers_setting( $field ) {
        global $wp_roles;
        global $Lasercommerce_Tier_Tree;
        // if (!isset($Lasercommerce_Tier_Tree)) {
        //     $Lasercommerce_Tier_Tree = new Lasercommerce_Tier_Tree();
        // }
        if ( ! isset( $wp_roles ) )
            $wp_roles = new WP_Roles(); 
        
        $names = $wp_roles->get_names();
        $availableRoles = array_keys($names);
        $usedRoles = $Lasercommerce_Tier_Tree->getRoles();
        $tree = $Lasercommerce_Tier_Tree->getTierTree();
        if(!$usedRoles){
            $unusedRoles = $availableRoles;
        } else {
            $unusedRoles = array_diff($availableRoles, $usedRoles);
        }
        IF(WP_DEBUG) error_log("-> availableRoles: ".serialize($availableRoles));
        IF(WP_DEBUG) error_log("-> tree: ".serialize($tree));
        IF(WP_DEBUG) error_log("-> usedRoles: ".     serialize($usedRoles));
        IF(WP_DEBUG) error_log("-> unusedRoles: ".   serialize($unusedRoles));
        IF(WP_DEBUG) error_log("-> names: ".         serialize($names));
        IF(WP_DEBUG) error_log("-> field: ".         serialize($field));

        if(isset($field['id'])){
        ?>
            <div class="dd" id="nestable-used">
                <?php if( !empty($tree) ){ 
                    ?>
                        <ol class="dd-list">
                    <?php foreach ($tree as $node) {
                        $this->output_nestable($node, $names);
                    } ?>
                        </ol>
                    <?php
                } ?>
            </div>
            <hr>
            <div class="dd" id="nestable-unused">
                <ol class="dd-list">
                    <?php foreach( $unusedRoles as $role ) {
                        ?>
                            <li class="dd-item" data-id="<?php echo $role; ?>">
                                <div class="dd-handle">
                                    <?php echo isset($names[$role])?$names[$role]:$role; ?>
                                </div>
                            </li>
                        <?php
                    } ?>
                </ol>
            </div>
            <input type="text" name="<?php echo esc_attr( $field['id'] ); ?>" id="<?php echo esc_attr( $field['id'] ); ?>"  style="width:100%; max-width:600px" value="">    
            <!-- input id='nestable-unused-output' style="width:100%"-->    

            <script >
                (function ($) {
                    $(document).ready(function()
                    {
                        var updateOutput = function (e)
                        {
                            var list    = e.length ? e : $(e.target),
                                output  = list.data('output');
                            if (window.JSON) {
                                output.val(window.JSON.stringify(list.nestable('serialize')));
                            } else {
                                output.val('JSON browser support required');
                            }
                        };

                        $('#nestable-used').nestable({
                            group: 1
                        })
                        .on('change', updateOutput);
                        $('#nestable-unused').nestable({
                            group: 1
                        })

                        updateOutput($('#nestable-used').data('output', $('#<?php echo esc_attr( $field['id'] ); ?>')));

                        //$('.dd').nestable();
                    });
                }(jQuery));            
            </script>
        <?php }
    }
    
    public function price_tiers_save( $field ){
        if(WP_DEBUG) error_log('updating price tier! field: '.serialize($field).' POST '.serialize($_POST));
        if( isset( $_POST[ $field['id']]) ){
            if(WP_DEBUG) error_log('updating option '.$field['id'].' as '.$_POST[$field['id']]);
            update_option( $field['id'], $_POST[$field['id']]);
        }
    }
    
    public function donationBoxSection(){
        //todo: this
    }

    public function save() {
        global $current_section;
        
        if( !$current_section ) {
            $settings = $this->get_settings();
            
            WC_Admin_Settings::save_fields( $settings );
        }
    }
}
?>