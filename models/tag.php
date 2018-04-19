<?php

class JOAPP_API_Tag {
  
  var $id;          // Integer
  var $slug;        // String
  var $title;       // String
  var $description; // String
  
  function JOAPP_API_Tag($wp_tag = null) {
    if ($wp_tag) {
      $this->import_wp_object($wp_tag);
    }
    do_action("joapp_api_action_tag_init", $this);
  }
  
  function import_wp_object($wp_tag) {
    $this->id = (int) $wp_tag->term_id;
    $this->slug = $wp_tag->slug;
    $this->title = $wp_tag->name;
    $this->description = $wp_tag->description;
    $this->post_count = (int) $wp_tag->count;
  }
  
}

?>
