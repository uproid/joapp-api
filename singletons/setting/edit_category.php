<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}
add_action("joapp_api_action_view_edit_category", "joapp_api_edit_category_func");
do_action("joapp_api_action_view_edit_category");

function getIcon($name) {
    echo plugins_url('joapp-api') . "/assets/icons/$name.jpg";
}

function joapp_api_edit_category_func() {
    $t_id = isset($_GET['id']) ? $_GET['id'] : -999;
    if ($t_id == -999) {
        wp_die("دسته بندی مورد نظر یافت نشد.");
    }
    $term = get_term_by('term_taxonomy_id', $t_id);
    if (isset($_POST['save'])) {
        add_action("joapp_api_action_save_category", "joapp_api_edit_save_category_func");
        do_action("joapp_api_action_save_category", $t_id);
    }
    ?>
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
        <h3>ویرایش دسته بندی :<?php echo $term->name; ?></h3>
        <form method="post" action="admin.php?page=joapp-api&page_joapp=edit_category&id=<?php echo $t_id; ?>">
            <table class="form-table">
                <tbody>
                    <?php
                    wp_enqueue_media();
                    $content = get_option("joapp_api_taxonomy_$t_id", "[]");


                    $icon = get_option("joapp_api_icon_category_$t_id", "ic_mood");
                    $post_views = get_option("joapp_api_taxonomy_post_view_$t_id", "one_news_large");
                    $is_product = ($term->taxonomy === "product_cat");
                    $type_layout = $is_product ? "one_product": "one_news";
                    ?>
                    <tr>
                        <th>حالت نمایش پست ها در اپلیکیشن برای این دسته بندی</th>
                        <td>
                            <div class="form-field">
                                <select name="joapp_api_taxonomy_post_view">
                                    <option <?php echo ($post_views == "{$type_layout}_large") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_large">نرمال</option>
                                    <option <?php echo ($post_views == "{$type_layout}_medium") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_medium">نرمال کوچک</option>
                                    <option <?php echo ($post_views == "{$type_layout}_large_2") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_large_2">اینستاگرامی</option>
                                    <option <?php echo ($post_views == "{$type_layout}_chat") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_chat">گفت و گو</option>
                                    <option <?php echo ($post_views == "{$type_layout}_nil") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_nil">بند انگشتی</option>
                                    <option <?php echo ($post_views == "{$type_layout}_row") ? "selected='selected'" : '' ?> value="<?php echo $type_layout ?>_row">ردیفی</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <?php
                    if (!$is_product) {
                        ?>
                        <tr>
                            <th>آیکون دسته بندی حالت نوار انتخاب</th>
                            <td>
                                <div>
                                    <img onclick="select_icon(this)" class="icon_event_joapp" onclick="icon_one_select(this)" src="<?php getIcon($icon); ?>" id="new_tag_icon" />
                                    <input value="<?php echo $icon; ?>" type="hidden" id="joapp_api_icon_category" name="joapp_api_icon_category"/>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <th>عنوان جایگزین در اپلیکیشن</th>
                        <td>
                            <div>
                                <input value="<?php echo get_option("joapp_api_title_category_$t_id", $term->name); ?>" type="text" id="joapp_api_title_category" name="joapp_api_title_category"/>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>انتخاب تصاویر اسلایدر بالای این دسته بندی</th>
                        <td>
                            <div class="form-field">
                                <div class="notice inline notice-info notice-alt">
                                    <p>افزودن تصویر <a class="button button-primary" id="upload-button">+</a>&nbsp;
                                        <?php if ($term->taxonomy != "product_cat") { ?>|&nbsp;نوع اسلایدر :&nbsp;
                                            <select name="joapp_api_taxonomy_circle_slider_type">
                                                <?php
                                                $is_circle_slider = get_option("joapp_api_taxonomy_circle_slider_type_$t_id", "0") === "1";
                                                ?>
                                                <option value="0" <?php echo $is_circle_slider ? "" : "selected='selected'" ?> type="tile">موزائیکی</option>
                                                <option value="1" <?php echo (!$is_circle_slider) ? "" : "selected='selected'" ?> type="circle">چرخشی</option>
                                            </select>
                                        <?php } ?>
                                    </p>
                                </div>
                                <hr/>
                                <ul id="stack_slider" style="overflow-x: scroll;width: 100%">
                                    <?php
                                    $arr_slider = json_decode($content);

                                    foreach ($arr_slider as $value) {
                                        ?>
                                        <li style="margin:5px;">
                                            <input id="image-url" type="hidden" name="image_slider[url][]" value="<?php echo $value->url ?>" />
                                            <img id="image-view" class="thumbnail thumbnail-image" src="<?php echo $value->url ?>" style="height: 80px; width: 160px;cursor: pointer" onclick="delete_slider(this);"/>
                                            &nbsp;|&nbsp;<a style="vertical-align: bottom" onclick="delete_slider(this)" class='button button-secoundary'>حذف</a>
                                            <label>رویداد ارجاع مستقیم :</label>
                                            <input style="width: 300px;" dir="ltr" id="image-intent" type="text" name="image_slider[intent][]" value="<?php echo $value->intent ?>" />
                                            <a target="_blank" href="https://joapp.ir/plugin_update/wordpress_slider_intent.php" style="vertical-align: bottom" class="button button-primary">?</a>
                                            <hr/>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <script>
                                    var request_img;
                                    function icon_one_select(select) {
                                        jQuery('.div_image_select').fadeOut();
                                        var src = jQuery(select).attr('src');
                                        jQuery(request_img).attr('src', src);
                                        var fileNameIndex = src.lastIndexOf("/") + 1;
                                        var icon = src.substr(fileNameIndex);
                                        icon = icon.replace(".jpg", "");
                                        jQuery("#joapp_api_icon_category").val(icon);
                                    }
                                    function select_icon(img) {
                                        request_img = img;
                                        var icons = jQuery('.div_image_select');
                                        icons.fadeIn();
                                        var left = jQuery(img).offset().left;
                                        var top = jQuery(img).offset().top;
                                        icons.offset({top: top, left: left - 270});

                                    }
                                    jQuery(document).ready(function () {
                                        jQuery("#stack_slider").sortable();
                                    });
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
                                                if ((attachment.height !== 180 || attachment.width !== 360) && (attachment.height !== 400 || attachment.width !== 800)) {
                                                    alert("خطا : تصویر انتخاب شده در ابعاد 360x180 پیکسل یا 400x800 نیست !!!\n\nابعاد تصویر انتخاب شده:" + attachment.width + "x" + attachment.height);
                                                    return;
                                                }
                                                var str = "<li style='margin:5px;'><input id='image-url' type='hidden' name='image_slider[url][]' value='" + attachment.url + "' /><img id='image-view' class='thumbnail thumbnail-image' src='" + attachment.url + "' style='height: 80px; width: 160px;cursor: pointer' onclick='delete_slider(this);'/> | <a style='vertical-align: bottom' onclick='delete_slider(this)' class='button button-secoundary'>حذف</a> <label>رویداد ارجاع مستقیم :</label> <input style='width: 150px;' dir='ltr' id='image-intent' type='text' name='image_slider[intent][]' value='' /> <a target='_blank' href='https://joapp.ir/plugin_update/wordpress_slider_intent.php' style='vertical-align: bottom' class='button button-primary'>?</a><hr/></li>"
                                                jQuery('#stack_slider').append(str);
                                            });
                                            mediaUploader.open();
                                        });

                                    });

                                </script>
                            </div>
                        <td>
                    </tr>
                </tbody>
            </table>
            <hr/>
            <input value="true" name="save" type="hidden"/>
            <input type="submit" value="ذخیره" class="button button-primary" />
            <a href="edit-tags.php?taxonomy=<?php echo $term->taxonomy ?>" class="button">بازگشت</a>
        </form>
    </div>
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
    <?php
}

function joapp_api_edit_save_category_func($t_id) {
    if (isset($_POST['joapp_api_taxonomy_post_view'])) {
        update_option("joapp_api_taxonomy_post_view_$t_id", $_POST['joapp_api_taxonomy_post_view']);
    }

    if (isset($_POST['joapp_api_taxonomy_circle_slider_type'])) {
        update_option("joapp_api_taxonomy_circle_slider_type_$t_id", $_POST['joapp_api_taxonomy_circle_slider_type']);
    }

    if (isset($_POST['joapp_api_icon_category'])) {
        update_option("joapp_api_icon_category_$t_id", $_POST['joapp_api_icon_category']);
    }
    if (isset($_POST['joapp_api_title_category'])) {
        if (trim($_POST['joapp_api_title_category']) !== "")
            update_option("joapp_api_title_category_$t_id", $_POST['joapp_api_title_category']);
        else {
            delete_option("joapp_api_title_category_$t_id");
        }
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
    