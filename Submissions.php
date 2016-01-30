<?php
class WPCF7Submissions {
    
    function __construct() {
        add_action('init', array($this, 'post_type') );
        
        add_filter('wpcf7_mail_components', array($this, 'submission'), 999, 3);
    }

    function post_type() {
        $labels = array(
            'name'                => __( 'Contact Form Submissions', 'contact-form-submissions' ),
            'singular_name'       => __( 'Submission', 'contact-form-submissions' ),
            'menu_name'           => __( 'Submission', 'contact-form-submissions' ),
            'all_items'           => __( 'Submissions', 'contact-form-submissions' ),
            'view_item'           => __( 'Submission', 'contact-form-submissions' ),
            'edit_item'           => __( 'Submission', 'contact-form-submissions' ),
            'search_items'        => __( 'Search', 'contact-form-submissions' ),
            'not_found'           => __( 'Not found', 'contact-form-submissions' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'contact-form-submissions' ),
        );
        $args = array(
            'label'               => __( 'Submission', 'contact-form-submissions' ),
            'description'         => __( 'Post Type Description', 'contact-form-submissions' ),
            'labels'              => $labels,
            'supports'            => false,
            'hierarchical'        => true,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'wpcf7',
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,     
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'rewrite'             => false,
            'capability_type'     => 'page',
            'capabilities' => array(
                'create_posts'  => false
            ),
            'map_meta_cap' => true
        );
        register_post_type( 'wpcf7s', $args );
    }

    function submission($components, $contact_form, $mail){
        global $wpcf7s_post_id;

        $contact_form_id = 0;
        if(method_exists($contact_form,'id')){
            $contact_form_id = $contact_form->id();
        } elseif(property_exists($contact_form , 'id' )) {
            $contact_form_id = $contact_form->id;
        }

        $body = $components['body'];
        $sender = wpcf7_strip_newline( $components['sender'] );
        $recipient = wpcf7_strip_newline( $components['recipient'] );
        $subject = wpcf7_strip_newline( $components['subject'] );
        $headers = trim($components['additional_headers']);
        $attachments = $components['attachments'];

        $submission = array(
            'form_id'   => $contact_form_id,
            'body'      => $body,
            'sender'    => $sender,
            'subject'   => $subject,
            'recipient' => $recipient,
            'additional_headers' => $headers,
            'attachments' => $attachments
        );

        if(!empty($wpcf7s_post_id)){
            $submission['parent'] = $wpcf7s_post_id;
        }

        $post_id = $this->save($submission);

        if(empty($wpcf7s_post_id)){
            $wpcf7s_post_id = $post_id;
        }

        return $components;
    }

    private function save($submission = array()){
        $post = array(
            'post_title'    => ' ',
            'post_content'  => $submission['body'],
            'post_status'   => 'publish',
            'post_type'     => 'wpcf7s',
        );

        if(isset($submission['parent'])){
            $post['post_parent'] = $submission['parent'];
        }
        
        $post_id = wp_insert_post($post);
        
        add_post_meta($post_id, 'form_id', $submission['form_id']);
        add_post_meta($post_id, 'subject', $submission['subject']);
        add_post_meta($post_id, 'sender', $submission['sender']);
        add_post_meta($post_id, 'recipient', $submission['recipient']);
        add_post_meta($post_id, 'additional_headers', $submission['additional_headers']);

        return $post_id;
    }
}