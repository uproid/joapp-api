<?php

class JOAPP_API_Product {

    var $id;                            //int
    var $title;                         //String
    var $type;                          //String
    var $permalink;                     //String
    var $featured = false;              //boolean
    var $description;                   //String
    var $short_description;             //String
    var $price;                         //String
    var $regular_price;                 //String
    var $in_stock;                      //boolean
    var $weight = "0 k/g";              //String
    var $dimensions;                    //Dimension
    var $images;                        //List<Image>
    var $attributes;                    //List<Attribute>
    var $average_rating = "0.0";        //String
    var $rating_count;                  //int
    var $product_meta;                  // HashMap<String, Object>
    var $managing_stock = false;        //boolean
    var $stock_quantity = 0;            //int
    private $is_small = false;

    function JOAPP_API_Product($wp_prodct_id, $is_small = false) {
        $this->id = (int) $wp_prodct_id;
        $this->is_small = $is_small;
        $this->init_JOAPP_API_Product();
        do_action("joapp_api_action_product_init", $this);
    }

    function init_JOAPP_API_Product() {

        $product = wc_get_product($this->id);

        if (!class_exists('WooCommerce') || $product == null || !is_a($product, 'WC_Product')) {
            $this->id = "-999";
            return;
        }


        $this->title = $product->get_title();
        $this->id = $product->get_id();
        $this->price = $product->get_price();

        $this->regular_price = $product->get_regular_price();

        $this->in_stock = $product->is_in_stock();

        $this->featured = $product->is_featured();

        $this->images = $this->get_images($product);

        $this->short_description = $product->post->post_excerpt ? $product->post->post_excerpt : "";
        
        if ($this->is_small) {
            unset($this->type);
            unset($this->permalink);
            unset($this->managing_stock);
            unset($this->stock_quantity);
            unset($this->dimensions);
            unset($this->weight);
            unset($this->description);
            unset($this->average_rating);
            unset($this->rating_count);
            unset($this->attributes);
            unset($this->product_meta);
            return;
        }

        $this->type = isset($product->product_type) && !is_null($product->product_type) ? $product->product_type : 'simple';
        
        $this->permalink = $product->get_permalink();
        
        $this->managing_stock = $product->managing_stock();
        
        $this->stock_quantity = ($this->managing_stock) ? $product->get_stock_quantity() : 1000;
        $this->weight = $product->get_weight() ? $product->get_weight() : null;
        $this->dimensions = array(
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height(),
            'unit' => get_option('woocommerce_dimension_unit'),
        );
         
        $this->description = get_post($this->id)->post_content;
      
        if ($this->description === "") {
            $this->description = $this->short_description;
            $this->short_description = "";
        }
        
        $this->average_rating = wc_format_decimal($product->get_average_rating(), 2);
        $this->rating_count = $product->get_rating_count();
        $this->attributes = $this->get_attributes($product);
        $meta = (array) get_post_meta($this->id);
        $meta_data = array();

        if ($meta) {
            foreach ($meta as $meta_key => $meta_value) {
                if (!is_protected_meta($meta_key)) {
                    $meta_data['product_meta'][$meta_key] = maybe_unserialize($meta_value[0]);
                }
            }
        }
        $this->product_meta = $meta_data;
    }

    private function get_images($product) {

        if ($this->is_small) {
            $images = array();

            $thumb = $product->get_image_id();
            if ($thumb) {
                $img = wp_get_attachment_image_src($thumb, 'post-thumbnail');
                if (isset($img[0])) {
                    $images[] = array(
                        'src' => $img[0],
                        'name' => "thumbnile"
                    );
                }
            }
            return $images;
        }

        $images = $attachment_ids = array();
        $product_image = $product->get_image_id();
        // Add featured image.
        if (!empty($product_image)) {
            $attachment_ids[] = $product_image;
        }


        // Add gallery images.
        $attachment_ids = array_merge($attachment_ids, $product->get_gallery_attachment_ids());

        // Build image data.
        foreach ($attachment_ids as $position => $attachment_id) {

            $attachment_post = get_post($attachment_id);

            if (is_null($attachment_post)) {
                continue;
            }

            $attachment = wp_get_attachment_image_src($attachment_id, 'full');

            if (!is_array($attachment)) {
                continue;
            }

            $images[] = array(
                'src' => current($attachment),
                'name' => '',
            );
        }

        // Set a placeholder image if the product has no images set.
        if (empty($images)) {

            $images[] = array(
                'src' => wc_placeholder_img_src(),
                'name' => '',
            );
        }

        return $images;
    }

    private function get_attributes($product) {

        $attributes = array();

        if ($product->is_type('variation')) {

            // variation attributes
            foreach ($product->get_variation_attributes() as $attribute_name => $attribute) {

                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'name' => wc_attribute_label(str_replace('attribute_', '', $attribute_name), $product),
                    'slug' => str_replace('attribute_', '', str_replace('pa_', '', $attribute_name)),
                    'option' => $attribute,
                );
            }
        } else {

            foreach ($product->get_attributes() as $attribute) {
                $attributes[] = array(
                    'name' => wc_attribute_label($attribute['name'], $product),
                    'slug' => str_replace('pa_', '', $attribute['name']),
                    'position' => (int) $attribute['position'],
                    'visible' => (bool) $attribute['is_visible'],
                    'variation' => (bool) $attribute['is_variation'],
                    'options' => $this->get_attribute_options($product->get_id(), $attribute),
                );
            }
        }

        return $attributes;
    }

    protected function get_attribute_options($product_id, $attribute) {
        if (isset($attribute['is_taxonomy']) && $attribute['is_taxonomy']) {
            return wc_get_product_terms($product_id, $attribute['name'], array('fields' => 'names'));
        } elseif (isset($attribute['value'])) {
            return array_map('trim', explode('|', $attribute['value']));
        }

        return array();
    }

}

?>
