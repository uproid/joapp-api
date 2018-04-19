<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}
add_action("joapp_api_action_view_edit_menus", "joapp_api_edit_menu_func");
do_action("joapp_api_action_view_edit_menus");

function getIcon($name) {
    echo plugins_url('joapp-api') . "/assets/icons/$name.jpg";
}

function joapp_api_edit_menu_func() {
    $is_wp = isset($_GET['wp']);
    $str_all_menus = get_option("joapp_api_menus" . ($is_wp ? "_wp" : ''), "[]");
    $all_menus = json_decode($str_all_menus);
    ?>

    <script>
        function new_tag() {
            jQuery("#all_menus").append('<li class="new_joapp_tag"><img onclick="select_icon(this)" class="icon_event_joapp" onclick="icon_one_select(this)" src="<?php getIcon('ic_language'); ?>" id="new_tag_icon" /><label>عنوان منو </label><input type="text" id="new_tag_title"/><label>رویداد منو </label><input type="text" id="new_tag_event"/><a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a></li>');
        }

        function save_menus_joapp() {
            waiting_joapp(true);

            var d = new FormData();

            jQuery(".new_joapp_tag").each(function (index) {
                var title = jQuery(this).children("#new_tag_title").val();
                var event = jQuery(this).children("#new_tag_event").val();
                var icon_src = jQuery(this).children("#new_tag_icon").attr('src');
                var icon = "ic_language";

                var fileNameIndex = icon_src.lastIndexOf("/") + 1;
                var icon = icon_src.substr(fileNameIndex);
                icon = icon.replace(".jpg", "");

                d.append('data[' + index + '][title]', title);
                d.append('data[' + index + '][event]', event);
                d.append('data[' + index + '][icon]', icon);
            });

            d.append("action", 'joapp_ajax_menus');
            d.append("wp", "<?php echo $is_wp ? 'true' : 'false' ?>");

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

        function delete_tag(obj) {
            jQuery(obj).parent().remove();
        }

        var request_img;
        function select_icon(img) {
            request_img = img;
            var icons = jQuery('.div_image_select');
            icons.fadeIn();
            var left = jQuery(img).offset().left;
            var top = jQuery(img).offset().top;
            icons.offset({top: top, left: left - 270});

        }

        function icon_one_select(select) {
            jQuery('.div_image_select').fadeOut();
            jQuery(request_img).attr('src', jQuery(select).attr('src'));
        }

    </script>
    <style>
        .joapp_tag{
            background-color: #006633;
            color: #FFF;
        }

        .icon_event_joapp{
            background-color: red;
            height: 24px;
            width: 24px;
            cursor: pointer;
            border: 1px #999 solid;
            margin: 0px;
            padding: 0px;
            vertical-align: middle;
        }

        .icon_event_joapp:hover{
            border: 1px red solid;
        }

        .div_image_select{
            position:fixed;
            display: none;
            padding: 5px;
            border: 5px solid #000;
            border-radius: 15px;
            background-color: #aaa;
            z-index: 100000;
            width: 238px;
            height: 210px;
        }

    </style>
    <div class="wrap">
        <h2>ویرایش منو ها</h2>
        <hr/>
        <p>همه منو ها:  <a onclick="new_tag()" class="button button-primary">+</a></p>
        <ol id="all_menus">
            <?php
            foreach ($all_menus as $tag) {
                ?>
                <li class="new_joapp_tag">
                    <img onclick="select_icon(this)" class="icon_event_joapp" onclick="icon_one_select(this)" src="<?php getIcon(isset($tag->icon) ? $tag->icon : 'ic_language'); ?>" id="new_tag_icon" />
                    <label>عنوان منو </label>
                    <input value="<?php echo $tag->title ?>" type="text" id="new_tag_title"/>
                    <label>رویداد منو </label>
                    <input value="<?php echo $tag->event ?>" type="text" id="new_tag_event"/>
                    <a onclick="delete_tag(this)" class="button button-cancel">حذف</a>
                    <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
                </li>
                <?php
            }
            ?>
            <li class="new_joapp_tag">
                <img onclick="select_icon(this)" class="icon_event_joapp" onclick="icon_one_select(this)" src="<?php getIcon('ic_language'); ?>" id="new_tag_icon" />
                <label>عنوان منو </label>
                <input type="text" id="new_tag_title"/>
                <label>رویداد منو </label>
                <input type="text" id="new_tag_event"/>
                <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
            </li>
        </ol>
        <hr/>
        <a class="button button-primary" onclick="save_menus_joapp()">ذخیره</a>
        <a class="button button-cancel" href="admin.php?page=joapp-api&tab=<?php echo $is_wp ? "1" : "3" ?>">بازگشت</a>
        <span class="div_image_select">
            <div>
                <img src="<?php getIcon('ic_language'); ?>" class="icon_event_joapp icon_selector_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_alarm'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_highlight_off'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_settings'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_mood'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_mood_bad'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_help'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_cancel'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_local_dining'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_info'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_visibility_off'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_group'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_shopping_cart'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_history'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_close'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_loyalty'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_star'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_card_giftcard'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_open_in_browser'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_headset'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_save'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_place'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_share'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_visibility'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_local_offer'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_shopping_basket'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_build'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_home'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_check_box'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_exit_to_app'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_favorite'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_photo_camera'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_email'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_airplane'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_call'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_map'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_school'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_notifications'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_store'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_chat'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_event'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_person'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_announcement'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_thumb_up'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_thumb_down'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_delete'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_file_download'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_edit'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_file_upload'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_reply'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_phone_android'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_poll'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_local_cafe'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_image'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_add_box'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_attach_money'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
            <div>
                <img src="<?php getIcon('ic_payment'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_flag'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_sms'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_attach'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_work'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_check'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_arrow_left'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
                <img src="<?php getIcon('ic_audiotrack'); ?>" class="icon_event_joapp" onclick="icon_one_select(this)"/>
            </div>
        </span>
    </div>
    <?php
}
?>