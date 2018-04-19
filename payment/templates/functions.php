<?php

add_action('shop_isle_page', 'joapp_shop_isle_page_content', 20);
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);

function joapp_shop_isle_page_content() {
    the_content();
}

if (class_exists('woocommerce')) {
    add_filter('woocommerce_product_is_visible', 'joapp_product_invisible');

    function joapp_product_invisible() {
        return false;
    }

    add_filter('woocommerce_register_post_type_product', 'joapp_hide_product_page', 12, 1);

    function joapp_hide_product_page($args) {
        $args["publicly_queryable"] = false;
        $args["public"] = false;
        return $args;
    }

    add_filter('woocommerce_account_menu_items', 'joapp_custom_my_account_menu_items');

    function joapp_custom_my_account_menu_items($items) {
        return array();
    }

    function joapp_wc_empty_cart_redirect_url() {
        return '#';
    }

    add_filter('woocommerce_return_to_shop_redirect', 'joapp_wc_empty_cart_redirect_url');
}


?>