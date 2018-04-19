<?php
require_once __DIR__ . "/functions.php";
$color = get_option("joapp_api_payment_color", "255,0,68");
$joapp_alert = "";
global $wp;
$wc_ver = (class_exists('WooCommerce') && wc_coupons_enabled()) ? str_replace(".", "", $woocommerce->version) : "0";

if ($wc_ver >= "320") {
    $order_id = $wp->query_vars['order-pay'];
    $order = wc_get_order($order_id);

    if ($order && $order->get_status() === "pending" && isset($_POST['joapp_apply_coupon'])) {
        $res = wc()->order_factory->get_order($order->id)->apply_coupon($_POST['joapp_coupon_code']);
        if ($res !== TRUE && isset($res->errors['invalid_coupon'])) {
            foreach ($res->errors['invalid_coupon'] as $p) {
                $joapp_alert .= "<p class='woocommerce-error'>" . $p . "</p>";
            }
        } elseif ($res === TRUE) {
            $joapp_alert .= "<p>تخفیف با موفقیت اعمال گردید.</p>";
        }
        $order = wc()->order_factory->get_order($order->id);
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php echo get_bloginfo('name'); ?></title>
        <meta name="HandheldFriendly" content="true">
        <meta name="viewport" content="width=device-width, initial-scale=0.666667, maximum-scale=0.666667, user-scalable=0">
        <meta name="viewport" content="width=device-width">
        <style>
            body{
                direction: rtl;
                text-align: right;
                font-size: 15px;
                background-color: #FFFFF;
                font-family: tahoma;
            }

            p{
                background-color: rgba(<?php echo $color; ?>,0.15);
                color: #000;
                padding:  5px;
                border: 1px #999 solid;
                border-radius: 3px;
                text-align:justify;
            }

            p:empty {
                display: none;
            }

            a{
                padding: 5px;
                text-decoration: none;
                color: rgb(<?php echo $color; ?>);
            }

            mark{
                background-color: transparent;
                color: rgb(<?php echo $color; ?>);
            }

            h2 ,h3{
                text-align: center;
                background-color: rgba(<?php echo $color; ?>,0.2);
                color: #000;
                font-size: 20px;
                padding: 5px;
                border-radius: 3px;
                margin-top: 10px;
                margin-bottom: 0px;
            }

            table{
                border-collapse: collapse;
                width: 100%;
                margin-top: 5px;
            }

            table, th {
                border: 1px solid #888;
            }

            .product-total,.woocommerce-Price-amount{
                float: left;
                font-size: 13px;
                font-weight: bold;
                margin-left:5px;
            }

            .woocommerce-Price-currencySymbol{
                float: left;
                padding-right: 10px;
            }

            th{
                padding: 3px;
                padding-bottom:0px;
                text-align: right;
                background-color:rgba(<?php echo $color; ?>,0.1);
            }

            td{
                padding: 10px 3px 10px 3px;
                text-align: left;
            }

            tr{
                border : 1px solid #888;
                background: #FFF;
            }

            address{
                font-size: 13px;
                background: #eee;
                border-radius:0px 0px 5px 5px;
                margin-left:10px;
                margin-right:10px;
                margin-top:0px;
                margin-bottom: 5px;
                padding:5px;
            }

            ul{
                padding: 0px;
                list-style-type: none;
            }

            .product-name{
                text-align: right;
            }

            .product-quantity{
                border : 1px solid #888;
                background-color: rgba(<?php echo $color; ?>,0.1);
            }

            .product-total,.product-quantity strong{
                border : 0;
                background-color:transparent;
            }

            .button {
                border: 1px solid rgba(<?php echo $color ?>,1.0);
                border-radius: 2px;
                background: rgba(<?php echo $color ?>,1.0);
                padding: 10.5px 21px;
                color: #ffffff;
                font-size: 14px;
                font-weight: bold;
                text-decoration: none;
                vertical-align: middle;
                margin: 1px;
            }
            .button:hover {
                text-shadow: #1e4158 0 1px 0;
                background: rgba(<?php echo $color ?>,0.65);
                color: #fff;
            }
            .button:active {
                text-shadow: #1e4158 0 1px 0;
                background: rgba(<?php echo $color ?>,0.65);
                color: #fff;
            }

            li strong{
                float:left;
            }

            .cancel,.return-to-shop{
                display: none;
                background: rgb(<?php echo $color ?>);
            }

            .wc-backward{
                display: none;
            }

            .wc_payment_methods img{
                height:35px;
                float:left;
                vertical-align:middle;
            }

            .payment_box p{
                background-color:transparent;
                border:0;
                border-radius:0px;
                color:#444;

            }

            .wc_payment_method{
                border-radius:5px;
                background-color:#eee;
                margin :5px;
                padding:10px;
                transition:height 1s ease-out;
            }

            .wc_payment_method label{
                font-weight:bold;

            }

            .woocommerce-error{
                color:#fff;
                background-color:#ff3333;
                margin :10px 0px 10px 0px;
                padding:10px;
                text-align:center;
                font-weight:bold;
                border: 3px solid #000;
                border-radius:5px;
            }

            input[type=text],input[type=password],input[type=number],input[type=tel],input[type=email],select{
                width:95%;
                height:30px;
                margin:5px;
                display:block;

            }

            .woocommerce-message{
                color:#006600;
                background-color:#b3ffb3;
                margin :10px 0px 10px 0px;
                padding:10px;
                text-align:center;
                font-weight:bold;
                border: 3px solid #006600;
                border-radius:5px;
            }

            .woocommerce-info{
                color:#000066;
                background-color:#b3daff;
                margin :10px 0px 10px 0px;
                padding:10px;
                text-align:center;
                font-weight:bold;
                border: 3px solid #000066;
                border-radius:5px;
            }

            .woocommerce-MyAccount-navigation-link{
                padding:100px;
            }
        </style>
    </head>
    <body>
        <div class="main">
            <div class="col-sm-12">
<?php
echo $joapp_alert;
while (have_posts()) :
    the_post();
    if (is_wc_endpoint_url('order-pay') && $wc_ver >= "320" && $order->get_status() === "pending" && isset($_REQUEST['pay_for_order'])) {
        ?>
                        <form style="text-align: center" class="checkout_coupon" method="post">
                            <center>
                                <input style="display: inline;width: 200px;padding: 0px;margin: 0px" type="text" name="joapp_coupon_code" placeholder="<?php esc_attr_e('Coupon code', 'woocommerce'); ?>" id="coupon_code" value="" />
                                <button style="width: 90px;font-size: 12px;padding: 0px;height: 32px;margin-right: 3px" type="submit" class="button" name="joapp_apply_coupon" value="<?php esc_attr_e('Apply coupon', 'woocommerce'); ?>"><?php esc_html_e('Apply coupon', 'woocommerce'); ?></button>
                            </center>
                        </form>
        <?php
    }
    the_content();
    ?>
                <?php endwhile; ?>
            </div>
        </div>
        <script>
            var x = document.getElementsByClassName("wc_payment_method");
            for (var i = 0; i < x.length; i++) {
                x[i].onclick = function () {
                    joapp_hide_all_pay_method();
                    var p = this.getElementsByClassName("payment_box");
                    p[0].style.display = "block";
                };
            }

            function joapp_hide_all_pay_method() {
                var x = document.getElementsByClassName("payment_box");
                for (var i = 0; i < x.length; i++) {
                    x[i].style.display = "none";
                }
            }
        </script>
    </body>
</html>
