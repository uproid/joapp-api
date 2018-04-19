<?php

class JOAPP_API_Comment {

    var $id;      // Integer
    var $name;    // String
    var $url;     // String
    var $date;    // String
    var $content; // String
    var $parent;  // Integer
    var $author;  // Object (only if the user was registered & logged in)
    var $rating;

    function JOAPP_API_Comment($wp_comment = null) {
        if ($wp_comment) {
            $this->import_wp_object($wp_comment);
        }
        do_action("joapp_api_action_comment_init",$this);
    }

    function import_wp_object($wp_comment) {
        global $joapp_api;

        $date_format = $joapp_api->query->date_format;
        $content = apply_filters('comment_text', $wp_comment->comment_content);

        $this->id = (int) $wp_comment->comment_ID;
        $this->name = $wp_comment->comment_author;
        $this->url = $wp_comment->comment_author_url;
        $this->date = date($date_format, strtotime($wp_comment->comment_date));
        if (function_exists("wp_encode_emoji"))
            $this->content = wp_encode_emoji($content);
        else
            $this->content = $content;
        $this->parent = (int) $wp_comment->comment_parent;

        $meta = get_comment_meta($wp_comment->comment_ID);
        $this->rating = "-1";

        if (isset($meta['rating']) && count($meta['rating']) == 1) {
            if (is_numeric($meta['rating'][0]))
                $this->rating = (string) $meta['rating'][0];
        }
        //$this->raw = $wp_comment;

        if (!empty($wp_comment->user_id)) {
            $this->author = new JOAPP_API_Author($wp_comment->user_id);
        } else {
            unset($this->author);
        }
    }

    function handle_submission($user) {
        global $comment, $wpdb;
        add_action('comment_id_not_found', array(&$this, 'comment_id_not_found'));
        add_action('comment_closed', array(&$this, 'comment_closed'));
        add_action('comment_on_draft', array(&$this, 'comment_on_draft'));
        add_filter('comment_post_redirect', array(&$this, 'comment_post_redirect'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['comment_post_ID'] = $_REQUEST['post_id'];
        $_POST['email'] = $user->user_email;
        $_POST['author'] = $user->user_nickname;
        $_POST['url'] = '';
        $_POST['comment'] = $_REQUEST['comment_content'];
        $_POST['parent'] = 0;

        include ABSPATH . 'wp-comments-post.php';
    }

    function comment_id_not_found() {
        global $joapp_api;
        $joapp_api->error("Post ID '{$_REQUEST['post_id']}' not found.");
    }

    function comment_closed() {
        global $joapp_api;
        $joapp_api->error("Post is closed for comments.", 403);
    }

    function comment_on_draft() {
        global $joapp_api;
        $joapp_api->error("You cannot comment on unpublished posts.", 403);
    }

    function comment_post_redirect() {
        global $comment, $joapp_api;
        $status = ($comment->comment_approved) ? 'ok' : 'pending';
        $new_comment = new JOAPP_API_Comment($comment);
        $joapp_api->response->respond($new_comment, $status,200);
    }

}

?>
