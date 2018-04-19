<?php

/*
  Controller name: Posts
  Controller description: Data manipulation methods for posts
 */

class JOAPP_API_Posts_Controller {

    public function create_post() {
        global $joapp_api;
        $joapp_api->error("Cannot Create Post.", 500);
    }

    public function update_post() {
        global $joapp_api;
        $joapp_api->error("Cannot Update Post", 403);
    }

    public function delete_post() {
        global $joapp_api;
        $joapp_api->error("Cannot Delete Post.", 403);
    }

}

?>
