<?php

class JOAPP_API_Response {

    function setup() {
        global $joapp_api;
        $this->include_values = array();
        $this->exclude_values = array();
        if ($joapp_api->query->include) {
            $this->include_values = explode(',', $joapp_api->query->include);
        }
        if ($joapp_api->query->exclude) {
            $this->exclude_values = explode(',', $joapp_api->query->exclude);
            $this->include_values = array_diff($this->include_values, $this->exclude_values);
        }

        remove_action('loop_end', 'dsq_loop_end');
    }

    function get_joapp($data, $status = 'ok') {
        global $joapp_api;
        if (is_array($data)) {
            $data = array_merge(array('status' => $status), $data);
        } else if (is_object($data)) {
            $data = get_object_vars($data);
            $data = array_merge(array('status' => $status), $data);
        }
        $data = array_merge(array('joapp_api_version_code' => (INT) get_option("joapp_api_version_code", 1)), $data);
        $R = ($_SERVER['REQUEST_METHOD'] === 'POST' ? "POST" : "GET");

        $data = array_merge(array('v' => JOAPP_API_VERSION), $data);

        $data = array_merge(array('method' => $R), $data);
        $data = array_merge(array('joapp_api_apk_url' => get_option("joapp_api_apk_url", "")), $data);
        $post_view = get_option("joapp_api_post_view", "");
        $data = array_merge(array('post_view' => $post_view), $data);
        $data = apply_filters('joapp_api_encode', $data);

        if (function_exists('joapp_encode')) {
            if (version_compare(PHP_VERSION, '5.3') < 0) {
                $joapp = joapp_encode($data);
            } else {
                $joapp_encode_options = 0;
                if ($joapp_api->query->joapp_encode_options) {
                    $joapp_encode_options = $joapp_api->query->joapp_encode_options;
                }
                $joapp = joapp_encode($data, $joapp_encode_options);
            }
        } else {
            if (!class_exists('Services_JOAPP')) {
                $dir = joapp_api_dir();
                require_once "$dir/library/JOAPP.php";
            }
            $joapp_service = new Services_JOAPP();
            $joapp = $joapp_service->encode($data);
        }

        if ($joapp_api->query->joapp_unescaped_unicode) {
            $callback = array($this, 'replace_unicode_escape_sequence');
            $joapp = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', $callback, $joapp);
        }

        return $joapp;
    }

    function is_value_included($key) {
        if (empty($this->include_values) && empty($this->exclude_values)) {
            return true;
        } else {
            if (empty($this->exclude_values)) {
                return in_array($key, $this->include_values);
            } else {
                return !in_array($key, $this->exclude_values);
            }
        }
    }

    function respond($result, $status, $http_status, $skey = "") {

        global $joapp_api;
        $joapp = $this->get_joapp($result, $status);
        $status_redirect = "redirect_$status";
        if ($joapp_api->query->dev || !empty($_REQUEST['dev'])) {
            if (!headers_sent()) {
                header('HTTP/1.1 200 OK');
                header('Content-Type: text/plain; charset: UTF-8', true);
            } else {
                echo '<pre>';
            }
            echo $this->prettify($joapp);
        } else if (!empty($_REQUEST[$status_redirect])) {
            wp_redirect($_REQUEST[$status_redirect]);
        } else if ($joapp_api->query->redirect) {
            $url = $this->add_status_query_var($joapp_api->query->redirect, $status);
            wp_redirect($url);
        } else if ($joapp_api->query->callback) {
            $this->callback($joapp_api->query->callback, $joapp);
        } else {
            $this->output($joapp, $http_status, $skey);
        }
        exit;
    }

    function output($result, $http_status = 200, $skey = "") {
        $http_status = apply_filters('joapp_api_http_status', $http_status);
        $charset = get_option('blog_charset');
        if (!headers_sent()) {
            status_header($http_status);
            header("Content-Type: application/json; charset=$charset", true);
        }
        if ($skey === "") {
            echo $result;
        } else {
            include_once __DIR__."/chipher.php";
            $encoder = AesCipher::encrypt($skey, $result);
            echo "{".$encoder->getData()."}";
        }
    }

    function callback($callback, $result) {
        $charset = get_option('blog_charset');
        if (!headers_sent()) {
            status_header(200);
            header("Content-Type: application/javascript; charset=$charset", true);
        }
        echo "$callback($result)";
    }

    function add_status_query_var($url, $status) {
        if (strpos($url, '#')) {
            $pos = strpos($url, '#');
            $anchor = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }
        if (strpos($url, '?')) {
            $url .= "&status=$status";
        } else {
            $url .= "?status=$status";
        }
        if (!empty($anchor)) {
            $url .= $anchor;
        }
        return $url;
    }

    function prettify($ugly) {

        $pretty = "";
        $indent = "";
        $last = '';
        $pos = 0;
        $level = 0;
        $string = false;
        while ($pos < strlen($ugly)) {
            $char = substr($ugly, $pos++, 1);
            if (!$string) {
                if ($char == '{' || $char == '[') {
                    if ($char == '[' && substr($ugly, $pos, 1) == ']') {
                        $pretty .= "[]";
                        $pos++;
                    } else if ($char == '{' && substr($ugly, $pos, 1) == '}') {
                        $pretty .= "{}";
                        $pos++;
                    } else {
                        $pretty .= "$char\n";
                        $indent = str_repeat('  ', ++$level);
                        $pretty .= "$indent";
                    }
                } else if ($char == '}' || $char == ']') {
                    $indent = str_repeat('  ', --$level);
                    if ($last != '}' && $last != ']') {
                        $pretty .= "\n$indent";
                    } else if (substr($pretty, -2, 2) == '  ') {
                        $pretty = substr($pretty, 0, -2);
                    }
                    $pretty .= $char;
                    if (substr($ugly, $pos, 1) == ',') {
                        $pretty .= ",";
                        $last = ',';
                        $pos++;
                    }
                    $pretty .= "\n$indent";
                } else if ($char == ':') {
                    $pretty .= ": ";
                } else if ($char == ',') {
                    $pretty .= ",\n$indent";
                } else if ($char == '"') {
                    $pretty .= '"';
                    $string = true;
                } else {
                    $pretty .= $char;
                }
            } else {
                if ($last != '\\' && $char == '"') {
                    $string = false;
                }
                $pretty .= $char;
            }
            $last = $char;
        }
        return $pretty;
    }

    function replace_unicode_escape_sequence($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }

}

?>
