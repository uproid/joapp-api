<?php

if (!class_exists('ajaxed_status_page_joapp')) {

    class ajaxed_status_page_joapp {

        public function __construct() {
            global $pagenow, $typenow; //&& $typenow =='page'

            if (is_admin() && $pagenow == 'edit.php') {
                add_filter('admin_footer', array($this, 'joapp_api_insert_ajax_status_page_joapp_script'));
            }


            add_filter('manage_pages_columns', array($this, 'joapp_api_add_new_columns'));
            add_action('manage_pages_custom_column', array($this, 'joapp_api_manage_columns'), 10, 2);

            add_action('wp_ajax_change_status_page_joapp', array($this, 'joapp_api_ajax_change_status_page_joapp'));
        }

        public function joapp_api_change_post_status_page_joapp($post_id, $status_page_joapp) {
            if (!add_post_meta($post_id, 'post_status_page_joapp', $status_page_joapp, true)) {
                update_post_meta($post_id, 'post_status_page_joapp', $status_page_joapp);
            }
        }

        public function joapp_api_add_new_columns($columns) {
            $columns['status_page_joapp'] = "صفحه مهم JoApp";
            return $columns;
        }

        public function joapp_api_manage_columns($column_name, $id) {
            global $wpdb, $post;
            if ("status_page_joapp" == $column_name) {
                $post_status_page_joapp = (get_post_meta($id, "post_status_page_joapp", 'set_false') != 'set_true') ? 'set_false' : 'set_true';
                echo '<div id="psatus">';
                if ($post_status_page_joapp == 'set_false') {
                    $img = plugins_url("assets/false.png", __FILE__);
                    echo "<a href='#----' class='pb' change_to='set_true' pid='$id'><img src='$img' /></a>";
                } else {
                    $img = plugins_url("assets/true.png", __FILE__);
                    echo "<a href='#----' class='pb' change_to='set_false' pid='$id'><img src='$img' /></a>";
                }

                echo '</div>';
            }
        }

        public function joapp_api_insert_ajax_status_page_joapp_script() {
            ?>
            <script type="text/javascript">

                function joapp_api_ajax_change_status_page_joapp(p) {
                    p.fadeOut("500");
                    jQuery.getJSON(ajaxurl,
                            {
                                post_id: p.attr("pid"),
                                action: "change_status_page_joapp",
                                change_to: p.attr("change_to")
                            },
                            function (data) {
                                if (data.error) {
                                    alert(data.error);
                                } else {
                                    jQuery(p).find("img").attr('src', data.text);
                                    p.attr("change_to", data.change_to);
                                    p.fadeIn('500');
                                }
                            }
                    );

                }
                jQuery(document).ready(function () {
                    jQuery(".pb").click(function () {
                        joapp_api_ajax_change_status_page_joapp(jQuery(this));
                    });
                });
            </script>
            <?php

        }

        public function joapp_api_ajax_change_status_page_joapp() {
            if (!isset($_GET['post_id'])) {
                $re['data'] = 'something went wrong ...';
                echo json_encode($re);
                die();
            }
            if (isset($_GET['change_to'])) {
                $this->joapp_api_change_post_status_page_joapp($_GET['post_id'], $_GET['change_to']);
                if ($_GET['change_to'] == "set_true") {
                    $re['text'] = plugins_url("assets/true.png", __FILE__);
                    $re['change_to'] = "set_false";
                } else {
                    $re['text'] = plugins_url("assets/false.png", __FILE__);
                    $re['change_to'] = "set_true";
                }
            } else {
                $re['data'] = 'something went wrong ...';
            }
            echo json_encode($re);
            die();
        }

    }

}

new ajaxed_status_page_joapp();

?>