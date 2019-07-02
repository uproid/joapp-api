<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}

add_action("joapp_api_action_view_send_notification", "joapp_api_send_notification_func");
do_action("joapp_api_action_view_send_notification");

if (isset($_REQUEST['act']) && $_REQUEST['act'] == "send") {
    $req = array(
        "app_ids" => array("holand.yar"),
        "platform" => 2,
        "data" => array(
            "title" => "عنوان پیام",
            "content" => "محتوای پیام"
        )
    );
}

function joapp_api_send_notification_func() {
    $str_all_tags = get_option("joapp_api_tags", "[]");
    $all_tags = json_decode($str_all_tags);
    ?>
    <div class="wrap">
        <h2>ارسال ناتیفیکیشن با استفاده از API سایت pushe.co</h2>
        <hr/>
        <div>
            <form method="post" action="admin.php?page=joapp-api&page_joapp=send_notification">
                <input name="act" value="send" type="hidden"/>
                Title: <input type="text" name="title_noti"><br/>
                Text: <textarea name="text_noti"></textarea><br/>
                API KEY: <input value="9fe9f2a070792fdf2e396ea70d310362a056e58a" type="text" name="apikey_noti"><br/>
                APP_ID: <input type="text" name="appid_noti"><br/>
                <input type="submit" value="SEND"/>
            </form>
        </div>
    </div>
    <?php
}
?>