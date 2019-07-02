<?php
if (!defined('ABSPATH')) {
    exit('error');
}

if (!function_exists('is_admin') || !is_admin()) {
    exit('error');
}
?>
<div class="wrap plugin-install-tab-featured">
    <h1 class="wp-heading-inline">افزونه های کمکی JoApp API</h1>
    <div class="notice inline notice-info notice-alt">
        <p>در این بخش افزونه های جانبی JoApp API برای افزایش کارایی اپلیکیشن های سرویس WordPress JoApp Pro قرار دارد. فایل ZIP افزونه را دانلود نمایید و در بخش افزونه های وردپرس نصب نمایید. شما نیز میتوانید افزونه های ساخته شده خود را برای انتشار به ایمیل info@bejo.ir ارسال نمایید. <a href="https://api.joapp.ir">مستندات پلاگین نویسی JoApp API</a></p>
        <strong>همهٔ حقوق برای شرکت داده ابزار سپاهان محفوظ است. ©‏ ۱۳۹۷</strong>
    </div>
    <hr/>
    <div id='the-list'>
        <center><h1 style='color:red'>در حال بررسی افزونه های JoApp API</h1></center>
    </div>
</div>
<script>
    jQuery(document).ready(function () {
        get_plugins();
    });
    function get_plugins() {
        waiting_joapp(true);
        var d = new FormData();
        d.append("action", 'joapp_ajax_get_plugins');
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            processData: false,
            contentType: false,
            data: d,
        }).done(function (data) {
            waiting_joapp(false);
            init(data);
        }).fail(function () {
            waiting_joapp(false);
            alert("خطا در دریافت داده ها.");
            init(null);
        });
    }

    function init(data) {
        jQuery("#the-list").html("");
        if (data === null || data === "") {
            jQuery("#the-list").html("<center><h1 style='color:red'>خطای بررسی افزونه های موجود از دیتاسنتر</h1></center>");
            return;
        }
        jQuery("#the-list").html("");
        var json = JSON.parse(data);

        if (json.plugins.length === 0) {
            jQuery("#the-list").html("<center><h1 style='color:red'>در حال حاضر افزونه کمکی بر روی دیتاسنتر وجود ندارد</h1></center>");
            return;
        }

        var theme = `<div class="plugin-card plugin-card-theme-check">
	<div class="plugin-card-top">
		<div class="name column-name">
			<h3>{TITLE}<img src="{ICON}" class="plugin-icon"></h3>
		</div>
		<div class="action-links">
			<ul class="plugin-action-buttons">
				<li>
					<a class="install-now button button-primary" target="_blank" href="{DOWNLOAD}" >{BUTTON}</a>
				</li>
			</ul>
		</div>
		<div style="margin-left:0" class="desc column-description">
			<p>{DESCRIPTION}</p>
			<p class="authors"> <cite>بدست <a>{AUTHOR}</a></cite></p>
		</div>
	</div>
	<div class="plugin-card-bottom">
		<div class="vers column-rating">
			<span class="num-ratings" aria-hidden="true"><strong>JoApp API موردنیاز :</strong> {JAV}</span>
                        <span class="num-ratings" aria-hidden="true"><strong>وضعیت: </strong> {STATUS}</span>
		</div>
		<div class="column-updated"><strong>نسخه:</strong> {VERSION}</div>
		<div class="column-updated"><strong>قیمت:</strong> {FEE}</div>
	</div>
</div>`;
        for (var i = 0; i < json.plugins.length; i++) {
            var plugin = json.plugins[i];
            var new_theme = theme.replace("{TITLE}", plugin.title);
            new_theme = new_theme.replace("{ICON}", plugin.icon);
            new_theme = new_theme.replace("{DESCRIPTION}", plugin.description);
            new_theme = new_theme.replace("{VERSION}", plugin.version);
            new_theme = new_theme.replace("{JAV}", plugin.joapp_api);
            new_theme = new_theme.replace("{DOWNLOAD}", plugin.download);
            new_theme = new_theme.replace("{AUTHOR}", plugin.author);
            new_theme = new_theme.replace("{BUTTON}", plugin.button);
            new_theme = new_theme.replace("{FEE}", plugin.fee);
            new_theme = new_theme.replace("{STATUS}", plugin.status);
            jQuery("#the-list").append(new_theme);
        }
    }
</script>