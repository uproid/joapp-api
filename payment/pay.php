<?php

add_action('init', 'joapp_api_start_session', 1);

function joapp_api_start_session() {
    if (!session_id()) {
        session_start();
    }

    if (isset($_REQUEST['joapp_payment_page']) || isset($_SESSION['joapp_payment_page'])) {
        add_filter('template_include', 'joapp_api_contact_page_template', 1);

        function joapp_api_contact_page_template($template) {
            $_SESSION['joapp_payment_page'] = "1";

            $file_name = 'page.php';
            global $joapp_result;
            //$joapp_result = dirname(__FILE__) . '/templates/' . $file_name;
            $joapp_result = dirname(__FILE__) . '/templates_full/' . $file_name;
            do_action("joapp_api_action_payment_template");
            return $joapp_result;
        }

    }
}

?>