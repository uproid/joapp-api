<?php
add_action('admin_init', 'joapp_api_custom_fields_products');

function joapp_api_custom_fields_products() {
    if (!is_admin())
        return;
    $screens = array('product', 'post', 'page');

    foreach ($screens as $screen) {

        add_meta_box(
                'layers_child_meta_sectionid', 'زمینه های اختیاری JoApp', 'joapp_api_layers_child_meta_box_callback', $screen, 'normal', 'high'
        );
    }
}

function joapp_api_custom_fields_products_save($post_id) {
    if (!is_admin())
        return;
    $is_autosave = wp_is_post_autosave($post_id);
    $is_revision = wp_is_post_revision($post_id);
    $is_not_editpost = !isset($_POST['action']) || $_POST['action'] != 'editpost';
    $is_valid_nonce = ( isset($_POST['layers_child_meta_box_nonce']) && wp_verify_nonce($_POST['layers_child_meta_box'], basename(__FILE__)) ) ? 'true' : 'false';

    if ($is_autosave || $is_revision || !$is_valid_nonce || $is_not_editpost) {
        return;
    }

    if (!isset($_POST['joapp_fealds'])) {
        delete_post_meta($post_id, 'joapp_fealds');
        return;
    }

    $all = $_POST['joapp_fealds'];
    $code = array();

    $i = 0;
    foreach ($all['type'] as $value) {
        if (isset($all['type'][$i]) && $all['type'][$i] != "" && isset($all['title'][$i]) && $all['title'][$i] != "" && isset($all['value'][$i]) && $all['value'][$i] != "") {
            $code[] = array(
                'title' => $all['title'][$i],
                'type' => $all['type'][$i],
                'value' => array($all['value'][$i]),
                'key' => "joapp_field_$i"
            );
        }
        $i++;
    }

    $str = json_encode($code, JSON_UNESCAPED_UNICODE);
    update_post_meta($post_id, 'joapp_fealds', $str);
}

function joapp_api_layers_child_meta_box_callback($post) {
    $all_str = get_post_meta($post->ID, "joapp_fealds", TRUE);

    wp_nonce_field('layers_child_meta_box', 'layers_child_meta_box_nonce');
    $all = json_decode($all_str);
    ?>
    <p><strong>نکته :</strong>تمامی فیلد ها را پر کنید. در غیر این صورت زمینه ساخته شده حذف میشود.</p>
    <table id="tbl_joapp_feild">
        <tr>
            <th style="width: 100px;">عنوان</th>
            <th style="width: 100px;">نوع</th>
            <th>مقدار</th>
            <th style="width: 50px;"></th>
        </tr>

        <?php
        if (count($all) > 0) {
            foreach ($all as $cf) {
                ?>
                <tr>
                    <td>
                        <input type="text" name="joapp_fealds[title][]" value="<?php echo $cf->title; ?>" style="width: 100px;"/>
                    </td>
                    <td>
                        <select name="joapp_fealds[type][]"  style="width: 100px;">
                            <option <?php echo $cf->type != "Text" ? '' : 'selected="select"' ?>>Text</option>
                            <option <?php echo $cf->type != "Download" ? '' : 'selected="select"' ?> >Download</option>
                            <option <?php echo $cf->type != "Video" ? '' : 'selected="select"' ?>>Video</option>
                            <option <?php echo $cf->type != "Audio" ? '' : 'selected="select"' ?>>Audio</option>
                            <option <?php echo $cf->type != "Image" ? '' : 'selected="select"' ?>>Image</option>
                            <option <?php echo $cf->type != "WebView" ? '' : 'selected="select"' ?>>WebView</option>
                            <option <?php echo $cf->type != "IntentJoApp" ? '' : 'selected="select"' ?>>IntentJoApp</option>
                        </select>
                    </td>
                    <td>
                        <input value="<?php echo $cf->value[0]; ?>" type="text" name="joapp_fealds[value][]"  style="width: 250px;"/>
                    </td>
                    <td>
                        <a onclick="delete_joapp_field(this)" class="button button-cancel">حذف</a>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </table>
    <hr/>
    <table id="new_joapp_feild" style="display: none">
        <tr>
            <td>
                <input type="text" name="joapp_fealds[title][]"  style="width: 100px;"/>
            </td>
            <td>
                <select name="joapp_fealds[type][]"  style="width: 100px;">
                    <option selected="select">Text</option>
                    <option>Download</option>
                    <option>Video</option>
                    <option>Audio</option>
                    <option>Image</option>
                    <option>WebView</option>
                    <option>IntentJoApp</option>
                </select>
            </td>
            <td>
                <input type="text" name="joapp_fealds[value][]"  style="width: 250px;"/>
            </td>
            <td>
                <a onclick="delete_joapp_field(this)" class="button button-cancel">حذف</a>
            </td>
        </tr>
    </table>
    <a onclick="add_joapp_field()" class="button button-primary">جدید</a>
    <script>
        function add_joapp_field() {
            jQuery("#tbl_joapp_feild").append(jQuery("#new_joapp_feild").html());
        }

        function delete_joapp_field(feild) {
            var tr = jQuery(feild).parent().parent();
            jQuery(tr).remove();
        }
    </script>
    <?php
}

add_action('save_post', 'joapp_api_custom_fields_products_save');
?>