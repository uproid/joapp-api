<?php

class JOAPP_API {

    function __construct() {
        if (isset($_REQUEST['check_update']) && $_REQUEST['check_update'] == '1') {
            $this->check_update();
            exit;
        }
        $this->query = new JOAPP_API_Query();
        $this->introspector = new JOAPP_API_Introspector();
        $this->response = new JOAPP_API_Response();
        add_action('template_redirect', array(&$this, 'template_redirect'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('update_option_joapp_api_base', array(&$this, 'flush_rewrite_rules'));
        add_action('pre_update_option_joapp_api_controllers', array(&$this, 'update_controllers'));
    }

    function template_redirect() {
        $controller = strtolower($this->query->get_controller());
        $available_controllers = $this->get_controllers();
        $enabled_controllers = explode(',', get_option('joapp_api_controllers', 'core'));
        $active_controllers = array_intersect($available_controllers, $enabled_controllers);

        if ($controller) {
            $api_key = get_option("joapp_api_key");
            if (isset($_REQUEST['v']) && $_REQUEST['v'] != "") {
                $format = 'Y-m-d H:00:00';
                switch ($_REQUEST['v']) {
                    case "1":
                        $format = 'Y-m-d H:00:00';
                        break;

                    case "2":
                        $format = 'Y-m-d';
                        break;
                    case "3":
                        $format = false;
                        break;
                    default:
                        $format = 'Y-m-d H:00:00';
                        break;
                }

                if ($format) {
                    $timestamp = $this->time_stamp($format);
                    $api_key = md5($api_key . "" . $timestamp);
                }
            }
            //if ($_REQUEST['joapp'] !== 'open_pay_woo') {
            if (!isset($_REQUEST['API_KEY']) || $api_key !== $_REQUEST['API_KEY']) {
                $this->error("Your API KEY Not Valid...");
            }
            //}

            $login_level = get_option("joapp_api_login_level", 2);
            if ($login_level == 2 && $_REQUEST['joapp'] != 'open_pay_woo' && $_REQUEST['joapp'] != 'get_register_link' && $_REQUEST['joapp'] != 'register_user' && $_REQUEST['joapp'] != 'get_about') {
                if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
                    $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                }

                if (!is_email($_REQUEST['user'])) {

                    if (!validate_username($_REQUEST['user'])) {
                        $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                    }

                    if (!username_exists($_REQUEST['user'])) {
                        $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                    }
                } else {
                    if (!email_exists($_REQUEST['user'])) {
                        $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                    }
                }
                if (!wp_login($_REQUEST['user'], $_REQUEST["pass"], $_REQUEST["de"])) {
                    $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                }
            }

            if (empty($this->query->dev)) {
                error_reporting(0);
            }

            if (!in_array($controller, $active_controllers)) {
                $this->error("Unknown controller '$controller'.");
            }

            $controller_path = $this->controller_path($controller);
            if (file_exists($controller_path)) {

                require_once $controller_path;
            }

            $controller_class = $this->controller_class($controller);

            if (!class_exists($controller_class)) {
                $this->error("Unknown controller '$controller_class'.");
            }

            $this->controller = new $controller_class();
            $method = $this->query->get_method($controller);

            if ($login_level == 1 && ($method == "get_post" || $method == "get_page")) {
                if (!wp_login($_REQUEST['user'], $_REQUEST["pass"], $_REQUEST["de"])) {
                    if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
                        $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                    }

                    if (!is_email($_REQUEST['user'])) {

                        if (!validate_username($_REQUEST['user'])) {
                            $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                        }

                        if (!username_exists($_REQUEST['user'])) {
                            $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                        }
                    } else {
                        if (!email_exists($_REQUEST['user'])) {
                            $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                        }
                    }
                    if (!wp_login($_REQUEST['user'], $_REQUEST["pass"], $_REQUEST["de"])) {
                        $this->error_title_details("login", "Username or Password is not valid...", $this->getRegisterLink());
                    }
                }
            }

            if ($method) {

                $this->response->setup();

                do_action("joapp_api", $controller, $method);
                do_action("joapp_api-{$controller}-$method");

                if ($method == '404') {
                    $this->error('Not found');
                }

                $result = $this->controller->$method();
                $skey = (isset($result['skey'])) ? $result['skey'] : "";
                unset($result['skey']);
                $this->response->respond($result, "ok", 200, $skey);

                exit;
            }
        }
    }

    function admin_menu() {
        add_menu_page('تنظیمات JoApp API', 'تنظیمات JoApp API', 'manage_options', 'joapp-api', array(&$this, 'admin_options'), plugins_url('assets/joapp.png', __DIR__), 25);

        add_submenu_page("joapp-api", "اتصال به اپلیکیشن", "اتصال به اپلیکیشن", "manage_options", 'admin.php?page=joapp-api&tab=1');
        add_submenu_page("joapp-api", "پست ها در اپلیکیشن", "پست ها در اپلیکیشن", "manage_options", 'admin.php?page=joapp-api&tab=2');
        add_submenu_page("joapp-api", "تنظیمات فروشگاه", "تنظیمات فروشگاه", "manage_options", 'admin.php?page=joapp-api&tab=3');
        add_submenu_page("joapp-api", "فیلد های زمینه دلخواه", "فیلد های زمینه دلخواه", "manage_options", 'admin.php?page=joapp-api&tab=4');
        add_submenu_page("joapp-api", "پوش نوتیفیکیشن", "پوش نوتیفیکیشن", "manage_options", 'admin.php?page=joapp-api&tab=5');
        add_submenu_page('joapp-api', 'ویرایش درباره ما', "ویرایش درباره ما", 'manage_options', 'admin.php?page=joapp-api&other=admin_about');
        add_submenu_page('joapp-api', 'افزونه های کمکی', "افزونه های کمکی", 'manage_options', 'admin.php?page=joapp-api&page_joapp=plugins');
        do_action("joapp_api_action_view_admin_menu");
    }

    function admin_about() {
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save_about' && isset($_POST['about_editor'])) {
            do_action("joapp_api_action_save_about", $_REQUEST);
            $this->save_option("joapp_api_about", htmlentities(stripslashes($_POST['about_editor'])));
            $this->save_option("joapp_api_about_image", htmlentities(stripslashes($_POST['about_image'])));
        }
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"><br /></div>
            <h2>درباره ما</h2>
            <hr/>
            <form action="admin.php?page=joapp-api&other=admin_about&action=save_about" method="post">
                <label for="about_image">تصویر شاخص بخش درباره ما :</label>

                <input type="text" id="about_image" value="<?php echo get_option("joapp_api_about_image", "") ?>" name="about_image"/>
                <a id="upload-button-a" href="#" class="button button-primary">انتخاب تصویر</a>

                <hr/>

                <?php
                $content = get_option("joapp_api_about", "قبل از انتشار برنامه خودتان بخش درباره ما را ویرایش کنید و نماد های اعتماد خود را در این بخش به همراه شبکه های اجتماعی لینک دهید.");
                $content = html_entity_decode($content);
                wp_editor($content, 'about_editor', $settings = array());
                do_action("joapp_api_action_view_about", $_REQUEST);
                ?>
                <hr/>
                <input type="submit" class="button button-primary" value="ذخیره درباره ما"/>
            </form>
            <script>
                jQuery(document).ready(function () {

                    var mediaUploader;

                    jQuery('#upload-button-a').click(function (e) {

                        e.preventDefault();

                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }

                        mediaUploader = wp.media.frames.file_frame = wp.media({
                            title: 'انتخاب تصویر شاخص درباره ما ',
                            button: {
                                text: 'انتخاب تصویر'
                            }, multiple: false});

                        mediaUploader.on('select', function () {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            jQuery("#about_image").val(attachment.url);
                        });
                        mediaUploader.open();
                    });

                });
            </script>
        </div>
        <?php
    }

    function admin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        wp_enqueue_media();
        wp_register_script('media-lib-uploader-js', plugins_url('media-lib-uploader.js', __FILE__), array('jquery'));
        wp_enqueue_script('media-lib-uploader-js');

        add_action('admin_enqueue_scripts', 'my_media_lib_uploader_enqueue');
        /*         * **************************** */

        if (isset($_GET['other'])) {
            $other = $_GET['other'];
            $this->$other();
            return;
        }

        $available_controllers = $this->get_controllers();
        $active_controllers = explode(',', get_option('joapp_api_controllers', 'core'));

        if (count($active_controllers) == 1 && empty($active_controllers[0])) {
            $active_controllers = array();
        }

        $is_valid_nonce = ( isset($_REQUEST['_nonce']) && wp_verify_nonce($_REQUEST['_nonce'], basename(__FILE__)) ) ? true : false;

        if ($is_valid_nonce) {
            if ((!empty($_REQUEST['action']) || !empty($_REQUEST['action2'])) &&
                    (!empty($_REQUEST['controller']) || !empty($_REQUEST['controllers']))) {
                if (!empty($_REQUEST['action'])) {
                    $action = $_REQUEST['action'];
                } else {
                    $action = $_REQUEST['action2'];
                }

                if (!empty($_REQUEST['controllers'])) {
                    $controllers = $_REQUEST['controllers'];
                } else {
                    $controllers = array($_REQUEST['controller']);
                }

                foreach ($controllers as $controller) {
                    if (in_array($controller, $available_controllers)) {
                        if ($action == 'activate' && !in_array($controller, $active_controllers)) {
                            $active_controllers[] = $controller;
                        } else if ($action == 'deactivate') {
                            $index = array_search($controller, $active_controllers);
                            if ($index !== false) {
                                unset($active_controllers[$index]);
                            }
                        }
                    }
                }
                $this->save_option('joapp_api_controllers', implode(',', $active_controllers));
            } else {
                if ($_REQUEST['action'] == 'save_tab1') {
                    do_action("joapp_api_action_save_tab_connection", $_REQUEST);
                    //TAB1
                    $this->save_option('joapp_approved_comments', isset($_REQUEST['joapp_approved_comments']) ? "str_true" : "str_false");
                    //TAB1
                    if (isset($_REQUEST['joapp_api_key']) && $_REQUEST['joapp_api_key'] != "") {
                        $api_key = $_REQUEST['joapp_api_key'];
                        $this->save_option('joapp_api_key', $api_key);
                    } else if ((get_option("joapp_api_key", '')) == '') {
                        $this->save_option('joapp_api_key', wp_generate_password(20, false));
                    }
                    //TAB1
                    if (isset($_REQUEST['joapp_api_version_code'])) {
                        $this->save_option('joapp_api_version_code', $_REQUEST['joapp_api_version_code']);
                    }
                    //TAB1
                    if (isset($_REQUEST['joapp_api_apk_url'])) {
                        $this->save_option('joapp_api_apk_url', trim($_REQUEST['joapp_api_apk_url']));
                    }
                    //TAB1
                    if (isset($_REQUEST['joapp_api_login_level'])) {
                        $this->save_option('joapp_api_login_level', $_REQUEST['joapp_api_login_level']);
                    }
                    //TAB1
                    if (isset($_REQUEST['joapp_api_register_link'])) {
                        $this->save_option("joapp_api_register_link", $_REQUEST['joapp_api_register_link']);
                    }
                    //TAB1
                    $this->save_option("joapp_api_active_register", (bool) isset($_REQUEST['active_register']));
                } else if ($_REQUEST['action'] == 'save_tab2') {
                    do_action("joapp_api_action_save_tab_posts", $_REQUEST);
                    //TAB2
                    if (isset($_REQUEST['joapp_allow_post_types'])) {
                        $post_types = $_REQUEST['joapp_allow_post_types'];

                        if (count($post_types) == 0) {
                            $post_types[] = 'post';
                        }

                        $this->save_option("joapp_allow_post_types", json_encode($post_types));
                    } else {
                        $this->save_option("joapp_allow_post_types", "[]");
                    }

                    //TAB2
                    if (isset($_REQUEST['hidden_category'])) {
                        $this->save_option("joapp_api_hidden_category", json_encode($_REQUEST['hidden_category']));
                    } else {
                        $this->save_option("joapp_api_hidden_category", "[]");
                    }
                    if (isset($_REQUEST['hidden_category_menu'])) {
                        $this->save_option("joapp_api_hidden_category_menu", json_encode($_REQUEST['hidden_category_menu']));
                    } else {
                        $this->save_option("joapp_api_hidden_category_menu", "[]");
                    }
                    $joapp_header_posts = isset($_REQUEST['joapp_header_posts']) ? $_REQUEST['joapp_header_posts'] : '';
                    $joapp_footer_posts = isset($_REQUEST['joapp_footer_posts']) ? $_REQUEST['joapp_footer_posts'] : '';
                    //TAB2
                    $this->save_option('joapp_header_posts', htmlentities(stripslashes($joapp_header_posts)));
                    //TAB2
                    $this->save_option('joapp_footer_posts', htmlentities(stripslashes($joapp_footer_posts)));
                    $this->save_option('joapp_date_format', $_REQUEST['joapp_date_format']);
                    $this->save_option("joapp_show_author", isset($_REQUEST['joapp_show_author']) ? "str_true" : "str_false");
                    $this->save_option("joapp_api_post_view", isset($_REQUEST['joapp_api_post_view']) ? $_REQUEST['joapp_api_post_view'] : "");
                } else if ($_REQUEST['action'] == 'save_tab3') {
                    do_action("joapp_api_action_save_tab_store", $_REQUEST);
                    //TAB3
                    $req_using_wocommerce = (isset($_REQUEST['using_woocommerce']) && $_REQUEST['using_woocommerce'] == 1) ? "1" : "0";
                    $this->save_option("joapp_api_active_wooceommerce", $req_using_wocommerce);
                    $this->save_option("joapp_woo_count_category", isset($_REQUEST['joapp_woo_count_category']) ? $_REQUEST['joapp_woo_count_category'] : 10);
                    //TAB3
                    $this->save_option("using_joapp_payment", isset($_REQUEST['using_joapp_payment']) ? "str_true" : "str_false");
                    $this->save_option("joapp_api_payment_color", isset($_REQUEST['joapp_api_payment_color']) ? $_REQUEST['joapp_api_payment_color'] : "255,0,68");
                    $this->save_option("joapp_api_allow_guest", isset($_REQUEST['joapp_api_allow_guest']));
                    //TAB3
                    $this->save_option('joapp_api_shipping_forms', json_encode($_REQUEST['joapp_api_shipping_forms']));
                    //TAB3
                    if (isset($_REQUEST['hidden_woo_category'])) {
                        $this->save_option("joapp_wooapi_hidden_category", json_encode($_REQUEST['hidden_woo_category']));
                    } else {
                        $this->save_option("joapp_wooapi_hidden_category", "[]");
                    }

                    if (isset($_REQUEST['hidden_woo_category_menu'])) {
                        $this->save_option("joapp_wooapi_hidden_category_menu", json_encode($_REQUEST['hidden_woo_category_menu']));
                    } else {
                        $this->save_option("joapp_wooapi_hidden_category_menu", "[]");
                    }

                    //TAB3
                    if (isset($_REQUEST['filter_attr'])) {
                        $this->save_option("joapp_api_woo_filters", json_encode($_REQUEST['filter_attr']));
                    }

                    //TAB3
                    if (isset($_REQUEST['image_slider'])) {
                        $arr = $_REQUEST['image_slider'];

                        $res = array();

                        $i = 0;
                        foreach ($arr['url'] as $v) {
                            $intent = $arr['intent'][$i];
                            $res[] = array(
                                'url' => $v,
                                'intent' => $intent
                            );
                            $i++;
                        }
                        $this->save_option("joapp_api_woo_image_slider", json_encode($res));
                    } else {
                        $this->save_option("joapp_api_woo_image_slider", "[]");
                    }
                }
            }
        }

        if (get_option('joapp_api_key', '') == '')
            $this->save_option('joapp_api_key', wp_generate_password(20, false));

        $tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : "1";
        $page = isset($_REQUEST['page_joapp']) ? $_REQUEST['page_joapp'] : "setting";

        if (!file_exists(__DIR__ . "/setting/$page.php")) {
            $page = "setting";
        }
        ?>
        <script>
            function waiting_joapp(show) {
                if (show) {
                    var img = '<center style="display: table-cell;vertical-align: middle;"><img src="<?php echo plugin_dir_url(__DIR__); ?>assets/waiting.gif" /></center>';
                    var src = '<div style="display: table;text-align: center;background-color: rgba(255,255,255,0.7);height: 100%;width: 100%;position: fixed;z-index: 100000000;" id="waiting_joapp">' + img + '</div>';
                    jQuery('body').prepend(src);
                    jQuery("#waiting_joapp").show();
                } else {
                    jQuery("#waiting_joapp").fadeOut(300, function () {
                        jQuery(this).remove();
                    });
                }
            }
        </script>
        <?php
        wp_enqueue_script(array("jquery", "jquery-ui-core", "interface", "jquery-ui-sortable", "wp-lists", "jquery-ui-sortable"));
        wp_enqueue_script("scriptaculous-dragdrop");

        if ($page == "setting") {
            ?>
            <div class="wrap">
                <div id="joapp_warning"></div>
                <style>
                    .tab{
                        display: none;
                    }

                    .all_filters{
                        max-height: 350px;
                        overflow-y: scroll;
                    }

                </style>
                <script>
                    jQuery(document).ready(function () {
                        select_tab('<?php echo $tab ?>');
                        check_update();
                    });

                    function check_update() {
                        jQuery.get('admin.php?page=joapp-api&check_update=1', function (contents) {
                            if (contents === 'true') {
                                var str = '<div class="update-message notice inline notice-warning notice-alt"><p>';
                                str += 'نسخه جدیدی از JOAPP API برای به روز رسانی وجود دارد. برای استفاده از تمامی امکانات تپلیکیشن اندروید به روز رسانی نمایید';
                                str += '&nbsp;&nbsp;<a class="button button-primary thickbox" href="<?php echo self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=joapp-api&amp;TB_iframe=true&amp;width=600&amp;height=550') ?>">به روز رسانی</a></p></div>';
                                jQuery("#joapp_warning").html(str);
                            }
                        }, 'text');
                    }

                    function select_tab(selected) {
                        jQuery(".tab").hide();
                        jQuery("#tab_" + selected).show();
                        jQuery(".nav-tab").attr('class', 'nav-tab');
                        jQuery("#nav-tab" + selected).attr('class', 'nav-tab nav-tab-active');
                        jQuery("#submit_tab_a").show();
                        jQuery(".selected_tab").val(selected);
                        if (selected === '4' || selected === '5')
                            jQuery("#submit_tab_a").hide();
                    }
                </script>
                <div id="icon-options-general" class="icon32"><br /></div>
                <h2>تنظیمات JoApp API</h2>
                <input class="selected_tab" type="hidden" value="<?php echo $tab ?>" name="tab"/>
                <h3 class="nav-tab-wrapper">
                    <a id="nav-tab1" onclick="select_tab('1')" href="#tab1" class="nav-tab<?php echo $tab == 'tab1' ? ' nav-tab-active' : '' ?>">اتصال به اپلیکیشن</a>
                    <a id="nav-tab2" onclick="select_tab('2')" href="#tab2" class="nav-tab<?php echo $tab == 'tab2' ? ' nav-tab-active' : '' ?>">پست ها در اپلیکیشن</a>
                    <a id="nav-tab3" onclick="select_tab('3')" href="#tab3" class="nav-tab<?php echo $tab == 'tab3' ? ' nav-tab-active' : '' ?>">تنظیمات فروشگاه</a>
                    <a id="nav-tab4" onclick="select_tab('4')" href="#tab4" class="nav-tab<?php echo $tab == 'tab4' ? ' nav-tab-active' : '' ?>">فیلد های زمینه دلخواه</a>
                    <a id="nav-tab5" onclick="select_tab('5')" href="#tab5" class="nav-tab<?php echo $tab == 'tab5' ? ' nav-tab-active' : '' ?>">پوش نوتیفیکیشن</a>
                    <a href="admin.php?page=joapp-api&page_joapp=plugins" class="nav-tab">افزونه های کمکی <span style="border-radius: 3px;background-color: red;color: #FFF;padding: 3px" ><?php echo get_option("joapp_api_plugin_update_count", "1") ?></span></a>
                </h3>
                <div id="tab_1" class="tab">
                    <form action="admin.php?page=joapp-api&action=save_tab1&tab=1" method="post">
                        <?php wp_nonce_field(basename(__FILE__), '_nonce'); ?>
                        <?php
                        $controller = "respond";
                        $active = in_array("respond", $active_controllers);
                        ?>
                        <hr/>
                        <a href="admin.php?page=joapp-api&page_joapp=edit_custom_menus&wp=true" class="button button-primary button-hero">تنظیم منو های دلخواه وردپرس</a>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">امکان ارسال دیدگاه</th>
                                <td>
                                    <?php
                                    if ($active) {
                                        echo '<a class="button button-primary" href="' . wp_nonce_url('admin.php?page=joapp-api&action=deactivate&controller=' . $controller, basename(__FILE__), '_nonce') . '" title="' . __('Deactivate this controller') . '" class="edit">' . __('Deactivate') . '</a>';
                                    } else {
                                        echo '<a class="button button-primary" href="' . wp_nonce_url('admin.php?page=joapp-api&action=activate&controller=' . $controller, basename(__FILE__), '_nonce') . '" title="' . __('Activate this controller') . '" class="edit">' . __('Activate') . '</a>';
                                    }
                                    ?>
                                    <div class="notice inline notice-warning notice-alt">
                                        <p>مشخص میکنید که آیا کاربران بتوانند از طریق اپلیکیشن دیدگاه برای پست ها ارسال کنند یا خیر.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>تایید خودکار دیدگاه کاربران</th>
                                <td>
                                    <input name="joapp_approved_comments" <?php echo (get_option("joapp_approved_comments", "str_false") === "str_true") ? " checked " : "" ?> type="checkbox" value="str_true" />
                                </td>
                            </tr>
                        </table>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">API KEY</th>
                                <td><input type="text" name="joapp_api_key" value="<?php echo get_option('joapp_api_key', ''); ?>" size="30" /></td>
                            </tr>

                        </table>
                        <div class="notice inline notice-warning notice-alt">
                            <p>با تغییر API KEY اتصال اپلیکیشن های قبلی به این سایت قطع خواهد شد.</p>
                        </div>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">شماره نسخه آخرین آپدیت فایل APK</th>
                                <td><input type="number" name="joapp_api_version_code" value="<?php echo get_option('joapp_api_version_code', '1'); ?>" size="30" /></td>
                            </tr>
                        </table>
                        <div class="notice inline notice-success notice-alt">
                            <p>در صورتی که شماره نسخه بیشتر از شماره نسخه اپلیکیشن کاربران باشد به صورت خودکار لینک APK دانلود شده و جایگزین برنامه قبلی در گوشی میشود</p>
                        </div>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">لینک دانلود مستقیم آخرین APK برای آپدیت</th>
                                <td><input type="text" name="joapp_api_apk_url" value="<?php echo get_option('joapp_api_apk_url', ''); ?>" /></td>
                            </tr>
                        </table>
                        <div class="notice inline notice-warning notice-alt">
                            <p>این لینک فایل APK نسخه جدید برنامه میباشد که در صورت افزایش شماره نسخه دانلود شده و جایگزین برنامه قبلی در گوشی میشود. توجه کنید که این برنامه باید با شماره نسخه بیشتر از نسخه قبلی ، دقیقا با همان نام بسته برنامه اصلی و همچنین در صورت استفاده از KeyStore باید با همان KeyStore برنامه قبلی ساخته شده باشد، تا عملیات نصب صورت گیرد. همچنین تست کنید کاراکتر اضافه ای وارد نشده باشد و دسترسی مستقیم به آن در مرورگر وجود دارد.</p>
                            <p>در صورتی که مارکت ها به شما اجازه استفاده از به روز رسانی از داخل اپلیکیشن را نمیدهند میتوانید از لینک های ارجاع به مارکت استفاده نمایید به طور مثال فرض کنید نام بسته برنامه شما ir.bejo.joapp باشد در این صورت لینک ارجاع مارکت به صورت زیر میباشد که در فیلد بالا قرار میدهید.</p>
                            <pre class="ltr text-red">{bazaar://details?id=<strong>ir.bejo.joapp</strong>|Intent.ACTION_VIEW|com.farsitel.bazaar}</pre>
                            <p><a href="http://joapp.ir/plugin_update/#sec174" target="_blank">آموزش برای دیگر مارکت ها</a></p>
                        </div>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">اجباری کردن ورود به حساب کاربری</th>
                                <td>
                                    <?php
                                    $login_level = get_option('joapp_api_login_level', 2);
                                    ?>
                                    <select name="joapp_api_login_level">
                                        <option <?php echo $login_level == 0 ? "selected='selected'" : '' ?> value="0">هیچگاه</option>
                                        <option <?php echo $login_level == 1 ? "selected='selected'" : '' ?> value="1">تنها برای مشاهده پست ها</option>
                                        <option <?php echo $login_level == 2 ? "selected='selected'" : '' ?> value="2">تمامی بخش های اپلیکیشن</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">لینک بازیابی رمزعبور در سایت</th>
                                <td>
                                    <?php
                                    $register_link = get_option('joapp_api_register_link', '');
                                    $is_defualt_register_link = ($register_link === '');

                                    $args = array(
                                        'sort_order' => 'DESC',
                                        'post_type' => 'page',
                                        'post_status' => 'publish'
                                    );
                                    $pages = get_pages($args);
                                    ?>
                                    <div>
                                        <select name="joapp_api_register_link">
                                            <option value="" <?php echo $is_defualt_register_link ? "selected='selected'" : "" ?>>صفحه عضویت پیش فرض</option>
                                            <option value="<?php echo wp_lostpassword_url(); ?>" <?php echo wp_lostpassword_url() === $register_link ? "selected='selected'" : "" ?>>صفحه پیش فرض بازیابی رمز عبور</option>
                                            <?php
                                            foreach ($pages as $row) {
                                                $link = get_page_link($row->ID);
                                                $selected = $register_link === $link ? "selected='selected'" : "";
                                                echo "<option " . $selected . " value='" . $link . "'>" . $row->post_title . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="notice inline notice-info notice-alt">
                            <p>لینکی که برای بازیابی ایمیل کاربران وجود دارد در این بخش مشخص نمایید.</p>
                        </div>
                        <hr/>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">اجاره عضویت به کاربر جدید</th>
                                <td>
                                    <?php
                                    $active_register = get_option('joapp_api_active_register', false);
                                    ?>
                                    <div>
                                        <input value="true" type="checkbox" <?php echo $active_register ? "checked='checked'" : '' ?> name="active_register"/>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="notice inline notice-info notice-alt">
                            <p>با تنظیم این بخش کاربران میتوانند برای عضویت از طریق اپلیکیشن اقدام کنند.</p>
                        </div>
                        <?php
                        do_action("joapp_api_action_view_tab_connection", $_REQUEST);
                        ?>
                        <div id="submit_tab_a">
                            <p class="submit">
                                <input type="submit" class="button button-primary button-hero" value="<?php _e('Save Changes') ?>" />
                            </p>
                            <hr/>
                        </div>
                    </form>
                </div>
                <div id="tab_2" class="tab">
                    <form action="admin.php?page=joapp-api&action=save_tab2&tab=2" method="post">
                        <?php wp_nonce_field(basename(__FILE__), '_nonce'); ?>
                        <table class="form-table">
                            <style>
                                .li_list{
                                    margin: 2px;
                                    padding: 10px;
                                    border: 1px #555 solid
                                }

                                #all_cat,#all_cat_woo{
                                    border: 1px #555 solid;overflow-y: scroll;
                                    padding-right: 20px;
                                    margin-left: 20px;
                                    background-color: #FFFFFF;
                                }

                                #select_cat,#select_cat_woo{
                                    background-color: #FFFFFF;
                                    border: 1px #555 solid;overflow-y: scroll
                                }

                            </style>
                            <tr valign="top">
                                <th>حالت نمایش پست ها در اپلیکیشن</th>
                                <td>
                                    <div class="form-field">
                                        <?php
                                        $post_view = get_option("joapp_api_post_view", "");
                                        ?>
                                        <select name="joapp_api_post_view">
                                            <option <?php echo ($post_view == "") ? "selected='selected'" : '' ?> value="">پیش فرض</option>
                                            <option <?php echo ($post_view == "one_news_large") ? "selected='selected'" : '' ?> value="one_news_large">نرمال</option>
                                            <option <?php echo ($post_view == "one_news_medium") ? "selected='selected'" : '' ?> value="one_news_medium">نرمال کوچک</option>
                                            <option <?php echo ($post_view == "one_news_large_2") ? "selected='selected'" : '' ?> value="one_news_large_2">اینستاگرامی</option>
                                            <option <?php echo ($post_view == "one_news_chat") ? "selected='selected'" : '' ?> value="one_news_chat">گفت و گو</option>
                                            <option <?php echo ($post_view == "one_news_nil") ? "selected='selected'" : '' ?> value="one_news_nil">بند انگشتی</option>
                                            <option <?php echo ($post_view == "one_news_row") ? "selected='selected'" : '' ?> value="one_news_row">ردیفی</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                            <hr/>
                            <tr valign="top">
                                <th>چه نوع پست هایی در اپلیکیشن نمایش داده شود</th>
                                <td>
                                    <div class="notice inline notice-warning notice-alt">
                                        <p>در صورت عدم انتخاب یک نوع پست، حالت نوشته ها ذخیره خواهد شد</p>
                                    </div>
                                    <div>
                                        <?php
                                        $post_types = get_post_types();
                                        $joapp_allow_post_types = get_option("joapp_allow_post_types", '["post","joapp_intent"]');
                                        $allow_types = json_decode($joapp_allow_post_types);
                                        if (count($allow_types) == 0) {
                                            $allow_types[] = 'post';
                                        }

                                        array_push($allow_types, "joapp_intent");

                                        foreach ($post_types as $k => $p) {
                                            $c = wp_count_posts($p);
                                            if ($c->publish == 0 && ($p != 'post' && $p != "joapp_intent"))
                                                continue;

                                            $is_allow_type = in_array($p, $allow_types);
                                            ?>
                                            <div>
                                                <input <?php echo ($p == 'joapp_intent' ? "disabled='disabled' readonly " : "") ?> <?php echo $is_allow_type ? ' checked ' : '' ?> type="checkbox" name="joapp_allow_post_types[]" value="<?php echo $p; ?>" />
                                                <label><?php echo $p . ": " . $c->publish ?></label>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <hr/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">مدیریت دسته بندی ها</th>
                                <td>
                                    <div class="notice inline notice-info notice-alt">
                                        <p>ترتیب دسته بندی ها را با گرفتن و کشیدن تغییر دهید. میتوانید از لیست مخفی به لیست فعال بکشید.</p>
                                    </div>
                                    <div class="columns-2">
                                        <div style="display: inline-block; min-width: 40%">
                                            <h3 style="display: block">دسته هایی فعال اپلیکیشن</h3>
                                            <ol id="all_cat" class="connectedSortable" multiple="multiple" style="width: 90%;height: 400px;">
                                                <?php
                                                $json_hidden_list = get_option('joapp_api_hidden_category', "[]");
                                                $json_hidden_list_menu = get_option('joapp_api_hidden_category_menu', "[]");
                                                $hidden_list = json_decode($json_hidden_list);
                                                $hidden_list_menu = json_decode($json_hidden_list_menu);
                                                $terms = get_categories();
                                                foreach ($hidden_list as $t) {

                                                    $cat = get_term_by('term_id', $t, 'category');
                                                    $is_menu = in_array($cat->term_id, $hidden_list_menu);
                                                    if ($cat == null)
                                                        continue;
                                                    ?>
                                                    <li class="li_list" style="background-color: #ccffff">
                                                        <input style="display: none" id="is_active" type="checkbox" checked="checked" value="<?php echo $cat->term_id; ?>" name="hidden_category[]"/>
                                                        <label><?php echo $cat->name; ?>&nbsp;  (<?php echo $cat->count; ?>)</label>
                                                        <span style="float: left">
                                                            <input id="is_menu" type="checkbox" <?php echo $is_menu ? 'checked="checked"' : '' ?>  value="<?php echo $cat->term_id; ?>" name="hidden_category_menu[]"/>
                                                            <label>منو</label>
                                                        </span>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ol>
                                        </div>
                                        <div  style="display: inline-block; min-width: 40%">
                                            <h3 style="display: block">دسته بندی هایی که نمیخواهید نمایش دهید</h3>
                                            <ul id="select_cat" class="connectedSortable" multiple="multiple" style="width: 90%;height: 400px;">
                                                <?php
                                                $json_hidden_list = get_option('joapp_api_hidden_category', "[]");
                                                $hidden_list = json_decode($json_hidden_list);
                                                $terms = get_categories();
                                                foreach ($terms as $t) {
                                                    if (in_array($t->term_id, $hidden_list))
                                                        continue;
                                                    ?>
                                                    <li class="li_list" style="background-color: #ffcccc">
                                                        <input style="display: none" type="checkbox"  id="is_active"  value="<?php echo $t->term_id; ?>" name="hidden_category[]"/>
                                                        <label><?php echo $t->name; ?>&nbsp;  (<?php echo $t->count; ?>)</label>
                                                        <span style="float: left; display: none">
                                                            <input id="is_menu" type="checkbox" value="<?php echo $t->term_id; ?>" name="hidden_category_menu[]"/>
                                                            <label>منو</label>
                                                        </span>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <script>
                                jQuery(document).ready(function () {
                                    jQuery("#all_cat,#select_cat").sortable({
                                        connectWith: ".connectedSortable",
                                        beforeStop: function (event, ui) {
                                            var item = jQuery(ui['item']);
                                            var box = jQuery(item).children("#is_active");
                                            var box_menu = jQuery(item).find("#is_menu");
                                            var parent = jQuery(item).parent();
                                            var isselect = (jQuery(parent).attr("id") === "all_cat");
                                            if (isselect) {
                                                box.attr("checked", "checked");
                                                box_menu.parent().show();
                                                box_menu.attr("checked", "checked");
                                            } else {
                                                box.removeAttr("checked");
                                                box_menu.removeAttr("checked");
                                                box_menu.parent().hide();
                                            }
                                        },
                                        stop: function (e, ui) {
                                            var item = jQuery(ui['item']);
                                            var parent = jQuery(item).parent();
                                            var id = jQuery(parent).attr("id");
                                            var allow = ["all_cat", "select_cat"];
                                            if (allow.indexOf(id) < 0) {
                                                jQuery(this).sortable("cancel");
                                            }
                                        }
                                    }).disableSelection();
                                });
                            </script>
                            <tr valign="top">
                                <th scope="row">متن یا تبلیغات بالای نوشته هر پست</th>
                                <td>
                                    <div class="notice inline notice-success notice-alt">
                                        <p>میتوانید از کدهای JavaScript تبلیغات کلیکی نیز استفاده نمایید</p>
                                    </div>
                                    <hr/>
                                    <textarea class="widefat code"  name="joapp_header_posts"><?php echo html_entity_decode(get_option("joapp_header_posts", "")) ?></textarea>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">متن یا تبلیغات پایین نوشته هر پست</th>
                                <td>
                                    <div class="notice inline notice-success notice-alt">
                                        <p>میتوانید از کدهای JavaScript تبلیغات کلیکی نیز استفاده نمایید</p>
                                    </div>
                                    <hr/>
                                    <textarea class="widefat code" name="joapp_footer_posts"><?php echo html_entity_decode(get_option("joapp_footer_posts", "")) ?></textarea>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">فرمت تاریخ در اپلیکیشن</th>
                                <td>
                                    <input type="text" name="joapp_date_format" value="<?php echo get_option("joapp_date_format", "Y/m/d"); ?>" />
                                    <hr/>
                                    <div class="notice inline notice-success notice-alt">
                                        <p>فرمت تاریخ به صورت پیش فرض Y/m/d میباشد که در صورت خالی قرار دادن آن تاریخ نمایش داده نمیشود</p>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">نمایش نام نویسنده پست ها</th>
                                <td>
                                    <input name="joapp_show_author" <?php echo (get_option("joapp_show_author", "str_true") === "str_true") ? " checked " : "" ?> type="checkbox" value="str_true" />
                                    <hr/>
                                    <div class="notice inline notice-success notice-alt">
                                        <p>به صورت پیش فرض نام نویسنده پست ها نمایش داده میشود که در این بخش میتوانید آن را مخفی نمایید</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <?php
                        do_action("joapp_api_action_view_tab_posts", $_REQUEST);
                        ?>
                        <hr/>
                        <div id="submit_tab_a">
                            <p class="submit">
                                <input type="submit" class="button button-primary button-hero" value="<?php _e('Save Changes') ?>" />
                            </p>
                            <hr/>
                        </div>
                    </form>
                </div>
                <div id="tab_3" class="tab">
                    <form action="admin.php?page=joapp-api&action=save_tab3&tab=3" method="post">
                        <?php wp_nonce_field(basename(__FILE__), '_nonce'); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">فعال سازی فروشگاه ساز WooCommerce برای اپلیکیشن سایت</th>
                                <td>
                                    <?php
                                    $using_woocommerce = false;
                                    if (class_exists('WooCommerce')) {
                                        echo "مطمئن شوید REST برای افزونه WooCommerce فعال است.<hr/>";
                                        $using_woocommerce = get_option('joapp_api_active_wooceommerce', false);
                                        ?>
                                        <select name="using_woocommerce">
                                            <option <?php echo $using_woocommerce ? "selected='selected'" : '' ?> value="1">فروشگاه من در اپلیکیشن فعال باشد</option>
                                            <option <?php echo (!$using_woocommerce) ? "selected='selected'" : '' ?> value="0">نمیخواهم فروشگاهم در اپلیکیشن باشد</option>
                                        </select>
                                        <?php
                                    } else {
                                        echo "فروشگاه ساز WooCommerce فعال نیست و یا افزونه آن نصب نیست.";
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                        <hr/>
                        <?php if ($using_woocommerce) { ?>
                            <a href="admin.php?page=joapp-api&page_joapp=edit_tags" class="button button-primary button-hero">تنظیمات تگ ها</a>
                            <a href="admin.php?page=joapp-api&page_joapp=edit_custom_menus" class="button button-primary button-hero">تنظیم منو های دلخواه</a>
                            <a href="admin.php?page=joapp-api&page_joapp=edit_states" class="button button-primary button-hero">تنظیمات استان و شهرها</a>
                            <hr/>
                            <table>
                                <tr valign="top">
                                    <th scope="row">استفاده از قالب تسویه حساب JoApp</th>
                                    <td>
                                        <?php
                                        $using_joapp_payment = (get_option("using_joapp_payment", "str_false") === "str_true");
                                        ?>
                                        <input name="using_joapp_payment" type="checkbox" value="true" <?php echo $using_joapp_payment ? "checked='checked'" : "" ?> />
                                        <div class="notice inline notice-warning notice-alt">
                                            <p>در صورت تایید این بخش قالب صفحه خرید بر اساس قالب افزونه مشخص میشود و در غیر این صورت قالب پیش فرض سایتتان برای صفحه تسویه حساب اجرا میگردد</p>
                                        </div>
                                        <hr/>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">رنگ استایل صفحه پرداخت</th>
                                    <td>
                                        <?php
                                        $joapp_api_payment_color = get_option("joapp_api_payment_color", "255,0,68");
                                        ?>
                                        <select style="color:#FFF;background-color: rgb(<?php echo $joapp_api_payment_color; ?>); " name="joapp_api_payment_color">
                                            <option <?php echo ($joapp_api_payment_color === "255,0,68") ? "selected='selected'" : "" ?> style="background-color: rgb(255,0,68)" value="255,0,68">RED</option>
                                            <option <?php echo ($joapp_api_payment_color === "45,179,0") ? "selected='selected'" : "" ?> style="background-color: rgb(45,179,0)" value="45,179,0">GREEN</option>
                                            <option <?php echo ($joapp_api_payment_color === "0,68,255") ? "selected='selected'" : "" ?> style="background-color: rgb(0,68,255)" value="0,68,255">BLUE</option>
                                            <option <?php echo ($joapp_api_payment_color === "255,85,0") ? "selected='selected'" : "" ?> style="background-color: rgb(255,85,0)" value="255,85,0">ORANGE</option>
                                            <option <?php echo ($joapp_api_payment_color === "191,64,128") ? "selected='selected'" : "" ?> style="background-color: rgb(191,64,128)" value="191,64,128">VIOLET</option>
                                            <option <?php echo ($joapp_api_payment_color === "0,0,0") ? "selected='selected'" : "" ?> style="background-color: rgb(0,0,0)" value="0,0,0">BLACK</option>
                                            <option <?php echo ($joapp_api_payment_color === "102,51,0") ? "selected='selected'" : "" ?> style="background-color: rgb(102,51,0)" value="102,51,0">BROWN</option>
                                            <option <?php echo ($joapp_api_payment_color === "134,179,0") ? "selected='selected'" : "" ?> style="background-color: rgb(134,179,0)" value="134,179,0">OLIVE</option>
                                        </select>
                                        <hr/>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">اجازه سفارش خرید میهمان (بدون عضویت)</th>
                                    <td>
                                        <?php
                                        $allow_guest = get_option('joapp_api_allow_guest', false);
                                        ?>
                                        <input name="joapp_api_allow_guest" <?php echo $allow_guest ? "checked='checked'" : '' ?> type="checkbox"  value="1" />
                                    </td>
                                </tr>
                            </table>
                            <hr/>
                            <?php
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
                            ?>
                            <table>
                                <tr valign="top">
                                    <th scope="row">انتخاب فرم های تسویه حساب</th>
                                    <td>
                                        <div class="notice inline notice-warning notice-alt">
                                            <p>انتخاب حداقل یک گزینه برای فرم تسویه حساب اجباری است</p>
                                        </div>
                                        <input <?php echo in_array("FIRST_NAME", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_FIRST_NAME" name="joapp_api_shipping_forms[]" value="FIRST_NAME" />
                                        <label for="joapp_api_shipping_forms_FIRST_NAME">نام</label>
                                        <br/>
                                        <input <?php echo in_array("LAST_NAME", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_LAST_NAME" name="joapp_api_shipping_forms[]" value="LAST_NAME" />
                                        <label for="joapp_api_shipping_forms_LAST_NAME">نام خانوادگی</label>
                                        <br/>
                                        <input <?php echo in_array("COMPANY", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_COMPANY" name="joapp_api_shipping_forms[]" value="COMPANY" />
                                        <label for="joapp_api_shipping_forms_COMPANY">شرکت</label>
                                        <br/>
                                        <input <?php echo in_array("ADDRESS_1", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_ADDRESS_1" name="joapp_api_shipping_forms[]" value="ADDRESS_1" />
                                        <label for="joapp_api_shipping_forms_ADDRESS_1">آدرس 1</label>
                                        <br/>
                                        <input <?php echo in_array("ADDRESS_2", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_ADDRESS_2" name="joapp_api_shipping_forms[]" value="ADDRESS_2" />
                                        <label for="joapp_api_shipping_forms_ADDRESS_2">آدرس 2</label>
                                        <br/>
                                        <input <?php echo in_array("CITY", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_CITY" name="joapp_api_shipping_forms[]" value="CITY" />
                                        <label for="joapp_api_shipping_forms_CITY">استان</label>
                                        <br/>
                                        <input <?php echo in_array("STATE", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_STATE" name="joapp_api_shipping_forms[]" value="STATE" />
                                        <label for="joapp_api_shipping_forms_STATE">شهر</label>
                                        <br/>
                                        <input <?php echo in_array("POSTCODE", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_POSTCODE" name="joapp_api_shipping_forms[]" value="POSTCODE" />
                                        <label for="joapp_api_shipping_forms_POSTCODE">کدپستی</label>
                                        <br/>
                                        <input <?php echo in_array("EMAIL", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_EMAIL" name="joapp_api_shipping_forms[]" value="EMAIL" />
                                        <label for="joapp_api_shipping_forms_EMAIL">ایمیل</label>
                                        <br/>
                                        <input <?php echo in_array("PHONE", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_PHONE" name="joapp_api_shipping_forms[]" value="PHONE" />
                                        <label for="joapp_api_shipping_forms_PHONE">شماره تماس</label>
                                        <br/>
                                        <input <?php echo in_array("NOTE", $shipping_form, true) ? "checked='checked'" : '' ?> type="checkbox" id="joapp_api_shipping_forms_NOTE" name="joapp_api_shipping_forms[]" value="NOTE" />
                                        <label for="joapp_api_shipping_forms_NOTE">یادداشت</label>
                                        <br/>
                                    </td>
                                </tr>
                            </table>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">نمایش حداکثر تعداد محصول در هر دسته از صفحه اصلی فروشگاه</th>
                                    <td>
                                        <input type="number" name="joapp_woo_count_category" value="<?php echo (get_option("joapp_woo_count_category", 10)) ?>"/>
                                        <div class="notice inline notice-warning notice-alt">
                                            <p>هرچه این عدد افزایش یابد صفحه اصلی کند تر خواهد بود پیشنهاد میشود این عدد را بین 5 تا 15 قرار دهید.</p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <hr/>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">مدیریت دسته بندی های WooCommerce</th>
                                    <td>
                                        <div class="notice inline notice-info notice-alt">
                                            <p>ترتیب دسته بندی ها را با گرفتن و کشیدن تغییر دهید. میتوانید از لیست مخفی به لیست فعال بکشید.</p>
                                        </div>
                                        <div style="display: inline-block">
                                            <h3 style="display: block">دسته هایی فعال اپلیکیشن</h3>
                                            <ol id="all_cat_woo" class="connectedSortable" multiple="multiple" style="width: 300px;height: 400px;">
                                                <?php
                                                $json_woo_hidden_list = get_option('joapp_wooapi_hidden_category', "[]");
                                                $json_woo_hidden_list_menu = get_option('joapp_wooapi_hidden_category_menu', "[]");
                                                $hidden_list = json_decode($json_woo_hidden_list);
                                                $hidden_list_menu = json_decode($json_woo_hidden_list_menu);

                                                foreach ($hidden_list as $t) {

                                                    $cat = get_term_by('term_id', $t, 'product_cat');
                                                    if ($cat == null)
                                                        continue;
                                                    $is_menu = in_array($t, $hidden_list_menu);
                                                    ?>
                                                    <li class="li_list" style="background-color: #ccffff">
                                                        <input style="display: none" type="checkbox" checked="checked" value="<?php echo $cat->term_id; ?>" name="hidden_woo_category[]"/>
                                                        <label><?php echo $cat->name; ?>&nbsp;  (<?php echo $cat->count; ?>)</label>
                                                        <span style="float: left">
                                                            <input id="is_menu" type="checkbox" <?php echo $is_menu ? 'checked="checked"' : '' ?>  value="<?php echo $cat->term_id; ?>" name="hidden_woo_category_menu[]"/>
                                                            <label>منو</label>
                                                        </span>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ol>
                                        </div>
                                        <div style="display: inline-block">
                                            <h3 style="display: block">دسته بندی هایی که نمیخواهید نمایش دهید</h3>
                                            <ul id="select_cat_woo" class="connectedSortable" multiple="multiple" style="width: 300px;height: 400px;">
                                                <?php
                                                $json_hidden_list = get_option('joapp_wooapi_hidden_category', "[]");
                                                $hidden_list = json_decode($json_hidden_list);
                                                $args = array(
                                                    'taxonomy' => 'product_cat'
                                                );

                                                $names = array();
                                                $terms = get_categories($args);
                                                $terms2 = $terms;
                                                foreach ($terms as $t) {
                                                    if (in_array($t->term_id, $hidden_list))
                                                        continue;
                                                    ?>
                                                    <li class="li_list" style="background-color: #ffcccc">
                                                        <input style="display: none" type="checkbox" value="<?php echo $t->term_id; ?>" name="hidden_woo_category[]"/>
                                                        <label><?php echo $t->name; ?>&nbsp;  (<?php echo $t->count; ?>)</label>
                                                        <span style="float: left; display: none">
                                                            <input id="is_menu" type="checkbox" value="<?php echo $t->term_id; ?>" name="hidden_woo_category_menu[]"/>
                                                            <label>منو</label>
                                                        </span>
                                                    </li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <script>
                                    jQuery(document).ready(function () {
                                        jQuery("#all_cat_woo,#select_cat_woo").sortable({
                                            connectWith: ".connectedSortable",
                                            beforeStop: function (event, ui) {

                                                var item = jQuery(ui['item']);
                                                var box = jQuery(item).children("input");
                                                var box_menu = jQuery(item).find("#is_menu");
                                                var parent = jQuery(item).parent();
                                                var isselect = (jQuery(parent).attr("id") === "all_cat_woo");
                                                if (isselect) {
                                                    box.attr("checked", "checked");
                                                    box_menu.attr("checked", "checked");
                                                    box_menu.parent().show();
                                                } else {
                                                    box.removeAttr("checked");
                                                    box_menu.removeAttr("checked");
                                                    box_menu.parent().hide();
                                                }
                                            },
                                            stop: function (e, ui) {
                                                var item = jQuery(ui['item']);
                                                var parent = jQuery(item).parent();
                                                var id = jQuery(parent).attr("id");
                                                var allow = ["all_cat_woo", "select_cat_woo"];
                                                if (allow.indexOf(id) < 0) {
                                                    jQuery(this).sortable("cancel");
                                                }
                                            }
                                        }).disableSelection();
                                    });</script>
                            </table>
                            <hr/>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row">تنظیم فیلتر های دسته بندی ها در فروشگاه</th>
                                    <td>
                                        <script>

                                            function removefilter(id_cat, id_attr, title) {
                                                jQuery("#filter_" + id_cat + "_" + id_attr).remove();
                                                jQuery("#select_attr_" + id_cat).append("<option value='" + id_attr + "'>" + title + "</option>");
                                            }

                                            function add_attr_to_cat(id) {
                                                var select = jQuery("#select_attr_" + id);
                                                var selected_option = jQuery('option:selected', select);
                                                var val = jQuery(selected_option).val();
                                                if (jQuery.type(val) === "undefined")
                                                    return;

                                                var txt = jQuery(selected_option).text();
                                                jQuery("#lis_filter_" + id).append("<a id='filter_" + id + "_" + val + "' style='display:block;margin:3px;' onclick=\"removefilter('" + id + "','" + val + "','" + txt + "')\" class='button button-primary' value='" + val + "'>" + txt + "<input type='hidden' name='filter_attr[" + id + "][]' value='" + val + "'></a>");
                                                jQuery(selected_option).remove();
                                            }

                                            function save_timer(id) {
                                                var m = jQuery("#timer_m_" + id).val();
                                                var h = jQuery("#timer_h_" + id).val();
                                                waiting_joapp(true);

                                                var d = new FormData();
                                                d.append("action", 'joapp_ajax_save_timer');
                                                d.append("h", h);
                                                d.append("m", m);
                                                d.append("id", id);

                                                jQuery.ajax({
                                                    url: ajaxurl,
                                                    type: "POST",
                                                    processData: false,
                                                    contentType: false,
                                                    data: d,
                                                }).done(function (data) {
                                                    waiting_joapp(false);
                                                    json = JSON.parse(data);
                                                    alert(json.message);
                                                }).fail(function () {
                                                    waiting_joapp(false);
                                                    alert("خطا در ذخیره سازی داده ها.");
                                                });
                                            }
                                        </script>
                                        <table id="all-plugins-table" class="widefat">
                                            <thead>
                                                <tr>
                                                    <th class="manage-column" scope="col">عنوان دسته</th>
                                                    <th class="manage-column" style="text-align: center" scope="col">فیلد های فیلتر این دسته</th>
                                                    <th class="manage-column" scope="col">افزودن</th>
                                                    <th class="manage-column" scope="col">تایمر معکوس دسته</th>
                                                </tr>
                                            </thead>
                                            <tbody class="plugins">
                                                <?php
                                                $json_hidden_list = get_option('joapp_wooapi_hidden_category', "[]");
                                                $hidden_list = json_decode($json_hidden_list);

                                                $saved_filters = json_decode(get_option("joapp_api_woo_filters", "{}"));

                                                foreach ($hidden_list as $t) {
                                                    $cat = get_term_by('id', $t, 'product_cat');
                                                    ?>
                                                    <tr class="active">
                                                        <td class="plugin-title">
                                                            <strong><?php echo $cat->name ?></strong>
                                                        </td>
                                                        <td class="desc" style="text-align: center">
                                                            <div class="all_filters" id="lis_filter_<?php echo $cat->term_id ?>">
                                                                <?php
                                                                $id = $cat->term_id;
                                                                if (isset($saved_filters->$id)) {
                                                                    foreach ($saved_filters->$id as $f) {
                                                                        $val = wc_attribute_label(wc_attribute_taxonomy_name_by_id($f));
                                                                        echo "<a id='filter_" . $cat->term_id . "_" . $f . "' style='display:block;margin:3px;' onclick=\"removefilter('" . $cat->term_id . "','" . $f . "','" . $val . "')\" class='button button-primary' value='" . $f . "'>" . $val . "<input type='hidden' name='filter_attr[" . $cat->term_id . "][]' value='" . $f . "'></a>";
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <td class="desc">
                                                            <?php
                                                            $A = wc_get_attribute_taxonomies();
                                                            echo "<select id='select_attr_$cat->term_id'>";
                                                            foreach ($A as $a) {
                                                                if (isset($saved_filters->$id) && in_array($a->attribute_id, $saved_filters->$id))
                                                                    continue;
                                                                echo "<option value='$a->attribute_id'>" . $a->attribute_label . "</option>";
                                                            }
                                                            echo "</select>";
                                                            ?>
                                                            <a class="button button-secondary" onclick="add_attr_to_cat('<?php echo $cat->term_id ?>')">افزودن</a>
                                                        </td>
                                                        <?php
                                                        $timestamp = get_option('joapp_api_timer_' . $cat->term_id, 0);
                                                        $time_to = new DateTime(date('Y-m-d H:i:s', $timestamp));
                                                        $time = new DateTime(date('Y-m-d H:i:s'));

                                                        $mod = $time_to->getTimestamp() - $time->getTimestamp();
                                                        if ($mod >= 0) {
                                                            $h = floor($mod / 3600);
                                                            $m = floor(($mod % 3600 ) / 60);
                                                            $s = ($mod % 60);
                                                        } else {
                                                            $h = 0;
                                                            $m = 0;
                                                            $s = 0;
                                                        }
                                                        ?>
                                                        <td class="desc">
                                                            <div style="border: 1px solid black;padding: 10px;background-color: #ddd;">
                                                                <input value="<?php echo $s ?>" disabled="disabled" style="width: 35px" type="text">
                                                                <label>:</label>
                                                                <input id="timer_m_<?php echo $cat->term_id ?>" value="<?php echo $m ?>" style="width: 50px" type="number">
                                                                <label>:</label>
                                                                <input id="timer_h_<?php echo $cat->term_id ?>" value="<?php echo $h ?>" style="width: 50px" type="number">

                                                                <a class="button button-secondary" onclick="save_timer('<?php echo $cat->term_id ?>')">ذخیره تایمر</a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <div class="notice inline notice-info notice-alt">
                                <p>در این بخش میتوانید بخش فیلتر محصولات برنامه را مدیریت نمایید</p>
                            </div>
                            <hr/>
                            <table>
                                <thead>
                                    <tr>
                                        <th>انتخاب تصاویر اسلایدر صفحه اصلی فروشگاه</th>
                                        <th>
                                            <a class="button button-primary" id="upload-button">+</a>
                                        </th>
                                    </tr>
                                </thead>
                            </table>
                            <br/>
                            <div id="stack_slider" style="overflow-x: scroll;width: 100%">
                                <?php
                                $arr_slider_str = get_option("joapp_api_woo_image_slider", "[]");
                                $arr_slider = json_decode($arr_slider_str);

                                foreach ($arr_slider as $value) {
                                    ?>
                                    <div style="margin:5px;">
                                        <input id="image-url" type="hidden" name="image_slider[url][]" value="<?php echo $value->url ?>" />
                                        <img id="image-view" class="thumbnail thumbnail-image" src="<?php echo $value->url ?>" style="height: 80px; width: 160px;cursor: pointer" onclick="delete_slider(this);"/>
                                        <a style="vertical-align: bottom" onclick="delete_slider(this)" class='button button-secoundary'>حذف</a>
                                        <label>رویداد ارجاع مستقیم :</label>
                                        <input dir="ltr" id="image-intent" type="text" name="image_slider[intent][]" value="<?php echo $value->intent ?>" />
                                        <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <script>
                                function help_joapp_intent() {
                                    jQuery("#stack_slider").dialog();
                                }

                                function delete_slider(t) {
                                    if (confirm("آیا این اسلایدر از برنامه حذف شود؟")) {
                                        var parent = jQuery(t).parent();
                                        jQuery(parent).remove();
                                    }
                                }

                                jQuery(document).ready(function () {

                                    var mediaUploader;


                                    jQuery('#upload-button').click(function (e) {

                                        e.preventDefault();

                                        if (mediaUploader) {
                                            mediaUploader.open();
                                            return;
                                        }

                                        mediaUploader = wp.media.frames.file_frame = wp.media({
                                            title: 'انتخاب تصویر اسلایدر 800x400',
                                            button: {
                                                text: 'انتخاب اسلاید'
                                            }, multiple: false});

                                        mediaUploader.on('select', function () {
                                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                                            if (attachment.height !== 400 || attachment.width !== 800) {
                                                alert("خطا : تصویر انتخاب شده در ابعاد 800x400 پیکسل نیست !!!\n\nابعاد تصویر انتخاب شده:" + attachment.width + "x" + attachment.height);
                                                return;
                                            }

                                            var str = "<div style='margin:5px;'><input id='image-url' type='hidden' name='image_slider[url][]' value='" + attachment.url + "' /><img id='image-view' class='thumbnail thumbnail-image' src='" + attachment.url + "' style='height: 80px; width: 160px;cursor: pointer' onclick='delete_slider(this);'/><a style='vertical-align: bottom' onclick='delete_slider(this)' class='button button-secoundary'>حذف</a><label>رویداد ارجاع مستقیم :</label><input dir='ltr' id='image-intent' type='text' name='image_slider[intent][]' value='' /><a target='_blank' href='http://joapp.ir/plugin_update/wordpress_slider_intent.php' style='vertical-align: bottom' class='button button-primary'>?</a></div>"
                                            jQuery('#stack_slider').append(str);
                                        });
                                        mediaUploader.open();
                                    });

                                });
                            </script>
                            <?php
                        }
                        ?>
                        <?php
                        do_action("joapp_api_action_view_tab_store", $_REQUEST);
                        ?>
                        <hr/>
                        <div id="submit_tab_a">
                            <p class="submit">
                                <input type="submit" class="button button-primary button-hero" value="<?php _e('Save Changes') ?>" />
                            </p>
                            <hr/>
                        </div>
                    </form>
                </div>
                <?php
                if (!get_option('permalink_structure', '')) {
                    ?>
                    <br />
                    <p><strong>خطا:</strong> پیوند یکتا را بر روی "روز و نام" تنظیم نمایید. <a target="_blank" class="button" href="options-permalink.php">تغییر پیوند یکتا</a>
                        <?php
                    }
                    ?>

                <div id="tab_4" class="tab">
                    <h2>تنظیمات فیلد های اختیاری پست ها</h2>
                    <table id="all-plugins-table" class="widefat">
                        <thead>
                            <tr>
                                <th class="manage-column" scope="col">ردیف</th>
                                <th class="manage-column" scope="col">عنوان فیلد</th>
                                <th class="manage-column" style="text-align: center" scope="col">کلید فیلد</th>
                                <th class="manage-column" scope="col">نوع فیلد</th>
                            </tr>
                        </thead>
                        <tbody class="plugins">

                            <?php
                            if (isset($_REQUEST['act'])) {
                                if ($_REQUEST['act'] == "new_field") {
                                    if (!empty($_REQUEST['new_field_title']) && !empty($_REQUEST['new_field_type']) && !empty($_REQUEST['new_field_key'])) {
                                        $new_field = array(
                                            'title' => "$_REQUEST[new_field_title]",
                                            'type' => "$_REQUEST[new_field_type]"
                                        );

                                        $fields_save = get_option("JOAPP_API_CUSTOM_FIELDS", "{}");
                                        $fields = json_decode($fields_save);

                                        $key = trim($_REQUEST['new_field_key']);
                                        $fields->$key = $new_field;
                                        $this->save_option("JOAPP_API_CUSTOM_FIELDS", json_encode($fields));
                                    }
                                } elseif ($_REQUEST['act'] == "delete") {
                                    $k = $_REQUEST['key'];
                                    $k = base64_decode($k);
                                    $fields_save = get_option("JOAPP_API_CUSTOM_FIELDS", "{}");
                                    $fields = json_decode($fields_save);
                                    if (array_key_exists($k, $fields)) {
                                        unset($fields->$k);
                                    }
                                    $this->save_option("JOAPP_API_CUSTOM_FIELDS", json_encode($fields));
                                }
                            }
                            $fields_save = get_option("JOAPP_API_CUSTOM_FIELDS", "{}");
                            $fields = json_decode($fields_save);

                            $i = 0;
                            foreach ($fields as $key => $value) {
                                $i++;
                                ?>
                                <tr class="active">
                                    <th class="check-column" scope="row"><?php echo $i ?></th>
                                    <td class="plugin-title">
                                        <strong><?php echo $value->title ?></strong>
                                        <a onclick="return confirm('آیا <?php echo $key; ?> را حذف می کنید؟')" class="row-actions-visible" href="admin.php?page=joapp-api&act=delete&key=<?php echo base64_encode($key) ?>&tab=4">حذف</a>
                                    </td>
                                    <td class="desc" style="text-align: center">
                                        <strong dir="ltr" style="text-align: center"><?php echo $key ?></strong>
                                    </td>
                                    <td class="desc">
                                        <strong><?php echo $value->type ?></strong>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>


                        </tbody>
                    </table>
                    <hr/>
                    <h3>ایجاد فیلد جدید</h3>
                    <form action="admin.php?page=joapp-api" method="post">
                        <input class="selected_tab" type="hidden" value="<?php echo $tab ?>" name="tab"/>
                        <input type="hidden" name="act" value="new_field">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">عنوان فیلد</th>
                                <td><input type="text" name="new_field_title" value=""></td>
                                <th scope="row">کلید فیلد</th>
                                <td><input type="text" dir="ltr" name="new_field_key" value=""></td>
                                <th scope="row">نوع فیلد</th>
                                <td>
                                    <select style="min-width: 100px" name="new_field_type">
                                        <option selected="select">Text</option>
                                        <option>Download</option>
                                        <option>Video</option>
                                        <option>Audio</option>
                                        <option>Image</option>
                                        <option>WebView</option>
                                        <option>IntentJoApp</option>
                                    </select>
                                </td>
                                <td><input type="submit" class="button-primary" value="ذخیره فیلد جدید" /></td>
                            </tr>
                        </table>
                    </form>
                    <?php
                    do_action("joapp_api_action_view_tab_custom_field", $_REQUEST);
                    ?>
                </div>

                <div id="tab_5" class="tab">
                    <?php
                    do_action("joapp_api_action_view_tab_pushe", $_REQUEST);
                    ?>
                    <div class="notice inline notice-info notice-alt">
                        <p>امکان ارسال نوتیفیکیشن در برنامه های ساخته شده از طریق سامانه pushe.co میسر میباشد. <br/>برای این منظور از طریق لینک زیر وارد سامانه pushe.co شوید و با عضویت و ثبت برنامه اندرویدتان در این سامانه اقدام به ارسال نوتیفیکیشن به صورت حرفه ای نمایید.</p>

                    </div>
                    <hr/>
                    <a class="button button-primary" target="_blank" href="http://panel.pushe.co/signin">سامانه Pushe.Co</a>
                </div>
            </div>
            <?php
        } else {
            include_once __DIR__ . "/setting/$page.php";
        }
    }

    function print_controller_actions($name = 'action') {
        ?>
        <div class="tablenav">
            <div class="alignleft actions">
                <select name="<?php echo $name; ?>">
                    <option selected="selected" value="-1">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                </select>
                <input type="submit" class="button-secondary action" id="doaction" name="doaction" value="Apply">
            </div>
            <div class="clear"></div>
        </div>
        <div class="clear"></div>
        <?php
    }

    function get_method_url($controller, $method, $options = '') {
        $url = get_bloginfo('url');
        $base = get_option('joapp_api_base', 'joapp_api');
        $permalink_structure = get_option('permalink_structure', '');
        if (!empty($options) && is_array($options)) {
            $args = array();
            foreach ($options as $key => $value) {
                $args[] = urlencode($key) . '=' . urlencode($value);
            }
            $args = implode('&', $args);
        } else {
            $args = $options;
        }
        if ($controller != 'core') {
            $method = "$controller/$method";
        }
        if (!empty($base) && !empty($permalink_structure)) {
            if (!empty($args)) {
                $args = "?$args";
            }
            return "$url/$base/$method/$args";
        } else {
            return "$url?joapp=$method&$args";
        }
    }

    function save_option($id, $value) {
        $option_exists = (get_option($id, null) !== null);
        if ($option_exists) {
            update_option($id, $value);
        } else {
            add_option($id, $value);
        }
    }

    function get_controllers() {
        $controllers = array();
        $dir = joapp_api_dir();
        $this->check_directory_for_controllers("$dir/controllers", $controllers);
        $this->check_directory_for_controllers(get_stylesheet_directory(), $controllers);
        $controllers = apply_filters('joapp_api_controllers', $controllers);
        return array_map('strtolower', $controllers);
    }

    function check_directory_for_controllers($dir, &$controllers) {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (preg_match('/(.+)\.php$/i', $file, $matches)) {
                $src = file_get_contents("$dir/$file");
                if (preg_match("/class\s+JOAPP_API_{$matches[1]}_Controller/i", $src)) {
                    $controllers[] = $matches[1];
                }
            }
        }
    }

    function controller_is_active($controller) {
        if (defined('JOAPP_API_CONTROLLERS')) {
            $default = JOAPP_API_CONTROLLERS;
        } else {
            $default = 'core';
        }
        $active_controllers = explode(',', get_option('joapp_api_controllers', $default));
        return (in_array($controller, $active_controllers));
    }

    function update_controllers($controllers) {
        if (is_array($controllers)) {
            return implode(',', $controllers);
        } else {
            return $controllers;
        }
    }

    function controller_info($controller) {
        $path = $this->controller_path($controller);
        $class = $this->controller_class($controller);
        $response = array(
            'name' => $controller,
            'description' => '(No description available)',
            'methods' => array()
        );
        if (file_exists($path)) {
            $source = file_get_contents($path);
            if (preg_match('/^\s*Controller name:(.+)$/im', $source, $matches)) {
                $response['name'] = trim($matches[1]);
            }
            if (preg_match('/^\s*Controller description:(.+)$/im', $source, $matches)) {
                $response['description'] = trim($matches[1]);
            }
            if (preg_match('/^\s*Controller URI:(.+)$/im', $source, $matches)) {
                $response['docs'] = trim($matches[1]);
            }
            if (!class_exists($class)) {
                require_once($path);
            }
            $response['methods'] = get_class_methods($class);
            return $response;
        } else if (is_admin()) {
            return "Cannot find controller class '$class' (filtered path: $path).";
        } else {
            $this->error("Unknown controller '$controller'.");
        }
        return $response;
    }

    function controller_class($controller) {
        return "joapp_api_{$controller}_controller";
    }

    function controller_path($controller) {
        $joapp_api_dir = joapp_api_dir();
        $joapp_api_path = "$joapp_api_dir/controllers/$controller.php";
        $theme_dir = get_stylesheet_directory();
        $theme_path = "$theme_dir/$controller.php";
        if (file_exists($theme_path)) {
            $path = $theme_path;
        } else if (file_exists($joapp_api_path)) {
            $path = $joapp_api_path;
        } else {
            $path = null;
        }
        $controller_class = $this->controller_class($controller);
        return apply_filters("{$controller_class}_path", $path);
    }

    function get_nonce_id($controller, $method) {
        $controller = strtolower($controller);
        $method = strtolower($method);
        return "joapp_api-$controller-$method";
    }

    function flush_rewrite_rules() {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    function error($message = 'Unknown error', $http_status = 200, $skey = "") {
        $this->error_title_details('error', $message, "Empty", $skey);
    }

    function error_details($message = 'Unknown error', $details = "Empty", $skey = "") {
        $this->error_title_details('error', $message, $details, $skey);
    }

    function error_title_details($title, $message = 'Unknown error', $details = "Empty", $skey = "") {
        $this->response->respond(array("error" => $message, 'details' => $details), "$title", 200, $skey);
    }

    function include_value($key) {
        return $this->response->is_value_included($key);
    }

    public function getRegisterLink() {
        $reg = get_option("joapp_api_register_link", "");

        if (empty($reg) || strlen($reg) < 6) {
            if (function_exists('wp_registration_url'))
                $reg = wp_registration_url();
            else
                $reg = apply_filters('register_url', site_url('wp-login.php?action=register', 'login'));
        }
        return $reg;
    }

    private function check_update() {
        if (!is_admin())
            exit;
        if (!function_exists('plugins_api')) {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }



        $args = array(
            'slug' => 'joapp-api',
            'fields' => array(
                'version' => true,
            )
        );

        $call_api = plugins_api('plugin_information', $args);

        if (is_wp_error($call_api)) {
            $api_error = $call_api->get_error_message();
        } else {
            if (!empty($call_api->version)) {
                $plugin_data = get_version_joapp_api();
                echo (version_compare($call_api->version, $plugin_data) == 1) ? 'true' : 'false';
                exit;
            }
        }

        echo "null";
        exit;
    }

    public function check_login() {

        if (!isset($_REQUEST['user']) || !isset($_REQUEST['pass'])) {
            return FALSE;
        }

        $user = $_REQUEST['user'];
        $pass = $_REQUEST['pass'];

        if (!wp_login($user, $pass)) {
            return FALSE;
        }

        $u = get_user_by('login', "$user");

        if (is_null($u->ID) && $u->ID > 0) {

            if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
                return FALSE;
            }
            $u = get_user_by('email', "$user");

            if (is_null($u->ID)) {
                return FALSE;
            }
        } else {
            return TRUE;
        }
    }

    public function joapp_api_validate($act, $login = false) {
        global $wpdb;
        $sign_port = $_REQUEST['sign_port'];
        $sign = $_REQUEST['sign'];
        $sign_server = "";
        $db = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_api_keys  WHERE consumer_key =%s", $sign_port);
        $key = $wpdb->get_row($db, ARRAY_A);
        if ($key) {
            $CS = md5($key['consumer_secret']);
            $API_KEY = get_option("joapp_api_key");
            $timestamp = $this->time_stamp('Y-m-d H:00:00');
            $sign_server = md5($CS . $API_KEY . $act . $timestamp);
            if ($sign == $sign_server) {
                $CK = $key['consumer_key'];
                $new_key = md5($timestamp . $CS . $CK);
                if (strlen($new_key) > 16) {
                    $new_key = substr($new_key, 0, 16);
                }

                if ($login && !$this->check_login()) {
                    $this->error_title_details("login", "لطفا وارد حساب کاربری خود شوید", $this->getRegisterLink(), $new_key);
                }

                return $new_key;
            }
        }

        $this->error("خطای پردازش اطلاعات امن");
        exit();
    }

    function time_stamp($format = "Y-m-d H:00:00") {
        $tz = 'UTC';

        $given = new DateTime(date($format));
        $timestamp = $given->getTimestamp();

        $dt = new DateTime("now", new DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        $dt->format($format);
        return "" . $dt->getTimestamp();
    }

}
?>
