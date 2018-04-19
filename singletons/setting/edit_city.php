<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

if (!isset($_GET['state']))
    exit('error');
$st = $_GET['state'];

include_once __DIR__ . "/JoAppState.php";
$joapp_states = new JoAppState();

$all_cities = $joapp_states->getSelectedCities($st);
?>
<script>
    function new_city() {
        jQuery("#all_cities").append('<li class="new_joapp_city"><label>عنوان شهرستان </label><input type="text" id="new_city_title"/><a onclick="delete_city(this)" class="button button-cancel">حذف</a></li>');
    }

    function save_cities_joapp() {
        waiting_joapp(true);

        var d = new FormData();
        var count = 0;

        jQuery(".new_joapp_city").each(function (index) {
            var title = jQuery(this).children("#new_city_title").val();

            d.append('data[' + index + '][title]', title);
            count++;
        });

        if (count == 0) {
            d.append('data', null);
        }
        
        d.append("state","<?php echo $st; ?>");
        d.append("action", 'joapp_ajax_save_cities');

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

    function delete_city(obj) {
        jQuery(obj).parent().remove();
    }
</script>
<style>
    .joapp_city{
        background-color: #006633;
        color: #FFF;
    }
</style>
<div class="wrap">
    <h2>ویرایش شهرهای استان <?php echo $st ?></h2>
    <hr/>
    <p>همه شهر ها:  <a onclick="new_city()" class="button button-primary">+</a></p>
    <ol id="all_cities">
        <?php
        foreach ($all_cities as $city) {
            ?>
            <li class="new_joapp_city">
                <label>عنوان شهر </label>
                <input value="<?php echo $city ?>" type="text" id="new_city_title"/>
                <a onclick="delete_city(this)" class="button button-cancel">حذف</a>
            </li>
            <?php
        }
        ?>
    </ol>
    <hr/>
    <a class="button button-primary" onclick="save_cities_joapp()">ذخیره</a>
    <a class="button button-cancel" href="admin.php?page=joapp-api&page_joapp=edit_states">بازگشت</a>
</div>