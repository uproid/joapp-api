<?php

/*
  Controller name: Respond
  Controller description: Comment/trackback submission methods
 */

class JOAPP_API_Respond_Controller {

    function submit_comment() {
        global $joapp_api;

        extract($joapp_api->query->get(array('user', 'pass')));
        if ($user) {

            if (!wp_login($user, $pass)) {
                $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
            }

            $u = get_user_by('login', "$user");

            if (is_null($u->ID)) {
                $u = get_user_by('email', "$user");
            }

            if (is_null($u->ID)) {
                $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
            }
        } else {
            $joapp_api->error_details("Not Logined...", $joapp_api->getRegisterLink());
        }

        if (empty($_REQUEST['post_id'])) {
            $joapp_api->error("پست شناسایی نشد");
        }

        if (empty($_REQUEST['comment_content'])) {
            $joapp_api->error("نظر خود را وارد نکرده اید");
        }

        if (!comments_open($_REQUEST['post_id'])) {
            $joapp_api->error("اجازه ثبت نظر برای این پست وجود ندارد");
        }

        nocache_headers();
        $ip = "";
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $post_id = $_REQUEST['post_id'];
        $approved = (get_option("joapp_approved_comments", "str_false") === "str_true") ? 1 : 0;
        if (user_can_edit_post_comments($u->ID, $post_id)) {
            $approved = 1;
        }

        global $joapp_data; 
        $joapp_data = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $u->user_login,
            'user_id' => $u->ID,
            'comment_author_email' => $u->user_email,
            'comment_author_url' => '',
            'comment_content' => $_REQUEST['comment_content'],
            'comment_author_IP' => $ip,
            'comment_agent' => 'JoApp API Agent Android or iOS',
            'comment_date' => date('Y-m-d H:i:s'),
            'comment_date_gmt' => date('Y-m-d H:i:s'),
            'comment_approved' => $approved,
        );

        do_action("joapp_api_action_submit_comment");
        
        $comment_id = wp_insert_comment($joapp_data);
        if (isset($_REQUEST['rating'])) {
            $rating = (int) $_REQUEST['rating'];
            if ($rating && $rating > 0 && $rating <= 5)
                add_comment_meta($comment_id, 'rating', "$rating");
        }

        return array("comment_id" => $comment_id);
    }
}

?>
