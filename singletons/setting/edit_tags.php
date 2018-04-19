<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

add_action("joapp_api_action_view_edit_tags", "joapp_api_edit_tags_func");
do_action("joapp_api_action_view_edit_tags");

function joapp_api_edit_tags_func() {
    $str_all_tags = get_option("joapp_api_tags", "[]");
    $all_tags = json_decode($str_all_tags);
    ?>

    <script>
        function new_tag() {
            jQuery("#all_tags").append('<li class="new_joapp_tag"><label>عنوان تگ </label><input type="text" id="new_tag_title"/><label>رویداد تگ </label><input type="text" id="new_tag_event"/><a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a></li>');
        }

        function save_tags_joapp() {
            waiting_joapp(true);

            var d = new FormData();

            jQuery(".new_joapp_tag").each(function (index) {
                var title = jQuery(this).children("#new_tag_title").val();
                var event = jQuery(this).children("#new_tag_event").val();

                d.append('data[' + index + '][title]', title);
                d.append('data[' + index + '][event]', event);
            });

            d.append("action", 'joapp_ajax_save_tags');

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
    </script>
    <style>
        .joapp_tag{
            background-color: #006633;
            color: #FFF;
        }
    </style>
    <div class="wrap">
        <h2>ویرایش تگ ها</h2>
        <hr/>
        <p>همه تگ ها:  <a onclick="new_tag()" class="button button-primary">+</a></p>
        <ol id="all_tags">
            <?php
            foreach ($all_tags as $tag) {
                ?>
                <li class="new_joapp_tag">
                    <label>عنوان تگ </label>
                    <input value="<?php echo $tag->title ?>" type="text" id="new_tag_title"/>
                    <label>رویداد تگ </label>
                    <input value="<?php echo $tag->event ?>" type="text" id="new_tag_event"/>
                    <a onclick="delete_tag(this)" class="button button-cancel">حذف</a>
                    <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
                </li>
                <?php
            }
            ?>
            <li class="new_joapp_tag">
                <label>عنوان تگ </label>
                <input type="text" id="new_tag_title"/>
                <label>رویداد تگ </label>
                <input type="text" id="new_tag_event"/>
                <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
            </li>
        </ol>
        <hr/>
        <a class="button button-primary" onclick="save_tags_joapp()">ذخیره</a>
        <a class="button button-cancel" href="admin.php?page=joapp-api&tab=3">بازگشت</a>
    </div>
    <?php
}
?>