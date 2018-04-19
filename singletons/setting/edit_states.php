<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

include_once __DIR__ . "/JoAppState.php";
$joapp_states = new JoAppState();
$all_states = $joapp_states->getSelectedStates();
?>
<script>
    function new_state() {
        jQuery("#all_states").append('<li class="new_joapp_state"><label>عنوان استان </label><input type="text" id="new_state_title"/><label>شناسه استان </label><input type="text" id="new_code_state"/><a onclick="delete_state(this)" class="button button-cancel">حذف</a></li>');
    }

    function save_states_joapp() {
        waiting_joapp(true);

        var d = new FormData();
        var count = 0;

        jQuery(".new_joapp_state").each(function (index) {
            var title = jQuery(this).children("#new_state_title").val();
            var code = jQuery(this).children("#new_code_state").val();

            d.append('data[' + index + '][title]', title);
            d.append('data[' + index + '][code]', code);
            count++;
        });

        if (count == 0) {
            d.append('data', null);
        }
        d.append("action", 'joapp_ajax_save_states');

        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            processData: false,
            contentType: false,
            data: d,
        }).done(function (data) {
            //waiting_joapp(false);
            json = JSON.parse(data);
            document.location = document.location;
        }).fail(function () {
            waiting_joapp(false);
            alert("خطا در ذخیره سازی داده ها.");
        });
    }

    function delete_state(obj) {
        jQuery(obj).parent().remove();
    }
</script>
<style>
    .joapp_state{
        background-color: #006633;
        color: #FFF;
    }
</style>
<div class="wrap">
    <h2>ویرایش استان ها</h2>
    <hr/>
    <p>همه استان ها:  <a onclick="new_state()" class="button button-primary">+</a></p>
    <ol id="all_states">
        <?php
        foreach ($all_states as $state) {
            ?>
            <li class="new_joapp_state">
                <label>عنوان استان </label>
                <input value="<?php echo $state['title'] ?>" type="text" id="new_state_title"/>
                <label>شناسه استان </label>
                <input value="<?php echo $state['code'] ?>" type="text" id="new_code_state"/>
                <a onclick="delete_state(this)" class="button button-cancel">حذف</a>
                <a href="admin.php?page=joapp-api&page_joapp=edit_city&state=<?php echo $state['code']; ?>" class="button button-primary">ویرایش شهرها</a>
            </li>
            <?php
        }
        ?>
    </ol>
    <hr/>
    <a class="button button-primary" onclick="save_states_joapp()">ذخیره</a>
    <a class="button button-cancel" href="admin.php?page=joapp-api&tab=3">بازگشت</a>
</div>