<?php
add_action('product_cat_edit_form_fields', 'joapp_api_taxonomy_edit_meta_field', 100, 2);
add_action('edit_category_form_fields', 'joapp_api_taxonomy_edit_meta_field', 100, 2);

function joapp_api_taxonomy_edit_meta_field($term) {
    $t_id = $term->term_id;
    wp_enqueue_media();
    $content = get_option("joapp_api_taxonomy_$t_id", "[]");
    if ($term->taxonomy != "product_cat") {
        $post_views = get_option("joapp_api_taxonomy_post_view_$t_id", "one_news_large");
        ?>
        <tr style="border-top: 1px solid red">
            <th>حالت نمایش پست ها در اپلیکیشن برای این دسته بندی</th>
            <td>
                <div class="form-field">
                    <select name="joapp_api_taxonomy_post_view">
                        <option <?php echo ($post_views == "one_news_large") ? "selected='selected'" : '' ?> value="one_news_large">نرمال</option>
                        <option <?php echo ($post_views == "one_news_medium") ? "selected='selected'" : '' ?> value="one_news_medium">نرمال کوچک</option>
                        <option <?php echo ($post_views == "one_news_large_2") ? "selected='selected'" : '' ?> value="one_news_large_2">اینستاگرامی</option>
                        <option <?php echo ($post_views == "one_news_chat") ? "selected='selected'" : '' ?> value="one_news_chat">گفت و گو</option>
                        <option <?php echo ($post_views == "one_news_nil") ? "selected='selected'" : '' ?> value="one_news_nil">بند انگشتی</option>
                        <option <?php echo ($post_views == "one_news_row") ? "selected='selected'" : '' ?> value="one_news_row">ردیفی</option>
                    </select>
                </div>
            </td>
        </tr>
    <?php } ?>
    <tr style="border-top: 1px solid red">
        <th>انتخاب تصاویر اسلایدر بالای این دسته بندی</th>
        <td>
            <div class="form-field">
                <div class="notice inline notice-info notice-alt">
                    <p>افزودن تصویر <a class="button button-primary" id="upload-button">+</a></p>
                </div>
                <hr/>
                <div id="stack_slider" style="overflow-x: scroll;width: 100%">
                    <?php
                    $arr_slider = json_decode($content);

                    foreach ($arr_slider as $value) {
                        ?>
                        <div style="margin:5px;">
                            <input id="image-url" type="hidden" name="image_slider[url][]" value="<?php echo $value->url ?>" />
                            <img id="image-view" class="thumbnail thumbnail-image" src="<?php echo $value->url ?>" style="height: 80px; width: 160px;cursor: pointer" onclick="delete_slider(this);"/>
                            <hr/>
                            <a style="vertical-align: bottom" onclick="delete_slider(this)" class='button button-secoundary'>حذف</a>
                            <label>رویداد ارجاع مستقیم :</label>
                            <input style="width: 300px;" dir="ltr" id="image-intent" type="text" name="image_slider[intent][]" value="<?php echo $value->intent ?>" />
                            <a target="_blank" href="http://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
                            <hr/>
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
                                title: 'انتخاب تصویر اسلایدر 360x180',
                                button: {
                                    text: 'انتخاب اسلاید'
                                }, multiple: false});

                            mediaUploader.on('select', function () {
                                var attachment = mediaUploader.state().get('selection').first().toJSON();
                                if (attachment.height !== 180 || attachment.width !== 360) {
                                    alert("خطا : تصویر انتخاب شده در ابعاد 360x180 پیکسل نیست !!!\n\nابعاد تصویر انتخاب شده:" + attachment.width + "x" + attachment.height);
                                    return;
                                }

                                var str = "<div style='margin:5px;'><input id='image-url' type='hidden' name='image_slider[url][]' value='" + attachment.url + "' /><img id='image-view' class='thumbnail thumbnail-image' src='" + attachment.url + "' style='height: 80px; width: 160px;cursor: pointer' onclick='delete_slider(this);'/><hr/><a style='vertical-align: bottom' onclick='delete_slider(this)' class='button button-secoundary'>حذف</a> <label>رویداد ارجاع مستقیم :</label> <input style='width: 150px;' dir='ltr' id='image-intent' type='text' name='image_slider[intent][]' value='' /> <a target='_blank' href='http://joapp.ir/plugin_update/wordpress_slider_intent.php' style='vertical-align: bottom' class='button button-primary'>?</a><hr/></div>"
                                jQuery('#stack_slider').append(str);
                            });
                            mediaUploader.open();
                        });

                    });
                </script>
            </div>
        <td>
    </tr>
    <?php
}

add_action('edited_product_cat', 'joapp_api_save_taxonomy_custom_meta', 100, 2);
add_action('edited_category', 'joapp_api_save_taxonomy_custom_meta', 100, 2);

function joapp_api_save_taxonomy_custom_meta($term_id) {
    $t_id = $term_id;

    if (!isset($_POST['action']) || $_POST['action'] !== "editedtag")
        return;

    if (isset($_POST['joapp_api_taxonomy_post_view'])) {
        update_option("joapp_api_taxonomy_post_view_$t_id", $_POST['joapp_api_taxonomy_post_view']);
    }

    if (isset($_POST['image_slider'])) {

        $arr = $_POST['image_slider'];

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
        update_option("joapp_api_taxonomy_$t_id", json_encode($res));
    } else {
        update_option("joapp_api_taxonomy_$t_id", '[]');
    }
}
?>
