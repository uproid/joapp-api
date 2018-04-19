<?php

class JOAPP_API_Category {

    var $id;          // Integer
    var $slug;        // String
    var $title;       // String
    var $description; // String
    var $parent;      // Integer
    var $post_count;  // Integer
    var $image;       // String
    var $sliders;     // Object
    var $post_view;   // String

    function JOAPP_API_Category($wp_category = null) {
        if ($wp_category) {
            $this->import_wp_object($wp_category);
        }
        do_action("joapp_api_action_category_init",$this);
    }

    function import_wp_object($wp_category) {
        $id = $this->id = (int) $wp_category->term_id;
        $this->slug = $wp_category->slug;
        $this->title = $wp_category->name;
        $this->description = $wp_category->description;
        $this->parent = (int) $wp_category->parent;
        $this->post_count = (int) $wp_category->count;
        $this->image = (String) $wp_category->image;

        $slider = array();
        $slider_str = get_option("joapp_api_taxonomy_$id", "[]");
        $slider = json_decode($slider_str);
        $slider = array_reverse($slider);

        $this->sliders = $slider;
        $this->post_view = get_option("joapp_api_taxonomy_post_view_$id","one_news_large");
    }

}

?>
