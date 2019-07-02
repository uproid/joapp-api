<?php

/*
  Controller name: Core
  Controller description: Basic introspection methods
 */

class JOAPP_API_Core_Controller {

    public function info() {
        global $joapp_api;
        $php = '';
        if (!empty($joapp_api->query->controller)) {
            return $joapp_api->controller_info($joapp_api->query->controller);
        } else {
            $dir = joapp_api_dir();
            if (file_exists("$dir/joapp-api.php")) {
                $php = file_get_contents("$dir/joapp-api.php");
            } else {
                $dir = dirname($dir);
                if (file_exists("$dir/joapp-api.php")) {
                    $php = file_get_contents("$dir/joapp-api.php");
                }
            }
            if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
                $version = trim($matches[1]);
            } else {
                $version = '(Unknown)';
            }
            $active_controllers = explode(',', get_option('joapp_api_controllers', 'core'));
            $controllers = array_intersect($joapp_api->get_controllers(), $active_controllers);
            return array(
                'joapp_api_version' => $version,
                'controllers' => array_values($controllers)
            );
        }
    }

    public function get_recent_posts() {
        global $joapp_api;
        $posts = $joapp_api->introspector->get_posts();
        return $this->posts_result($posts);
    }

    public function get_posts() {
        global $joapp_api, $joapp_result;
        $url = parse_url($_SERVER['REQUEST_URI']);
        $defaults = array(
            'ignore_sticky_posts' => true
        );
        $query = wp_parse_args($url['query']);
        unset($query['joapp']);
        unset($query['post_status']);

        $query = array_merge($defaults, $query);
        $posts = $joapp_api->introspector->get_posts($query);
        $joapp_result = $this->posts_result($posts);
        do_action("joapp_api_action_get_posts");
        return $joapp_result;
    }

    private function get_comment_children(&$all_joapp, $post_id, $comment_id, &$index = -1) {

        $args = array(
            'status' => 'approve',
            'post_id' => $post_id,
            'parent' => $comment_id,
            'orderby' => 'comment_ID',
            'order' => 'ASC'
        );
        $all_wp = get_comments($args);

        foreach ($all_wp as $v) {
            $index ++;
            $c = new JOAPP_API_Comment($v);
            $c->parent_position = (int) ($c->parent == 0 ? -1 : $index - 1);
            $all_joapp[] = $c;
            $this->get_comment_children($all_joapp, $post_id, $v->comment_ID, $index);
        }
    }

    public function get_comment() {
        global $joapp_api, $joapp_result;
        extract($joapp_api->query->get(array('post_id', 'page')));

        $all_joapp = array();
        $this->get_comment_children($all_joapp, $post_id, 0);
        $post = array('comments' => $all_joapp);
        $joapp_result = array(
            'post' => $post,
            'count' => (int) count($all_joapp)
        );
        do_action("joapp_api_action_get_comment");
        return $joapp_result;
    }

    public function get_post() {

        global $joapp_api, $post, $joapp_result;

        $post = $joapp_api->introspector->get_current_post();

        if ($post) {
            $previous = get_adjacent_post(false, '', true);
            $next = get_adjacent_post(false, '', false);
            $joapp_result = array(
                'post' => new JOAPP_API_Post($post)
            );
            if ($previous) {
                $joapp_result['previous_url'] = get_permalink($previous->ID);
            }
            if ($next) {
                $joapp_result['next_url'] = get_permalink($next->ID);
            }
            do_action("joapp_api_action_get_post");
            return $joapp_result;
        } else {
            $joapp_api->error("Not found.");
        }
    }

    public function get_about() {
        global $joapp_result;
        $content = get_option("joapp_api_about", "قبل از انتشار برنامه خودتان بخش درباره ما را ویرایش کنید و نماد های اعتماد خود را در این بخش به همراه شبکه های اجتماعی لینک دهید.");
        $content = html_entity_decode($content);
        $post = new JOAPP_API_Post();
        $post->content = $content;
        $post->title = get_bloginfo('name');
        $post->date = "";
        $post->id = (int) -1;
        $post->type = "about";
        $post->url = get_bloginfo('url');
        $post->status = "publish";
        $post->slug = "";
        $post->title_plain = $post->title;
        $post->comment_status = "close";
        $post->comment_count = (int) 0;
        $post->custom_fields = array();
        $post->attachments = array();
        $post->comments = array();
        $post->thumbnail = get_option("joapp_api_about_image", "");
        foreach ($post as $key => $value) {
            if (is_null($value))
                unset($post->$key);
        }
        $joapp_result = array('post' => $post);
        do_action("joapp_api_action_get_about");
        return $joapp_result;
    }

    function get_register_link() {
        global $joapp_api;
        $joapp_api->error_details("register_link", $joapp_api->getRegisterLink());
    }

    function set_user_nickname() {
        global $joapp_api;

        extract($joapp_api->query->get(array('user', 'pass')));
        if ($user) {

            if (!wp_login($user, $pass)) {
                $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
            }

            $u = get_user_by('login', "$user");

            if (is_null($u->ID)) {
                $u = get_user_by('email', "$user");
            }

            if (is_null($u->ID)) {
                $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
            }
        } else {
            $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
        }

        if (empty($_REQUEST['nickname'])) {
            $joapp_api->error("نام خود را وارد نکرده اید");
        }

        nocache_headers();

        $id = wp_update_user(array('ID' => $u->ID, 'nickname' => $_REQUEST['nickname'], 'display_name' => $_REQUEST['nickname']));

        return array("authors" => array(new JOAPP_API_Author($id)));
    }

    function register_user() {
        global $joapp_api;

        if (!get_option("joapp_api_active_register", false)) {
            $joapp_api->error("اجازه عضویت جدید در سامانه وجود ندارد");
        }

        $user;
        $pass;
        $mail;

        extract($joapp_api->query->get(array('user', 'pass', 'mail')));

        if (!validate_username($user)) {
            $joapp_api->error("شناسه کاربری وارد شده معتبر نیست");
        }

        if (username_exists($user)) {
            $joapp_api->error("شناسه کاربری از قبل وجود دارد");
        }

        if (!is_email($mail)) {
            $joapp_api->error("ایمیل معتبر نیست.");
        }

        if (email_exists($mail)) {
            $joapp_api->error("ایمیل از قبل وجود دارد");
        }

        if (!username_exists($user) && !email_exists($mail)) {
            $user_id = wp_create_user($user, $pass, $mail);
            if ($user_id) {
                $user = new WP_User($user_id);
                $user->set_role('customer');
                global $joapp_result;
                $joapp_result = array("authors" => array(new JOAPP_API_Author($user_id)));
                do_action("joapp_api_action_register_user");
                return $joapp_result;
            } else {
                $joapp_api->error("خطای عضویت در سایت");
            }
        } else
            $joapp_api->error_details("register_link", $joapp_api->getRegisterLink());
    }

    function get_shipping_methods() {
        global $joapp_api, $joapp_result;

        if (!class_exists("WC_Shipping_Zones")) {
            return array(
                'shipping' => array(),
                'payment_place' => array('enabled' => false, 'enable_for_method' => array())
            );
        }

        $default_zone = new WC_Shipping_Zones();
        $zoons = $default_zone->get_zones();

        $m = array();
        foreach ($zoons as $val) {
            foreach ($val['shipping_methods'] as $v) {
                if ($v->enabled !== "yes")
                    continue;
                $res = $v->instance_settings;
                $res['method_id'] = $v->id . ":" . $v->instance_id;
                array_push($m, $res);
            }
        }

        $payment_place = get_option("woocommerce_cod_settings", array());

        $payment_place_res = array(
            'enabled' => (isset($payment_place['enabled']) && $payment_place['enabled'] === "yes"),
            'enable_for_method' => ((is_null($payment_place['enable_for_methods']) || $payment_place['enable_for_methods'] == "") ? array() : $payment_place['enable_for_methods'] )
        );

        if (count($m) == 0) {
            $free_shiping = array(
                "title" => "بدون روش حمل کالا",
                "requires" => "",
                "min_amount" => "0",
                "method_id" => ""
            );
            array_push($m, $free_shiping);
        }

        $joapp_result = array(
            'shipping' => $m,
            'payment_place' => $payment_place_res
        );
        do_action("joapp_api_action_get_shipping_methods");
        return $joapp_result;
    }

    public function get_shipping_methods_city() {
        global $joapp_data;

        $joapp_data['country'] = isset($_REQUEST['country']) ? $_REQUEST['country'] : "IR";
        if (isset($_REQUEST['state']))
            $joapp_data['state'] = $_REQUEST['state'];
        if (isset($_REQUEST['postcode']))
            $joapp_data['postcode'] = $_REQUEST['postcode'];
        if (isset($_REQUEST['city']))
            $joapp_data['city'] = $_REQUEST['city'];

        do_action("joapp_api_action_data_shipping_methods_city");

        if (count($joapp_data) <= 1) {
            return $this->get_shipping_methods();
        }

        $package['destination'] = $joapp_data;

        $zoons = WC_Shipping_Zones::get_zone_matching_package($package);

        $payment_place = get_option("woocommerce_cod_settings", array());
        $payment_place_res = array(
            'enabled' => (isset($payment_place['enabled']) && $payment_place['enabled'] === "yes"),
            'enable_for_method' => ((is_null($payment_place['enable_for_methods']) || $payment_place['enable_for_methods'] == "") ? array() : $payment_place['enable_for_methods'] )
        );

        $m = array();
        foreach ($zoons->get_shipping_methods() as $v) {
            if ($v->enabled !== "yes")
                continue;
            $res = $v->instance_settings;
            $res['method_id'] = $v->id . ":" . $v->instance_id;
            array_push($m, $res);
        }

        if (count($m) == 0) {
            $free_shiping = array(
                "title" => "بدون روش حمل کالا",
                "requires" => "",
                "min_amount" => "0",
                "method_id" => ""
            );
            array_push($m, $free_shiping);
        }
        global $joapp_result;
        $joapp_result = array(
            'shipping' => $m,
            'payment_place' => $payment_place_res
        );
        do_action("joapp_api_action_get_shipping_methods");
        return $joapp_result;
    }

    function get_woo_categories() {
        global $joapp_api, $joapp_result;
        $args = null;
        if (!empty($joapp_api->query->parent)) {
            $args = array(
                'parent' => $joapp_api->query->parent
            );
        }

        $slider = array();

        $slider_str = get_option("joapp_api_woo_image_slider", "[]");
        $slider = json_decode($slider_str);
        $slider = array_reverse($slider);

        $tags_str = get_option("joapp_api_tags", "[]");
        $tags = json_decode($tags_str);

        $menus_str = get_option("joapp_api_menus", "[]");
        $menus = json_decode($menus_str);

        $categories = $joapp_api->introspector->get_woo_categories($args);
        $using_woo = TRUE;
        $joapp_result = array(
            'count' => count($categories),
            'using_woocommerce' => (bool) $using_woo,
            'categories' => $categories,
            'sliders' => $slider,
            'tags' => $tags,
            'menus' => $menus
        );
        if ($using_woo) {
            $joapp_result['t'] = $joapp_api->time_stamp('Y-m-d H:i:s');
            $joapp_result['allow_guest'] = (bool) get_option("joapp_api_allow_guest", FALSE);
        }


        do_action("joapp_api_action_get_woo_categories");
        return $joapp_result;
    }

    function get_user() {
        global $joapp_api, $joapp_result;
        extract($joapp_api->query->get(array('user', 'pass')));

        if ($user) {

            if (!wp_login($user, $pass)) {
                $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
            }

            $u = get_user_by('login', "$user");

            if (is_null($u->ID)) {
                $u = get_user_by('email', "$user");
            }

            $author = new JOAPP_API_Author($u->id);
            $joapp_result = array(
                'count' => 1,
                'authors' => array($author)
            );
            do_action("joapp_api_action_get_user");
            return $joapp_result;
        } else {
            $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
        }
    }

    function get_user_shipping() {
        global $joapp_api;
        extract($joapp_api->query->get(array('user', 'pass', 'is_guest', 'orders', 'ja_orders')));
        $ja_orders = isset($_REQUEST['ja_orders']) ? $ja_orders : $orders;
        
        $checked_guest = ($user && $user === "-9898" && $is_guest && $is_guest === '1' && get_option('joapp_api_allow_guest', false));
        if ($user || $checked_guest) {
            if (!$checked_guest) {
                if (!wp_login($user, $pass)) {
                    $joapp_api->error_title_details("login", "Not Logined...", $joapp_api->getRegisterLink());
                }

                $u = get_user_by('login', "$user");

                if (is_null($u->ID)) {
                    $u = get_user_by('email', "$user");
                }

                $shipping = array(
                    'first_name' => get_user_meta($u->ID, 'billing_first_name', true),
                    'last_name' => get_user_meta($u->ID, 'billing_last_name', true),
                    'company' => get_user_meta($u->ID, 'billing_company', true),
                    'address_1' => get_user_meta($u->ID, 'billing_address_1', true),
                    'address_2' => get_user_meta($u->ID, 'billing_address_2', true),
                    'city' => get_user_meta($u->ID, 'billing_city', true),
                    'state' => get_user_meta($u->ID, 'billing_state', true),
                    'postcode' => get_user_meta($u->ID, 'billing_postcode', true),
                    'country' => get_user_meta($u->ID, 'billing_country', true),
                    'email' => get_user_meta($u->ID, 'billing_email', true),
                    'phone' => get_user_meta($u->ID, 'billing_phone', true),
                );
            } else {
                $shipping = array(
                    'first_name' => '',
                    'last_name' => '',
                    'company' => '',
                    'address_1' => '',
                    'address_2' => '',
                    'city' => '',
                    'state' => '',
                    'postcode' => '',
                    'country' => '',
                    'email' => '',
                    'phone' => '',
                );
            }
            $str_shipping_forms = get_option("joapp_api_shipping_forms", '[]');
            $shipping_form = json_decode($str_shipping_forms, TRUE);

            if (!is_array($shipping_form) || count($shipping_form) == 0) {
                $shipping_form = array(
                    "FIRST_NAME",
                    "LAST_NAME",
                    "COMPANY",
                    "ADDRESS_1",
                    "ADDRESS_2",
                    "CITY",
                    "STATE",
                    "POSTCODE",
                    "COUNTRY",
                    "EMAIL",
                    "PHONE",
                    "NOTE"
                );
            }
            $check_orders = array();

            if ($ja_orders) {
                $orders_str = base64_decode($ja_orders);
                $arr_orders = json_decode($orders_str, TRUE);
                $arr_orders_clean = array();

                foreach ($arr_orders as $ord) {
                    if (!isset($arr_orders_clean[$ord['product_id']])) {
                        $arr_orders_clean[$ord['product_id']]['quantity'] = $ord['quantity'];
                    } else {
                        $arr_orders_clean[$ord['product_id']]['quantity'] += $ord['quantity'];
                    }
                }

                foreach ($arr_orders_clean as $id_order => $qu_order) {
                    $msg = "";
                    $o = wc_get_product($id_order);
                    if ($o) {
                        $in_stock = TRUE;
                        if (method_exists($o, "is_in_stock"))
                            $in_stock = $o->is_in_stock();
                        else
                            $in_stock = method_exists($o, "get_stock_status") ? ((bool) ($o->get_stock_status() == "instock")) : false;

                        if (!$in_stock) {
                            $msg .= $o->get_title() . " موجود نیست. ";
                        }

                        if ($o->managing_stock()) {
                            if ($o->get_stock_quantity() < $qu_order['quantity']) {
                                $msg .= $o->get_title() . " به این تعداد در انبار موجود نیست.";
                            }
                        }

                        if (method_exists($o, 'is_sold_individually') && $o->is_sold_individually() && $qu_order['quantity'] > 1) {
                            $msg .= $o->get_title() . " تنها برای فروش تکی میباشد";
                        }
                    } else {
                        $msg .= $id_order . " وجود ندارد. ";
                    }

                    if (strlen($msg) > 0) {
                        $check_orders[] = array(
                            "order_id" => $id_order,
                            "message" => $msg
                        );
                    }
                }
            }

            $states = array();
            $update_states = get_option("joapp_api_update_states", "0");

            if (isset($_REQUEST['update_states']) && $_REQUEST['update_states'] < $update_states && $update_states > 0) {
                include_once __DIR__ . "/../singletons/setting/JoAppState.php";
                $JoAppState = new JoAppState();
                $location['states'] = $JoAppState->getSelectedStates();
                foreach ($location['states'] as &$ss) {
                    $ss['cities'] = (array) $JoAppState->getSelectedCities($ss['code']);
                }
            }

            $location['update_flag'] = $update_states;
            global $joapp_result;

            $joapp_result = array(
                'count' => 1,
                'shipping' => $shipping,
                'shipping_form' => $shipping_form,
                'check_orders' => $check_orders,
                'locations' => $location,
            );
            do_action("joapp_api_action_get_user_shipping");
            return $joapp_result;
        } else {
            $joapp_api->error_title_details("login", "Not Logined...", $joapp_api->getRegisterLink());
        }
    }

    public function get_date_posts() {
        global $joapp_api;
        if ($joapp_api->query->date) {
            $date = preg_replace('/\D/', '', $joapp_api->query->date);
            if (!preg_match('/^\d{4}(\d{2})?(\d{2})?$/', $date)) {
                $joapp_api->error("Specify a date var in one of 'YYYY' or 'YYYY-MM' or 'YYYY-MM-DD' formats.");
            }
            $request = array('year' => substr($date, 0, 4));
            if (strlen($date) > 4) {
                $request['monthnum'] = (int) substr($date, 4, 2);
            }
            if (strlen($date) > 6) {
                $request['day'] = (int) substr($date, 6, 2);
            }
            $posts = $joapp_api->introspector->get_posts($request);
        } else {
            $joapp_api->error("Include 'date' var in your request.");
        }
        return $this->posts_result($posts);
    }

    public function get_category_posts() {
        global $joapp_api;
        $category = $joapp_api->introspector->get_current_category();
        if (!$category) {
            $joapp_api->error("Not found.");
        }


        $posts = $joapp_api->introspector->get_posts(array(
            'cat' => $category->id
        ));

        $result = $this->posts_object_result($posts, $category);
        $result['post_view'] = get_option("joapp_api_taxonomy_post_view_$category->id", "one_product_large");
        return $result;
    }

    public function get_tag_posts() {
        global $joapp_api;
        $tag = $joapp_api->introspector->get_current_tag();
        if (!$tag) {
            $joapp_api->error("Not found.");
        }
        $posts = $joapp_api->introspector->get_posts(array(
            'tag' => $tag->slug
        ));
        return $this->posts_object_result($posts, $tag);
    }

    public function get_author_posts() {
        global $joapp_api;
        $author = $joapp_api->introspector->get_current_author();
        if (!$author) {
            $joapp_api->error("Not found.");
        }
        $posts = $joapp_api->introspector->get_posts(array(
            'author' => $author->id
        ));
        return $this->posts_object_result($posts, $author);
    }

    public function get_search_results() {
        global $joapp_api;
        if ($joapp_api->query->search) {
            $posts = $joapp_api->introspector->get_posts(array(
                's' => $joapp_api->query->search
            ));
        } else {
            $joapp_api->error("Include 'search' var in your request.");
        }
        return $this->posts_result($posts);
    }

    public function get_date_index() {
        global $joapp_api;
        $permalinks = $joapp_api->introspector->get_date_archive_permalinks();
        $tree = $joapp_api->introspector->get_date_archive_tree($permalinks);
        return array(
            'permalinks' => $permalinks,
            'tree' => $tree
        );
    }

    public function get_category_index() {
        global $joapp_api, $joapp_result;
        $args = null;
        if (!empty($joapp_api->query->parent)) {
            $args = array(
                'parent' => $joapp_api->query->parent
            );
        }
        $categories = $joapp_api->introspector->get_categories($args);
        $using_woo = $this->using_woocommerce();

        $menus_str = get_option("joapp_api_menus_wp", "[]");
        $menus = json_decode($menus_str);

        $joapp_result = array(
            'count' => count($categories),
            'using_woocommerce' => (bool) $using_woo,
            'menus' => $menus,
            'categories' => $categories
        );
        if ($using_woo) {
            $joapp_result['t'] = $joapp_api->time_stamp('Y-m-d H:i:s');
        }
        do_action("joapp_api_action_get_category_index");
        return $joapp_result;
    }

    function using_woocommerce() {
        $res = false;

        if (class_exists('WooCommerce')) {
            $res = get_option("joapp_api_active_wooceommerce", false);
        }

        return $res;
    }

    public function get_tag_index() {
        global $joapp_api;
        $tags = $joapp_api->introspector->get_tags();
        return array(
            'count' => count($tags),
            'tags' => $tags
        );
    }

    public function open_pay_woo() {

        global $joapp_api;

        if (isset($_REQUEST['cart_token'])) {
            $this->start_pay_webview();
            exit();
        }

        if (!isset($_REQUEST['d'])) {
            $joapp_api->error("Include 'user' var in your request.");
        }

        $data = base64_decode($_REQUEST['d']);
        $arr = explode("{WOO}", $data);

        $user = $arr[0];
        $pass = $arr[1];
        $url = $arr[2];
        $items = explode("=", $url);
        $item_key = $items[count($items) - 1];
        $oid = wc_get_order_id_by_order_key($item_key);
        $is_guest = get_option('joapp_api_allow_guest', false) && $user === "-9898";
        if ($user) {
            if (!$is_guest) {
                if (!wp_login($user, $pass)) {
                    $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
                }

                $u = get_user_by('login', "$user");

                if (is_null($u->ID)) {

                    if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
                        $joapp_api->error("User Not Found.");
                        exit();
                    }
                    $u = get_user_by('email', "$user");

                    if (is_null($u->ID)) {
                        $joapp_api->error("User Not Found.");
                        exit();
                    }
                }
            }
            global $woocommerce;
            $pay_link = wc_get_checkout_url();

            $order = wc()->order_factory->get_order($oid);
            if ($order->get_status() === 'processing') {
                $pay_endpoint = urlencode(get_option('woocommerce_checkout_order_received_endpoint', 'order-received'));
            } else {
                $pay_endpoint = urlencode(get_option('woocommerce_checkout_pay_endpoint', 'order-pay'));
            }

            wp_clear_auth_cookie();

            if ($user !== "-9898") {
                wp_set_current_user($u->ID);
                wp_set_auth_cookie($u->ID);
            }

            $pay_for_order = "pay_for_order=true&";

            if (count(explode("?", $pay_link)) == 1) {
                if (substr($pay_link, strlen($pay_link) - 1, 1) === '/')
                    $url = "$pay_endpoint/$oid/?{$pay_for_order}key=$item_key";
                else
                    $url = "/$pay_endpoint/$oid/?{$pay_for_order}key=$item_key";
            }
            else {
                if (substr($pay_link, strlen($pay_link) - 1, 1) === '/')
                    $url = "?$pay_endpoint=$oid&{$pay_for_order}key=$item_key";
                else
                    $url = "&$pay_endpoint=$oid&{$pay_for_order}key=$item_key";
            }

            $ur = $pay_link . $url;

            if (get_option("using_joapp_payment", "str_false") == "str_true")
                $ur = add_query_arg('joapp_payment_page', 1, $ur);
            global $joapp_result;
            $joapp_result = $ur;
            do_action("joapp_api_action_open_pay_woo");
            header("location:" . $joapp_result);

            exit("Waiting...");
        } else {
            $joapp_api->error("Include 'user' var in your request.");
        }
    }

    public function get_author_index() {
        global $joapp_api, $joapp_result;
        $authors = $joapp_api->introspector->get_authors();
        $joapp_result = array(
            'count' => count($authors),
            'authors' => array_values($authors)
        );
        do_action("joapp_api_action_get_author_index");
        return $joapp_result;
    }

    public function get_page_index() {
        global $joapp_api;
        $pages = array();
        $post_type = $joapp_api->query->post_type ? $joapp_api->query->post_type : 'page';

        $numberposts = empty($joapp_api->query->count) ? -1 : $joapp_api->query->count;
        $wp_posts = get_posts(array(
            'post_type' => $post_type,
            'post_parent' => 0,
            'order' => 'ASC',
            'orderby' => 'menu_order',
            'numberposts' => $numberposts
        ));
        foreach ($wp_posts as $wp_post) {
            $pages[] = new JOAPP_API_Post($wp_post);
        }
        foreach ($pages as $page) {
            $joapp_api->introspector->attach_child_posts($page);
        }
        global $joapp_result;
        $joapp_result = array(
            'pages' => $pages
        );
        do_action("joapp_api_action_get_page_index");
        return $joapp_result;
    }

    public function get_nonce() {
        global $joapp_api;
        extract($joapp_api->query->get(array('controller', 'method')));
        if ($controller && $method) {
            $controller = strtolower($controller);
            if (!in_array($controller, $joapp_api->get_controllers())) {
                $joapp_api->error("Unknown controller '$controller'.");
            }
            require_once $joapp_api->controller_path($controller);
            if (!method_exists($joapp_api->controller_class($controller), $method)) {
                $joapp_api->error("Unknown method '$method'.");
            }
            $nonce_id = $joapp_api->get_nonce_id($controller, $method);
            return array(
                'controller' => $controller,
                'method' => $method,
                'nonce' => wp_create_nonce($nonce_id)
            );
        } else {
            $joapp_api->error("Include 'controller' and 'method' vars in your request.");
        }
    }

    protected function get_object_posts($object, $id_var, $slug_var) {
        global $joapp_api;
        $object_id = "{$type}_id";
        $object_slug = "{$type}_slug";
        extract($joapp_api->query->get(array('id', 'slug', $object_id, $object_slug)));
        if ($id || $$object_id) {
            if (!$id) {
                $id = $$object_id;
            }
            $posts = $joapp_api->introspector->get_posts(array(
                $id_var => $id
            ));
        } else if ($slug || $$object_slug) {
            if (!$slug) {
                $slug = $$object_slug;
            }
            $posts = $joapp_api->introspector->get_posts(array(
                $slug_var => $slug
            ));
        } else {
            $joapp_api->error("No $type specified. Include 'id' or 'slug' var in your request.");
        }
        return $posts;
    }

    protected function posts_result($posts) {
        global $wp_query, $joapp_result;
        $joapp_result = array(
            'count' => count($posts),
            'count_total' => (int) $wp_query->found_posts,
            'pages' => $wp_query->max_num_pages,
            'posts' => $posts
        );
        do_action("joapp_api_action_posts_result");
        return $joapp_result;
    }

    protected function posts_object_result($posts, $object, $object_key = "") {
        global $wp_query;
        if ($object_key == "")
            $object_key = strtolower(substr(get_class($object), 9));
        return array(
            'count' => count($posts),
            'pages' => (int) $wp_query->max_num_pages,
            $object_key => $object,
            'posts' => $posts
        );
    }

    function get_woo_filters() {
        global $joapp_api, $joapp_result;

        $all_attr = wc_get_attribute_taxonomies();
        $res = array();

        if (isset($_REQUEST['id'])) {
            $saved_filetr = json_decode(get_option("joapp_api_woo_filters", "{}"));
            $id = $_REQUEST['id'];
            if (isset($saved_filetr->$id) && $_REQUEST['id'] != '-1') {
                foreach ($saved_filetr->$id as $attr) {
                    $slug = urlencode(wc_attribute_taxonomy_name_by_id($attr));
                    $name = wc_attribute_label($slug);
                    $id = wc_attribute_taxonomy_id_by_name($slug);

                    $terms = get_terms(array(
                        'taxonomy' => $slug,
                        'hide_empty' => TRUE,
                    ));
                    if (count($terms) > 0) {
                        $res[] = array(
                            "name" => $name,
                            "slug" => "$slug",
                            "id" => (int) $id,
                            "count" => count($terms)
                        );
                    }
                }
                if (count($res) > 0) {
                    $joapp_result = array(
                        'filters' => $res,
                    );
                    do_action("joapp_api_action_get_woo_filters");
                    return $joapp_result;
                }
            }
        }

        foreach ($all_attr as $attr) {
            $c = 0;
            $slug = urlencode("pa_" . $attr->attribute_name);
            $terms = get_terms(array(
                'taxonomy' => $slug,
                'hide_empty' => TRUE,
            ));

            if (count($terms) > 0) {
                $res[] = array(
                    "name" => $attr->attribute_label,
                    "slug" => $slug,
                    "id" => (int) $attr->attribute_id,
                    "count" => count($terms)
                );
            }
        }

        $joapp_result = array(
            'filters' => $res
        );
        do_action("joapp_api_action_get_woo_filters");
        return $joapp_result;
    }

    /*     * ****************************************************************** */

    public function get_woo_one_product() {

        global $joapp_api, $joapp_result;

        $res = $joapp_api->joapp_api_validate(__FUNCTION__);
        $id;
        extract($joapp_api->query->get(array('id')));

        $product = new JOAPP_API_Product($id);
        if ($product->id === -999) {
            $joapp_api->error("محصول در دسترس نیست");
        }

        $joapp_result = array(
            'product' => $product,
            'skey' => $res
        );

        do_action("joapp_api_action_get_woo_one_product");

        return $joapp_result;
    }

    private function create_arg_product($field_name, $field_value, $page) {
        return array(
            'posts_per_page' => 20,
            'offset' => 20 * $page,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'product_cat',
                    'field' => "$field_name",
                    'terms' => "$field_value",
                )
            ),
            'post_type' => 'product',
            'orderby' => array('meta_value' => 'ASC', 'date' => 'DESC'),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                )
            )
        );
    }

    public function get_woo_one_products_category() {

        global $joapp_api, $joapp_result;
        $slug;
        $page = 1;
        extract($joapp_api->query->get(array('slug', 'page')));
        $res = $joapp_api->joapp_api_validate(__FUNCTION__);
        $pages = 0;

        $wp_category = get_term_by('slug', $slug, 'product_cat');
        $page = $page - 1;

        $args = $this->create_arg_product("slug", $wp_category->slug, $page);
        $cat_id = $wp_category->term_taxonomy_id;
        $postslist = get_posts($args);
        $products = array();
        foreach ($postslist as $p) {
            $products[] = new JOAPP_API_Product($p->ID, true);
        }

        $joapp_result = array(
            'products' => $products,
            'pages' => 5,
            'skey' => $res,
            'post_view' => get_option("joapp_api_taxonomy_post_view_$cat_id", "one_product_large")
        );

        do_action('joapp_api_action_get_woo_one_products_category');

        return $joapp_result;
    }

    public function get_woo_terms() {
        global $joapp_api, $joapp_result;
        $attr_id;
        extract($joapp_api->query->get(array('attr_id')));
        $res = $joapp_api->joapp_api_validate(__FUNCTION__);
        $attr = wc_get_attribute($attr_id);
        $terms = get_terms(array(
            'taxonomy' => $attr->slug,
            'hide_empty' => TRUE,
        ));

        $joapp_result = array(
            'terms' => $terms,
            'skey' => $res
        );

        do_action('joapp_api_action_get_woo_terms');

        return $joapp_result;
    }

    public function get_woo_products_term() {
        global $joapp_api, $joapp_result;
        $slug_cat;
        $slug_term;
        $slug_attr;
        $page;
        extract($joapp_api->query->get(array('slug_cat', 'slug_term', 'slug_attr', 'page')));
        $res = $joapp_api->joapp_api_validate(__FUNCTION__);

        $tax = array(
            array(
                'taxonomy' => $slug_attr,
                'field' => 'slug',
                'terms' => $slug_term,
            )
        );

        if ($slug_cat != "") {
            $tax[] = array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => array($slug_cat),
            );
        }

        $query_args = array(
            'post_type' => 'product',
            'tax_query' => $tax,
            'posts_per_page' => 10,
            'paged' => $page
        );

        $products_quert = new WP_Query($query_args);
        $products = array();

        foreach ($products_quert->get_posts() as $p) {
            $products[] = new JOAPP_API_Product($p->ID, true);
        }

        $joapp_result = array(
            'products' => $products,
            'skey' => $res
        );

        do_action('joapp_api_action_get_woo_products_term');

        return $joapp_result;
    }

    public function get_woo_products_search() {
        global $joapp_api, $joapp_result;
        $q;
        $page;
        extract($joapp_api->query->get(array('q', 'page')));
        $page--;
        $res = $joapp_api->joapp_api_validate(__FUNCTION__);
        $products = array();
        $query = array(
            's' => "$q",
            'post_type' => 'product',
            'offset' => 20 * $page,
            'posts_per_page' => 20,
            'orderby' => array('meta_value' => 'ASC', 'date' => 'DESC'),
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                )
            )
        );
        $posts = get_posts($query);

        foreach ($posts as $p) {
            $products[] = new JOAPP_API_Product($p->ID, TRUE);
        }

        $joapp_result = array(
            'products' => $products,
            'skey' => $res
        );

        do_action('joapp_api_action_get_woo_products_search');

        return $joapp_result;
    }

    public function set_order_woo() {
        global $joapp_api, $joapp_result;
        $is_guest = (isset($_REQUEST['is_guest']) && $_REQUEST['is_guest'] === '1') ? TRUE : FALSE;
        $res = $joapp_api->joapp_api_validate(__FUNCTION__, !$is_guest);
        $order = ( $_REQUEST['order'] );
        $order = urldecode(base64_decode(($order)));
        $order_post = json_decode($order, TRUE);
        $order_post['billing_address'] = $order_post['shipping_address'];

        require_once (__DIR__ . '/includes/wc_order.php');
        $wp_order = new JoApp_WC_API_Orders();
        $new_order = $wp_order->create_order(array('order' => $order_post));
        $o = $wp_order->get_order($new_order);
        $result_order = array(
            'id' => $new_order,
            'order_number' => $o['order_number'],
            'order_key' => $o['order_key'],
        );
        $joapp_result = array(
            'order' => $result_order,
            'skey' => $res
        );

        do_action('joapp_api_action_set_order_woo');

        return $joapp_result;
    }

    public function get_order_woo() {
        global $joapp_api, $joapp_result;
        $order_id;
        $res = $joapp_api->joapp_api_validate(__FUNCTION__);

        $order_id = ( $_REQUEST['order_id'] );

        require_once (__DIR__ . '/includes/wc_order.php');
        $wp_order = new JoApp_WC_API_Orders();
        $o = $wp_order->get_order($order_id);
        $result_order = array(
            'id' => $o['id'],
            'order_number' => $o['order_number'],
            'order_key' => $o['order_key'],
            'payment_details' => $o['payment_details'],
            'status' => $o['status']
        );

        $joapp_result = array(
            'order' => $result_order,
            'skey' => $res
        );

        do_action('joapp_api_action_get_order_woo');

        return $joapp_result;
    }

    public function get_customer_orders_woo() {

        global $joapp_api, $joapp_result;
        $customer_id = $_REQUEST['customer_id'];
        $res = $joapp_api->joapp_api_validate(__FUNCTION__, TRUE);

        require_once (__DIR__ . '/includes/wc_order.php');
        $wp_order = new JoApp_WC_API_Orders();
        $filter['customer_id'] = $customer_id;

        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'meta_value' => $customer_id,
            'post_type' => wc_get_order_types(),
            'post_status' => array(
                'wc-processing',
                'wc-on-hold',
                'wc-completed',
                'wc-cancelled',
                'wc-refunded',
                'wc-failed'
            ),
        ));

        $orders = array();
        foreach ($customer_orders as $o) {
            $or = $wp_order->get_order($o->ID);
            $orders[] = array(
                'id' => $or['id'],
                'order_number' => $or['order_number'],
                'order_key' => $or['order_key'],
                'payment_details' => $or['payment_details'],
                'status' => $or['status'],
                'line_items' => $or['line_items']
            );
        }
        $joapp_result = array(
            'orders' => $orders,
            'skey' => $res
        );

        do_action('joapp_api_action_get_customer_orders_woo');

        return $joapp_result;
    }

    public function get_customer_downloads_woo() {

        global $joapp_api, $joapp_result;
        $customer_id = $_REQUEST['customer_id'];
        $res = $joapp_api->joapp_api_validate(__FUNCTION__, TRUE);
        $all_downloads = array();

        $downloads = wc_get_customer_available_downloads($customer_id);

        foreach ($downloads as $d) {
            $file_name = pathinfo($d['file']['file'], PATHINFO_EXTENSION);
            $all_downloads[] = array(
                'download_url' => $d['download_url'],
                'download_name' => $d['product_name'] . ' : ' . $d['download_name'],
                'downloads_remaining' => $d['downloads_remaining'],
                'base_file_name' => $d['download_name'] . ($file_name ? '.' . $file_name : '')
            );
        }

        $joapp_result = array(
            'downloads' => $all_downloads,
            'skey' => $res
        );

        do_action('joapp_api_action_get_customer_downloads_woo');

        return $joapp_result;
    }

    public function get_cart_start() {

        global $joapp_api;
        extract($joapp_api->query->get(array('user', 'pass', 'is_guest', 'ja_orders')));

        $checked_guest = ($user && $user === "-9898" && $is_guest && $is_guest === '1' && get_option('joapp_api_allow_guest', false));
        if ($user || $checked_guest) {
            if (!$checked_guest) {
                if (!wp_login($user, $pass)) {
                    $joapp_api->error_title_details("login", "Not Logined...", $joapp_api->getRegisterLink());
                }

                $u = get_user_by('login', "$user");

                if (is_null($u->ID)) {
                    $u = get_user_by('email', "$user");
                }

                // $customer = new WC_Customer($u->ID);


                $shipping = array(
                    'first_name' => get_user_meta($u->ID, 'billing_first_name', true),
                    'last_name' => get_user_meta($u->ID, 'billing_last_name', true),
                    'company' => get_user_meta($u->ID, 'billing_company', true),
                    'address_1' => get_user_meta($u->ID, 'billing_address_1', true),
                    'address_2' => get_user_meta($u->ID, 'billing_address_2', true),
                    'city' => get_user_meta($u->ID, 'billing_city', true),
                    'state' => get_user_meta($u->ID, 'billing_state', true),
                    'postcode' => get_user_meta($u->ID, 'billing_postcode', true),
                    'country' => get_user_meta($u->ID, 'billing_country', true),
                    'email' => get_user_meta($u->ID, 'billing_email', true),
                    'phone' => get_user_meta($u->ID, 'billing_phone', true),
                );
            } else {
                
            }

            $str_shipping_forms = get_option("joapp_api_shipping_forms", '[]');
            $shipping_form = json_decode($str_shipping_forms, TRUE);

            if (!is_array($shipping_form) || count($shipping_form) == 0) {
                $shipping_form = array(
                    "FIRST_NAME",
                    "LAST_NAME",
                    "COMPANY",
                    "ADDRESS_1",
                    "ADDRESS_2",
                    "CITY",
                    "STATE",
                    "POSTCODE",
                    "COUNTRY",
                    "EMAIL",
                    "PHONE",
                    "NOTE"
                );
            }
            $check_orders = array();

            if ($ja_orders) {
                $orders_str = base64_decode($ja_orders);
                $arr_orders = json_decode($orders_str, TRUE);
                $arr_orders_clean = array();

                foreach ($arr_orders as $ord) {
                    if (!isset($arr_orders_clean[$ord['product_id']])) {
                        $arr_orders_clean[$ord['product_id']]['quantity'] = $ord['quantity'];
                    } else {
                        $arr_orders_clean[$ord['product_id']]['quantity'] += $ord['quantity'];
                    }
                }

                foreach ($arr_orders_clean as $id_order => $qu_order) {
                    $msg = "";
                    $o = wc_get_product($id_order);
                    if ($o) {
                        $in_stock = TRUE;
                        if (method_exists($o, "is_in_stock"))
                            $in_stock = $o->is_in_stock();
                        else
                            $in_stock = method_exists($o, "get_stock_status") ? ((bool) ($o->get_stock_status() == "instock")) : false;

                        if (!$in_stock) {
                            $msg .= $o->get_title() . " موجود نیست. ";
                        }

                        if ($o->managing_stock()) {
                            if ($o->get_stock_quantity() < $qu_order['quantity']) {
                                $msg .= $o->get_title() . " به این تعداد در انبار موجود نیست.";
                            }
                        }

                        if (method_exists($o, 'is_sold_individually') && $o->is_sold_individually() && $qu_order['quantity'] > 1) {
                            $msg .= $o->get_title() . " تنها برای فروش تکی میباشد";
                        }
                    } else {
                        $msg .= $id_order . " وجود ندارد. ";
                    }

                    if (strlen($msg) > 0) {
                        $check_orders[] = array(
                            "order_id" => $id_order,
                            "message" => $msg
                        );
                    }
                }
            }

            $states = array();
            $update_states = get_option("joapp_api_update_states", "0");

            if (isset($_REQUEST['update_states']) && $_REQUEST['update_states'] < $update_states && $update_states > 0) {
                include_once __DIR__ . "/../singletons/setting/JoAppState.php";
                $JoAppState = new JoAppState();
                $location['states'] = $JoAppState->getSelectedStates();
                foreach ($location['states'] as &$ss) {
                    $ss['cities'] = (array) $JoAppState->getSelectedCities($ss['code']);
                }
            }

            $location['update_flag'] = $update_states;
            global $joapp_result;

            $cart_token = "";
            if (count($check_orders) == 0 && $ja_orders) {
                $cart_token = md5(rand(100000, 999999999));
                while (get_option("joapp_api_cart_{$cart_token}", false) === true) {
                    $cart_token = md5(rand(100000, 999999999));
                }


                $joapp_api->save_option("joapp_api_cart_{$cart_token}", json_encode($arr_orders_clean));
            }

            $joapp_result = array(
                'count' => 1,
                // 'shipping' => $shipping,
                // 'shipping_form' => $shipping_form,
                'check_orders' => $check_orders,
                // 'locations' => $location,
                'cart_token' => $cart_token
            );
            do_action("joapp_api_action_get_cart_start");
            return $joapp_result;
        } else {
            $joapp_api->error_title_details("login", "Not Logined...", $joapp_api->getRegisterLink());
        }
    }

    public function start_pay_webview() {
        if (!isset($_REQUEST['cart_token']))
            wp_die("خطای بررسی داده ها");

        $cart_token = $_REQUEST['cart_token'];

        if (!isset($_REQUEST['d'])) {
            $joapp_api->error("Include 'user' var in your request.");
        }

        $data = base64_decode($_REQUEST['d']);
        $arr = explode("{WOO}", $data);

        $user = $arr[0];
        $pass = $arr[1];

        $is_guest = get_option('joapp_api_allow_guest', false) && $user === "-9898";
        if ($user) {
            if (!$is_guest) {
                if (!wp_login($user, $pass)) {
                    $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
                }

                $u = get_user_by('login', "$user");

                if (is_null($u->ID)) {

                    if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
                        $joapp_api->error("User Not Found.");
                        exit();
                    }
                    $u = get_user_by('email', "$user");

                    if (is_null($u->ID)) {
                        $joapp_api->error("User Not Found.");
                        exit();
                    }
                }
            }
            $products_str = get_option("joapp_api_cart_{$cart_token}", "{}");
            $products = json_decode($products_str);
            wp_clear_auth_cookie();

            if ($user !== "-9898") {
                wp_set_current_user($u->ID);
                wp_set_auth_cookie($u->ID);
            }
            wc()->cart->empty_cart();

            foreach ($products as $product_id => $value) {
                wc()->cart->add_to_cart($product_id, $value->quantity);
            }

            header("location:" . wc_get_checkout_url() . "?&joapp_payment_page=1");
        } else {
            $joapp_api->error("User Not Found.");
            exit();
        }
    }

}

?>
