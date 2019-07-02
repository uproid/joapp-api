<?php
/*
  Plugin Name: JOAPP API
  Plugin URI: https://joapp.ir/wordpress
  Description: افزونه ای برای تبدیل سایت های وردپرس به اپلیکیشن در JoApp
  Version: 5.0.0
  Author: SEPAHAN DATA TOOLS Co.
  Author URI: https://bejo.ir/
  Copyright: 2017 joapp.ir & bejo.ir
 */
if (!defined('ABSPATH')) {
    exit();
}

define("JOAPP_API_VERSION", 500);
define("JOAPP_MIN_SUPPORT", 500);
        
$dir = joapp_api_dir();
//add_action('plugins_loaded', 'joapp_api_load_textdomain');
//function joapp_api_load_textdomain() {
//    load_plugin_textdomain('joappapi', false, dirname(plugin_basename(__FILE__)) . '/languages');
//}

@include_once "$dir/singletons/api.php";
@include_once "$dir/singletons/query.php";
@include_once "$dir/singletons/introspector.php";
@include_once "$dir/singletons/response.php";
@include_once "$dir/models/post.php";
@include_once "$dir/models/comment.php";
@include_once "$dir/models/category.php";
@include_once "$dir/models/tag.php";
@include_once "$dir/models/author.php";
@include_once "$dir/models/attachment.php";
//WOOCOMMERCE
@include_once "$dir/models/product.php";

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'joapp_api_add_action_links');
add_filter('plugin_row_meta', 'joapp_api_add_action_meta_links', 10, 4);

function joapp_api_add_action_meta_links($links, $file) {
    if (strpos($file, plugin_basename(__FILE__)) !== false) {
        $plugin_data = get_plugin_data(__FILE__);
        $slug = 'joapp-api';
        $new_links = array(
            'details' => sprintf('<a href="%s" class="button thickbox" title="%s">%s</a>', self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=600&amp;height=550'), esc_attr(sprintf(__('More information about %s'), $plugin_data['Name'])), __('Details')),
            'doc' => sprintf('<a href="%s" target="_blank" class="button button-primary" title="%s">%s</a>', "https://joapp.ir/wordpress", 'آموزش ها', 'آموزش')
        );

        $links = array_merge($links, $new_links);
    }

    return $links;
}

function joapp_api_add_action_links($links) {
    $mylinks = array(
        '<a class="button button-primary" href="' . get_admin_url(null, 'admin.php?page=joapp-api') . '">تنظیمات</a>'
    );
    return array_merge($links, $mylinks);
}

function joapp_api_init() {
    $wp_actions = null;
    global $joapp_api;
    if (phpversion() < 5) {
        add_action('admin_notices', 'joapp_api_php_version_warning');
        return;
    }
    if (!class_exists('JOAPP_API')) {
        add_action('admin_notices', 'joapp_api_class_warning');
        return;
    }
    add_filter('rewrite_rules_array', 'joapp_api_rewrites');

    $joapp_api = new JOAPP_API();
}

function joapp_api_php_version_warning() {
    echo "<div id=\"joapp-api-warning\" class=\"updated fade\"><p>نسخه PHP باید بیشتر از 5.0 باشد</p></div>";
}

function joapp_api_class_warning() {
    echo "<div id=\"joapp-api-warning\" class=\"updated fade\"><p>خطا در بررسی فایل های افزونه</p></div>";
}

function joapp_api_activation() {
    // Add the rewrite rule on activation
    global $wp_rewrite;
    add_filter('rewrite_rules_array', 'joapp_api_rewrites');

    $wp_rewrite->flush_rules();
}

function joapp_api_cyb_activation_redirect($plugin) {
    if ($plugin == plugin_basename(__FILE__)) {
        //exit(wp_redirect(get_admin_url(null, 'options-general.php?page=joapp-api')));
    }
}

function joapp_api_deactivation() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function joapp_api_rewrites($wp_rules) {
    $base = get_option('joapp_api_base', 'joapp_api');
    if (empty($base)) {
        return $wp_rules;
    }
    $joapp_api_rules = array(
        "$base\$" => 'index.php?joapp=info',
        "$base/(.+)\$" => 'index.php?joapp=$matches[1]'
    );
    return array_merge($joapp_api_rules, $wp_rules);
}

function joapp_api_dir() {
    if (defined('JOAPP_API_DIR') && file_exists(JOAPP_API_DIR)) {
        return JOAPP_API_DIR;
    } else {
        return dirname(__FILE__);
    }
}

function get_version_joapp_api() {
    if (!function_exists("get_plugin_data")) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];
    return $plugin_version;
}

add_action('init', 'joapp_api_init');
if (function_exists('is_admin') && is_admin()) {

    include( plugin_dir_path(__FILE__) . 'custom_feilds_joapp.php');
    include( plugin_dir_path(__FILE__) . 'custom_feilds_intent_joapp.php');
    include( plugin_dir_path(__FILE__) . 'custom_woo_category.php');
    include( plugin_dir_path(__FILE__) . 'custom_col_page_joapp.php');
    include( plugin_dir_path(__FILE__) . 'joapp_api_update.php');
    include( plugin_dir_path(__FILE__) . 'api_ajax.php');
}

include( plugin_dir_path(__FILE__) . 'payment/pay.php');
register_activation_hook("$dir/joapp-api.php", 'joapp_api_activation');
register_deactivation_hook("$dir/joapp-api.php", 'joapp_api_deactivation');
add_action('activated_plugin', 'joapp_api_cyb_activation_redirect');
?>
