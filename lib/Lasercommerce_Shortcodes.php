<?php

//product category shortcodes
//dynamically generate woocommerce product category shortcode strings

class Lasercommerce_Shortcodes extends Lasercommerce_Abstract_Child
{
    const _CLASS = "LC_SC_";

    function __construct(){
        parent::__construct();
        $this->tree = $this->plugin->tree;
        $this->visibility = $this->plugin->visibility;
        add_action( 'init', array( &$this, 'wp_init' ), 999);
    }

    public function tier_unrestricted_product_categories_shortcode( $atts, $content="" ){
        $atts = shortcode_atts( array(
            // 'number'    => null,
            'orderby' => 'name',
            'order' => 'ASC',
            // 'columns' => '4',
            'hide_empty' => 1,
            'parent' => '',
            'ids' => '',
            'show' => 'unrestricted'
        ), $atts);

        if(in_array($atts['show'], array('restricted', 'unrestricted', 'both'))){
            $show = $atts['show'];
        } else {
            $show = 'unrestricted';
        }

        if ( isset( $atts['ids'] ) ) {
            $ids = explode( ',', $atts['ids'] );
            $ids = array_map( 'trim', $ids );
        } else {
            $ids = array();
        }

        $hide_empty = ( $atts['hide_empty'] == true || $atts['hide_empty'] == 1 ) ? 1 : 0;

        $term_args = array(
            'orderby'   => $atts['orderby'],
            'order'     => $atts['order'],
            'hide_empty'=> $hide_empty,
            'include'   => $ids,
            'pad_counts'=> true,
            'child_of'  => $atts['parent']  
        );

        $product_categories = get_terms('product_cat', $term_args);

        if ( '' !== $atts['parent'] ) {
            $product_categories = wp_list_filter( $product_categories, array( 'parent' => $atts['parent'] ) );
        }

        $restricted_term_ids = array();
        $unrestricted_term_ids = array();

        $out = '';

        foreach ($product_categories as $product_category) {
            $term_id = $product_category->term_id;
            if(class_exists('Lasercommerce_Visibility')){
                // $out .= $term_id . ": " . Lasercommerce_Visibility::get_term_read_tiers_str($term_id) . '<br/>';
                if(!Lasercommerce_Visibility::user_can_read_term(get_current_user_id(), $term_id)){
                    array_push($restricted_term_ids, $term_id);
                    continue;
                }
            }
            array_push( $unrestricted_term_ids, $term_id);
        }

        $restricted_term_id_string = implode(',', $restricted_term_ids);
        $unrestricted_term_id_string = implode(',', $unrestricted_term_ids);

        // $out .= "restricted: " . $restricted_term_id_string . "<br/>";
        // $out .= "unrestricted: " . $unrestricted_term_id_string . "<br/>";

        //update args with ids
        if($show == 'unrestricted'){
            $atts['ids'] = $unrestricted_term_id_string;
            $out .= WC_Shortcodes::product_categories( $atts );
        } elseif($show == 'restricted'){
            $atts['ids'] = $restricted_term_id_string;
            $out .= WC_Shortcodes::product_categories( $atts );
        } else{
            if($unrestricted_term_id_string){
                $atts['ids'] = $unrestricted_term_id_string;
                $unrestricted = WC_Shortcodes::product_categories( $atts );
                $out .= $unrestricted;
            }
            if($restricted_term_id_string){
                $atts['ids'] = $restricted_term_id_string;
                $restricted = WC_Shortcodes::product_categories( $atts );
                $out .= do_shortcode($content) . $restricted;
            }
        }

        return $out;

        // return "RESTRICTED: ". implode('/', $restricted_terms) . "<br/>UNRESTRICTED: " . implode('/', $unrestricted_terms);

        // return WC_Shortcodes::product_categories( $agrs );
    }

    public function proof_of_concept_shortcode($args, $content=""){
        return WC_Shortcodes::product_categories( array( 'parent' => '0'));
        // return do_shortcode( '[button]' . $this->tree->_class . $content . '[/button]' );
    }

    public function wp_init() {
        add_shortcode('lasercommerce_shortcode_test', array(&$this, 'proof_of_concept_shortcode'));
        add_shortcode('tier_unrestricted_product_categories', array(&$this, 'tier_unrestricted_product_categories_shortcode'));
    }

}