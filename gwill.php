<?php
/**
* Plugin Name: Gwill Post Field
* Description: Simple Gwill
*/
 
 
$post_type_name;
// Render the form to create a new custom post type
function render_custom_post_type_form() {
    ?>
    <div class="wrap">
        <h1>Create School Year</h1>
        <form method="post" action="">
            <?php wp_nonce_field('create_custom_post_type', 'custom_post_type_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">School Year</th>
                    <td><input type="text" name="post_type_name" required /></td>
                </tr>
              
            </table>
            <p class="submit">
                <input type="submit" name="submit_post_type" class="button-primary" value="Create Post Type" />
            </p>
        </form>
    </div>
    <?php
}

// Handle form submission and store custom post type in post table
function handle_custom_post_type_form() {
    if (isset($_POST['submit_post_type']) && check_admin_referer('create_custom_post_type', 'custom_post_type_nonce')) {
   

     
        $post_type_name = sanitize_text_field($_POST['post_type_name']);  
  
 
        if (empty($post_type_name)  ) {
            echo '<div class="notice notice-error is-dismissible"><p>All fields are required.</p></div>';
            return;
        }

        // Check if post type already exists in the database
        $existing_posts = get_posts(array(
            'post_type' => 'custom_post_type',
            'meta_key'  => 'post_type_name',
            'meta_value'=> $post_type_name,
        ));
        
        if (!empty($existing_posts)) {
            echo '<div class="notice notice-error is-dismissible"><p>Post type already exists.</p></div>';
            return;
        }
 // Data to be saved in post_content
 $post_content = serialize(array(
    'post_type' => 'gwill-post-field',
    'settings'  => array(
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'my_custom_post'),
        'supports_editor'   => true,
    )
));

        // Insert post representing the custom post type
        $post_id = wp_insert_post(array(
            'post_title'  => $post_type_name,
            'post_type'   => 'gwill-post-field', // Custom post type to hold post type definitions
            'post_content' => $post_content,
            'post_status' => 'publish',
            'meta_input'  => array(
                'post_type_name'         => $post_type_name,
                'post_type_label'        => $post_type_name,
                'post_type_singular_label'=>  $post_type_name,
            ),
        ));
       
        if ($post_id) {
            echo '<div class="notice notice-success is-dismissible"><p>Custom post type created successfully! Please reload the page to view the new added School Year.</p></div>';
        }
      
         duplicate_page_and_change_title("606", $post_type_name);

        
    }
}

// Register the custom post types
function register_custom_post_types() {
    // Get all posts representing custom post types
    $custom_posts = get_posts(array(
        'post_type'   => 'gwill-post-field',
        'numberposts' => -1,
    ));
 
    foreach ($custom_posts as $post) {
        $post_type_name = get_post_meta($post->ID, 'post_type_name', true);
    
        if ($post_type_name  ) {
            $args = array(
                'labels' => array(
                    'name'          => $post_type_name,
                    'singular_name' => $post_type_name,
                    'add_new'       => 'Add New Class', // Label for "Add New"
                    'add_new_item'  => 'Add New Class', // Label for the button
                    'edit_item'     => 'Edit Class', // Label for the edit screen
                    'new_item'      => 'New Class',  // Label for new items
                    'view_item'     => 'View Class', // Label for viewing items
                    'all_items'     => 'All Classes', // Label for all items in the menu
                ),
                'public'       => true,
                'has_archive'  => true,
                'rewrite'      => array('slug' => $post_type_name),
                'supports'     => array('title',),
                'show_in_rest' => true, // This enables REST API support
            );
            register_post_type($post_type_name, $args);
            flush_rewrite_rules();
        }
    }
}

 

// Hook the functions to the appropriate WordPress actions
add_action('admin_menu', function () {
    add_menu_page('School Year', 'School Year', 'manage_options', 'custom-post-types', 'render_custom_post_type_form');
});

add_action('admin_init', 'handle_custom_post_type_form');
add_action('init', 'register_custom_post_types');

 
// Hook into the save_post action to modify the post title based on the custom field "Grade Level"

function set_grade_level_as_post_title($post_id) {
    // Check if this is an autosave or a revision, we don't want to process these
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);
  // Check if the post type is not a default WordPress post type ('post', 'page', etc.)
    // Assuming custom post types will not be 'post', 'page', 'attachment', etc.
 if (in_array($post_type, array('post', 'page', 'attachment', 'revision','nav_menu_item'))){

    return;
 }
  // Check if "Grade Level" custom field is set
  $grade_level = get_post_meta($post_id, 'grade_level', true);
  $section = get_post_meta($post_id, 'section', true);
  if (!empty($grade_level)) {
      // Prepare the updated post data
      $post_data = array(
          'ID'         => $post_id,
          'post_title' => $grade_level.'-'. $section, // Set the title to the value of "Grade Level"
      );

      // Update the post, and use wp_update_post to avoid infinite loop
      remove_action('save_post', 'set_grade_level_as_post_title'); // Temporarily remove to prevent looping
      wp_update_post($post_data);
      add_action('save_post', 'set_grade_level_as_post_title'); // Add it back
  }
 
  
}

// Attach the function to the 'save_post' action
add_action('save_post', 'set_grade_level_as_post_title');


//crate a page
function duplicate_page_and_change_title($page_id, $new_title) {
    // Get the original page by ID
    $original_page = get_post($page_id);

    // Check if the original page exists
    if (is_null($original_page)) {
        return new WP_Error('page_not_found', 'Page not found');
    }

    // Create an array of arguments for the new page
    $new_page_args = array(
        'post_title'    => $new_title, // New title
        'post_content'  => $original_page->post_content, // Copy content
        'post_status'   => 'publish', // Publish the new page immediately
        'post_type'     => 'page', // Ensure it's a page post type
        'post_author'   => $original_page->post_author, // Same author
        'post_parent'   => $original_page->post_parent, // Same parent if it's a sub-page
        'menu_order'    => $original_page->menu_order, // Same order in menus
        'post_excerpt'  => $original_page->post_excerpt, // Copy excerpt
        'comment_status' => $original_page->comment_status, // Same comment status
        'ping_status'   => $original_page->ping_status, // Same ping status
        'post_name'     => sanitize_key($new_title),// Modify slug
        'meta_input'    => array(
            'page_type' => 'school year', // Add custom value here
        )
    );

    // Insert the new page
    $new_page_id = wp_insert_post($new_page_args);

    if (is_wp_error($new_page_id)) {
        return new WP_Error('duplication_failed', 'Failed to duplicate the page.');
    }

    // Duplicate all post meta data
    $meta_data = get_post_meta($page_id);
    foreach ($meta_data as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($new_page_id, $key, maybe_unserialize($value));
        }
    }
 
  // (Optional) If the page was using Elementor's Global Settings or Theme Styles, duplicate those too
  $elementor_data = get_post_meta($page_id, '_elementor_data', true);
  if (!empty($elementor_data)) {
      update_post_meta($new_page_id, '_elementor_data', maybe_unserialize($elementor_data));
  }

  flush_elementor_cache();
 
    return $new_page_id; // Return the new page ID
}
function flush_elementor_cache() {
    if (did_action('elementor/loaded')) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
    }
}
