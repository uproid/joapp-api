<?php

class JOAPP_API_Author {

    var $id;          // Integer
    var $slug;        // String
    var $name;        // String
    var $first_name;  // String
    var $last_name;   // String
    var $nickname;    // String
    var $url;         // String
    var $description; // String
    var $avatar;      // Object

    function JOAPP_API_Author($id = null) {
        if ($id) {
            $this->id = (int) $id;
        } else {
            $this->id = (int) get_the_author_meta('ID');
        }
        $this->set_value('slug', 'user_nicename');
        $this->set_value('name', 'display_name');
        $this->set_value('email', 'user_email');
        $this->set_value('first_name', 'first_name');
        $this->set_value('last_name', 'last_name');
        $this->set_value('nickname', 'nickname');
        $this->set_value('url', 'user_url');
        $this->set_value('description', 'description');
        $this->set_author_meta();
        $this->set_avatar();
        do_action("joapp_api_action_author_init", $this);
    }

    function set_avatar() {
        if (isset($this->email)) {
            $thumbnail = array(
                'thumbnail' => array(
                    'url' => "https://secure.gravatar.com/avatar/" . $this->email . "?s=75&d=" . urlencode("https://s.gravatar.com/avatar/00d60277d09f7a61d967d693abbecc72?s=80&r=x"),
                    'height' => 75,
                    'width' => 75
                ),
                'full' => array(
                    'url' => "https://secure.gravatar.com/avatar/" . $this->email . "?s=1024&d=" . urlencode("https://s.gravatar.com/avatar/00d60277d09f7a61d967d693abbecc72?s=1024&r=x"),
                    'height' => 1024,
                    'width' => 1024
                )
            );
        } else {
            $thumbnail = array(
                'thumbnail' => array(
                    'url' => "https://s.gravatar.com/avatar/00d60277d09f7a61d967d693abbecc72?s=80&r=x",
                    'height' => 75,
                    'width' => 75
                ),
                'full' => array(
                    'url' => "https://s.gravatar.com/avatar/00d60277d09f7a61d967d693abbecc72?s=1024&r=x",
                    'height' => 1024,
                    'width' => 1024
                )
            );
        }

        $this->avatar = $thumbnail;
    }

    function set_value($key, $wp_key = false) {
        if (!$wp_key) {
            $wp_key = $key;
        }

        if ($key == "email")
            $this->$key = md5(get_the_author_meta($wp_key, $this->id));
        else
            $this->$key = get_the_author_meta($wp_key, $this->id);
    }

    function set_author_meta() {
        global $joapp_api;
        if (!$joapp_api->query->author_meta) {
            return;
        }
        $protected_vars = array(
            'user_login',
            'user_pass',
            /* 'user_email', */
            'user_activation_key'
        );
        $vars = explode(',', $joapp_api->query->author_meta);
        $vars = array_diff($vars, $protected_vars);
        foreach ($vars as $var) {
            $this->set_value($var);
        }
    }

}

?>
