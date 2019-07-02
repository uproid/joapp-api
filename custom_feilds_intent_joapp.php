<?php
add_action('init', 'joapp_intent_init');

function joapp_intent_init() {
    $args = array(
        'label' => 'پست لینک JoApp',
        'public' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'joapp_intent'),
        'query_var' => true,
        'taxonomies' => array('category'),
        'menu_icon' => 'dashicons-editor-unlink',
        'supports' => array(
            'title',
            'author',
            'thumbnail',
            'excerpt',
            'comments',
        )
    );

    register_post_type('joapp_intent', $args);
}

add_action('admin_init', 'joapp_api_custom_fields_intent_joapp');

function joapp_api_custom_fields_intent_joapp() {
    if (!is_admin())
        return;
    $screens = array('joapp_intent');

    foreach ($screens as $screen) {

        add_meta_box(
                'layers_child_meta_sectionid', 'لینک جایگزین JoApp', 'layers_child_meta_box_callback_intent_joapp', $screen, 'normal', 'high'
        );
    }
}

function joapp_api_custom_fields_intent_joapp_save($post_id) {
    if (!is_admin())
        return;
    $is_autosave = wp_is_post_autosave($post_id);
    $is_revision = wp_is_post_revision($post_id);
    $is_not_editpost = !isset($_POST['action']) || $_POST['action'] != 'editpost';
    $is_valid_nonce = ( isset($_POST['layers_child_meta_box_nonce']) && wp_verify_nonce($_POST['layers_child_meta_box'], basename(__FILE__)) ) ? 'true' : 'false';

    if ($is_autosave || $is_revision || !$is_valid_nonce || $is_not_editpost) {
        return;
    }

    if (!isset($_POST['joapp_intent_url'])) {
        delete_post_meta($post_id, 'joapp_intent_url');
        return;
    }

    $all = $_POST['joapp_intent_url'];

    update_post_meta($post_id, 'joapp_intent_url', $all);
}

function layers_child_meta_box_callback_intent_joapp($post) {
    $url = get_post_meta($post->ID, "joapp_intent_url", TRUE);

    wp_nonce_field('layers_child_meta_box', 'layers_child_meta_box_nonce');
    ?>
    <input style="direction: ltr;min-width: 300px;color:red;border-color: red;font-weight: bold" name="joapp_intent_url" type="text" value="<?php echo $url == "" ? "http://bejo.ir" : $url; ?>"/>
    <a target="_blank" href="https://joapp.ir/plugin_update/wordpress_slider_intent.php#sec174" style="vertical-align: bottom" class="button button-primary">?</a>
    <a style="background-color: #FFF;padding: 0" target="_blank" href="https://joapp.ir/plugin_update/joapp_intent.php" style="vertical-align: bottom" class="button">
        <img style="width: 25px;height: 25px" src="<?php echo plugins_url('joapp-api') . "/assets/icons/ic_build.jpg"; ?>" />
    </a>
    <?php
}

add_action('save_post', 'joapp_api_custom_fields_intent_joapp_save');

?>