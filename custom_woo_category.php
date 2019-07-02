<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

add_action('product_cat_edit_form_fields', 'joapp_api_taxonomy_edit_meta_field', 100, 2);
add_action('edit_category_form_fields', 'joapp_api_taxonomy_edit_meta_field', 100, 2);

function joapp_api_taxonomy_edit_meta_field($term) {
    $t_id = $term->term_id;
    ?>
    <hr/>
    <a href="admin.php?page=joapp-api&page_joapp=edit_category&id=<?php echo $t_id; ?>" class="button button-primary button-hero">ویرایش دسته بندی برای اپلیکیشن</a>
    <hr/>
    <?php
}
?>
