<?php
if (!defined('ABSPATH')) {
    exit('error');
}

add_action('wp_ajax_joapp_ajax_menus', 'joapp_ajax_menus');
add_action('wp_ajax_joapp_ajax_save_tags', 'joapp_ajax_save_tags');
add_action('wp_ajax_joapp_ajax_save_cities', 'joapp_ajax_save_cities');
add_action('wp_ajax_joapp_ajax_save_timer', 'joapp_ajax_save_timer');
add_action('wp_ajax_joapp_ajax_save_states', 'joapp_ajax_save_states');
add_action('wp_ajax_joapp_ajax_get_plugins', 'joapp_ajax_get_plugins');

function joapp_ajax_save_timer() {
    if (isset($_POST['m']) && isset($_POST['id']) && isset($_POST['h'])) {
        wp_timezone_override_offset();
        $m = $_POST['m'];
        $h = $_POST['h'];

        $minutes_to_add = $h * 60 + $m;

        $time = new DateTime(date('Y-m-d H:i'));
        $time->add(new DateInterval('PT' . $minutes_to_add . 'M'));

        $stamp = $time->format('Y-m-d H:i');
        save_option("joapp_api_timer_$_POST[id]", "" . $time->getTimestamp());
        exit(json_encode(array("message" => "ذخیره سازی با موفقیت انجام گردید.")));
    }
    exit(json_encode(array("message" => "خطای ذخیره سازی")));
}

function joapp_ajax_menus() {
    if (isset($_POST['data'])) {
        $all_menus = array();
        $all = $_POST['data'];
        foreach ($all as $menus) {
            $title = trim($menus['title']);
            $event = trim($menus['event']);
            $icon = trim($menus['icon']);
            if ($title != "" && $event != "") {
                $all_menus[] = array(
                    'title' => $title,
                    'event' => $event,
                    'icon' => $icon,
                );
            }
        }
        $wp_or_woo = isset($_POST['wp']) && $_POST['wp'] == 'true' ? "_wp" : "";
        save_option("joapp_api_menus$wp_or_woo", json_encode($all_menus));
        exit(json_encode(array("message" => "ذخیره سازی با موفقیت انجام گردید.")));
    }
    exit(json_encode(array("message" => "خطای ذخیره سازی")));
}

function joapp_ajax_save_tags() {
    if (isset($_POST['data'])) {
        $all_tag = array();
        $all = $_POST['data'];
        foreach ($all as $tag) {
            $title = trim($tag['title']);
            $event = trim($tag['event']);
            if ($title != "" && $event != "") {
                $all_tag[] = array(
                    'title' => $title,
                    'event' => $event,
                );
            }
        }

        save_option("joapp_api_tags", json_encode($all_tag));
        exit(json_encode(array("message" => "ذخیره سازی با موفقیت انجام گردید.")));
    }
    exit(json_encode(array("message" => "خطای ذخیره سازی")));
}

function joapp_ajax_save_states() {
    if (isset($_POST['data'])) {
        $all_tag = array();
        $all = $_POST['data'];
        foreach ($all as $tag) {
            $title = trim($tag['title']);
            $event = trim($tag['code']);
            if ($title != "" && $event != "") {
                $all_tag[] = array(
                    'title' => $title,
                    'code' => $event,
                );
            }
        }

        save_option("joapp_api_selected_states", json_encode($all_tag));
        save_option("joapp_api_update_states", time());
        exit(json_encode(array("message" => "ذخیره سازی با موفقیت انجام گردید.")));
    }
    exit(json_encode(array("message" => "خطای ذخیره سازی")));
}

function joapp_ajax_save_cities() {
    if (isset($_POST['data']) && isset($_POST['state'])) {
        $all_tag = array();
        $all = $_POST['data'];
        $st = $_POST['state'];
        foreach ($all as $tag) {

            if ($tag['title'] != "") {
                $all_tag[] = $tag['title'];
            }
        }
        save_option("joapp_api_selected_city_$st", json_encode($all_tag));
        save_option("joapp_api_update_states", time());
        exit(json_encode(array("message" => "ذخیره سازی با موفقیت انجام گردید.")));
    }
    exit(json_encode(array("message" => "خطای ذخیره سازی")));
}

function save_option($id, $value) {
    $option_exists = (get_option($id, null) !== null);
    if ($option_exists) {
        update_option($id, $value);
    } else {
        add_option($id, $value);
    }
}

function joapp_ajax_get_plugins() {
    $url = "https://joapp.ir/plugin_update/wp/joapp_api_plugins.php";
    $string_json = file_get_contents($url);

    if ($string_json == "")
        exit("");

    $json = json_decode($string_json);
    $plugins = $json->plugins;
    $status_plugins = array();

    $count = 0;

    foreach ($plugins as $p) {
        $version_new = (int) str_replace('.', '', $p->version);
        $status = "نامشخص";

        if ($p->type === "plugin") {
            $active = @is_plugin_active($p->slug . '/' . $p->slug . '.php');
            $status = $active ? "<strong style='color:green'>فعال</strong>" : "<strong style='color:red'>غیرفعال</strong>";
            $pdata = @get_plugin_data(plugin_dir_path(__DIR__) . '/' . ($p->slug . '/' . $p->slug . '.php'));
            if (isset($pdata['Version']) && $pdata['Version'] !== "") {
                $version_plugin = (int) str_replace('.', '', $pdata['Version']);

                if ($version_new > $version_plugin) {
                    $status .= "<strong style='color:Red'> (به روز کنید)</strong>";
                    $count++;
                }
            } else {
                $status = "<strong style='color:red'>نصب نیست</strong>";
                $count++;
            }
        } elseif ($p->type === "theme") {
            $pdata = @get_theme_data(get_theme_root() . '/' . $p->slug . "/style.css");
            if (isset($pdata['Version']) && $pdata['Version']) {
                $version_plugin = (int) str_replace('.', '', $pdata['Version']);
                if ($version_new > $version_plugin) {
                    $status = "<strong style='color:Red'>به روز کنید</strong>";
                    $count++;
                }else{
                    $status = "<strong style='color:green'>نصب است</strong>";
                }
            } else {
                $status = "<strong style='color:red'>نصب نیست</strong>";
                $count++;
            }
        }

        save_option("joapp_api_plugin_update_count", $count);
        $p->status = $status;
        $status_plugins[] = $p;
    }

    $result = array('title' => $json->title, 'plugins' => $status_plugins);
    exit(json_encode($result));
}

?>