<?php

class JOAPP_API_Introspector {

    public function get_posts($query = false, $wp_posts = false) {
        global $post, $wp_query;
        $this->set_posts_query($query);

        $output = array();
        while (have_posts()) {
            the_post();

            if ($wp_posts) {
                $new_post = $post;
            } else {

                $new_post = new JOAPP_API_Post($post);
            }

            $output[] = $new_post;
        }
        return $output;
    }

    public function get_date_archive_permalinks() {
        $archives = wp_get_archives('echo=0');
        preg_match_all("/href='([^']+)'/", $archives, $matches);
        return $matches[1];
    }

    public function get_date_archive_tree($permalinks) {
        $tree = array();
        foreach ($permalinks as $url) {
            if (preg_match('#(\d{4})/(\d{2})#', $url, $date)) {
                $year = $date[1];
                $month = $date[2];
            } else if (preg_match('/(\d{4})(\d{2})/', $url, $date)) {
                $year = $date[1];
                $month = $date[2];
            } else {
                continue;
            }
            $count = $this->get_date_archive_count($year, $month);
            if (empty($tree[$year])) {
                $tree[$year] = array(
                    $month => $count
                );
            } else {
                $tree[$year][$month] = $count;
            }
        }
        return $tree;
    }

    public function get_date_archive_count($year, $month) {
        if (!isset($this->month_archives)) {
            global $wpdb;
            $post_counts = $wpdb->get_results("
        SELECT DATE_FORMAT(post_date, '%Y%m') AS month,
               COUNT(ID) AS post_count
        FROM $wpdb->posts
        WHERE post_status = 'publish'
          AND post_type = 'post'
        GROUP BY month
      ");
            $this->month_archives = array();
            foreach ($post_counts as $post_count) {
                $this->month_archives[$post_count->month] = $post_count->post_count;
            }
        }
        return $this->month_archives["$year$month"];
    }

    public function get_categories($args = null) {
        $categories = array();

        $json_hidden_list = get_option('joapp_api_hidden_category', "[]");
        $hidden_list = json_decode($json_hidden_list);

        $json_hidden_list_menu = get_option('joapp_api_hidden_category_menu', "[]");
        $hidden_list_menu = json_decode($json_hidden_list_menu);

        foreach ($hidden_list as $id) {
            $wp_category = get_term_by('term_id', $id, 'category');

            if ($wp_category == null) {
                continue;
            }
            if ($wp_category->term_id == 1 && $wp_category->slug == 'uncategorized') {
                continue;
            }
            $new_cat = $this->get_category_object($wp_category);

            if (in_array($id, $hidden_list_menu)) {
                $new_cat->is_menu = true;
            }

            $categories[] = $new_cat;
        }
        return $categories;
    }

    public function get_woo_categories($showAll = false) {
        global $woocommerce;
        $categories = array();
        $categories_res = array();

        $json_hidden_list = get_option('joapp_wooapi_hidden_category', "[]");
        $json_hidden_list_menu = get_option('joapp_wooapi_hidden_category_menu', "[]");

        $hidden_list = json_decode($json_hidden_list);
        $hidden_list_menu = json_decode($json_hidden_list_menu);

        foreach ($hidden_list as $id) {
            $wp_category = get_term_by('term_id', $id, 'product_cat');

            if ($wp_category == null) {
                continue;
            }
            if ($wp_category->term_id == 1 && $wp_category->slug == 'uncategorized') {
                continue;
            }

            if (function_exists("get_woocommerce_term_meta")) {
                $thumbnail_id = get_woocommerce_term_meta($wp_category->term_id, 'thumbnail_id', true);
                $wp_category->image = (String) wp_get_attachment_url($thumbnail_id);
            }
            $cat = $this->get_category_object($wp_category);
            if ($cat == null)
                continue;
            $count_posts = get_option("joapp_woo_count_category", 10);
            $args = array(
                'posts_per_page' => 100,
                'limit' => 100,
                'tax_query' => array(
                    'relation' => 'AND',
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $cat->slug,
                    )
                ),
                'post_type' => 'product',
                'orderby' => 'date',
                'order' => 'DESC'
            );

            $postslist = get_posts($args);
            $count_exist = 0;
            $cat->products = array();
            foreach ($postslist as $p) {
                if ($count_exist >= $count_posts)
                    break;
                $p_ok = new JOAPP_API_Product($p->ID,TRUE);

                if ($p_ok->id !== "-999" && $p_ok->in_stock) {
                    $cat->products[] = $p_ok;
                    $count_exist++;
                }
            }

            if (count($cat->products) == 0)
                continue;

            $cat->timedown = $this->get_timedown($cat->id);
            $cat->is_menu = in_array($cat->id, $hidden_list_menu);
            $categories_res[] = $cat;
        }
        return $categories_res;
    }

    private function get_timedown($cat_id) {
        $timestamp = get_option('joapp_api_timer_' . $cat_id, 0);
        $time_to = new DateTime(date('Y-m-d H:i:s', $timestamp));
        $time = new DateTime(date('Y-m-d H:i:s'));

        $mod = $time_to->getTimestamp() - $time->getTimestamp();
        return $mod >= 0 ? $mod : 0;
    }

    public function get_current_post() {
        global $joapp_api;
        extract($joapp_api->query->get(array('id', 'slug', 'post_id', 'post_slug')));
        if ($id || $post_id) {
            if (!$id) {
                $id = $post_id;
            }
            $posts = $this->get_posts(array(
                'p' => $id
                    ), true);
        } else if ($slug || $post_slug) {
            if (!$slug) {
                $slug = $post_slug;
            }
            $posts = $this->get_posts(array(
                'name' => $slug
                    ), true);
        } else {
            $joapp_api->error("Include 'id' or 'slug' var in your request.");
        }
        if (!empty($posts)) {
            return $posts[0];
        } else {
            return null;
        }
    }

    public function get_current_category() {
        global $joapp_api;
        extract($joapp_api->query->get(array('id', 'slug', 'category_id', 'category_slug')));
        if ($id || $category_id) {
            if (!$id) {
                $id = $category_id;
            }
            return $this->get_category_by_id($id);
        } else if ($slug || $category_slug) {
            if (!$slug) {
                $slug = $category_slug;
            }
            return $this->get_category_by_slug($slug);
        } else {
            $joapp_api->error("Include 'id' or 'slug' var in your request.");
        }
        return null;
    }

    public function get_category_by_id($category_id) {
        $wp_category = get_term_by('id', $category_id, 'category');
        return $this->get_category_object($wp_category);
    }

    public function get_category_by_slug($category_slug) {
        $wp_category = get_term_by('slug', $category_slug, 'category');
        return $this->get_category_object($wp_category);
    }

    public function get_tags() {
        $wp_tags = get_tags();
        return array_map(array(&$this, 'get_tag_object'), $wp_tags);
    }

    public function get_current_tag() {
        global $joapp_api;
        extract($joapp_api->query->get(array('id', 'slug', 'tag_id', 'tag_slug')));
        if ($id || $tag_id) {
            if (!$id) {
                $id = $tag_id;
            }
            return $this->get_tag_by_id($id);
        } else if ($slug || $tag_slug) {
            if (!$slug) {
                $slug = $tag_slug;
            }
            return $this->get_tag_by_slug($slug);
        } else {
            $joapp_api->error("Include 'id' or 'slug' var in your request.");
        }
        return null;
    }

    public function get_tag_by_id($tag_id) {
        $wp_tag = get_term_by('id', $tag_id, 'post_tag');
        return $this->get_tag_object($wp_tag);
    }

    public function get_tag_by_slug($tag_slug) {
        $wp_tag = get_term_by('slug', $tag_slug, 'post_tag');
        return $this->get_tag_object($wp_tag);
    }

    public function get_authors() {
        global $wpdb;
        $author_ids = $wpdb->get_col("
      SELECT u.ID, m.meta_value AS last_name
      FROM $wpdb->users AS u,
           $wpdb->usermeta AS m
      WHERE m.user_id = u.ID
        AND m.meta_key = 'last_name'
      ORDER BY last_name
    ");

        $all_authors = array_map(array(&$this, 'get_author_by_id'), $author_ids);
        $active_authors = array_filter($all_authors, array(&$this, 'is_active_author'));
        return $active_authors;
    }

    public function get_current_author() {
        global $joapp_api;
        extract($joapp_api->query->get(array('id', 'slug', 'author_id', 'author_slug')));
        if ($id || $author_id) {
            if (!$id) {
                $id = $author_id;
            }
            return $this->get_author_by_id($id);
        } else if ($slug || $author_slug) {
            if (!$slug) {
                $slug = $author_slug;
            }
            return $this->get_author_by_login($slug);
        } else {
            $joapp_api->error("Include 'id' or 'slug' var in your request.");
        }
        return null;
    }

    public function get_author_by_id($id) {
        $id = get_the_author_meta('ID', $id);
        if (!$id) {
            return null;
        }
        return new JOAPP_API_Author($id);
    }

    public function get_author_by_login($login) {
        global $wpdb;
        $id = $wpdb->get_var($wpdb->prepare("
      SELECT ID
      FROM $wpdb->users
      WHERE user_nicename = %s
    ", $login));
        return $this->get_author_by_id($id);
    }

    public function get_comments($post_id) {
        global $wpdb;

        $wp_comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments  WHERE comment_post_ID = %d AND comment_approved = 1 AND comment_type = '' ORDER BY comment_date", $post_id));
        $comments = array();
        foreach ($wp_comments as $wp_comment) {
            $comments[] = new JOAPP_API_Comment($wp_comment);
        }
        return $comments;
    }

    public function get_attachments($post_id) {
        $wp_attachments = get_children(array(
            'post_type' => 'attachment',
            'post_parent' => $post_id,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'suppress_filters' => false
        ));
        $attachments = array();
        if (!empty($wp_attachments)) {
            foreach ($wp_attachments as $wp_attachment) {
                $attachments[] = new JOAPP_API_Attachment($wp_attachment);
            }
        }
        return $attachments;
    }

    public function get_attachment($attachment_id) {
        global $wpdb;
        $wp_attachment = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $attachment_id)
        );
        return new JOAPP_API_Attachment($wp_attachment);
    }

    public function attach_child_posts(&$post) {
        $post->children = array();
        $wp_children = get_posts(array(
            'post_type' => $post->type,
            'post_parent' => $post->id,
            'order' => 'ASC',
            'orderby' => 'menu_order',
            'numberposts' => -1,
            'suppress_filters' => false
        ));
        foreach ($wp_children as $wp_post) {
            $new_post = new JOAPP_API_Post($wp_post);
            $new_post->parent = $post->id;
            $post->children[] = $new_post;
        }
        foreach ($post->children as $child) {
            $this->attach_child_posts($child);
        }
    }

    protected function get_category_object($wp_category) {
        if (!$wp_category) {
            return null;
        }
        return new JOAPP_API_Category($wp_category);
    }

    protected function get_tag_object($wp_tag) {
        if (!$wp_tag) {
            return null;
        }
        return new JOAPP_API_Tag($wp_tag);
    }

    protected function is_active_author($author) {
        if (!isset($this->active_authors)) {
            $this->active_authors = explode(',', wp_list_authors(array(
                'html' => false,
                'echo' => false,
                'exclude_admin' => false
            )));
            $this->active_authors = array_map('trim', $this->active_authors);
        }
        return in_array($author->name, $this->active_authors);
    }

    protected function set_posts_query($query = false) {
        global $joapp_api, $wp_query;

        if (!$query) {
            $query = array();
        }

        $query = array_merge($query, $wp_query->query);


        if ($joapp_api->query->page) {
            $query['paged'] = $joapp_api->query->page;
        }

        if ($joapp_api->query->count) {
            $query['posts_per_page'] = $joapp_api->query->count;
        }

        if ($joapp_api->query->post_type) {
            $query['post_type'] = $joapp_api->query->post_type;
        }

        $query = apply_filters('joapp_api_query_args', $query);

        if (isset($query['post_type']) && $query['post_type'] == 'page') {
            $query['meta_query'] = array(
                array(
                    'key' => 'post_status_page_joapp',
                    'value' => 'set_true',
                    'type' => 'string',
                    'compare' => '='
                )
            );
        } else if ($query['joapp'] != 'get_post') {
            $joapp_allow_post_types = get_option('joapp_allow_post_types', '["post"]');
            $arr_post_type = json_decode($joapp_allow_post_types);
            array_push($arr_post_type, "joapp_intent");
            $query['post_type'] = $arr_post_type;
        }

        if (!empty($query)) {
            query_posts($query);
            do_action('joapp_api_query', $wp_query);
        }
    }

}

?>
