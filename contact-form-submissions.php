<?php
/*
Plugin Name: Contact Form Submissions
Description: Never miss an enquiry again! Save all Contact Form 7 submissions in your database.
Version:     1.6.1
Author:      Jason Green
License:     GPLv3
Domain Path: /languages
Text Domain: contact-form-submissions
*/

define('WPCF7S_DIR', realpath(dirname(__FILE__)));
define('WPCF7S_FILE', 'contact-form-submissions/contact-form-submissions.php');

require_once WPCF7S_DIR . '/Submissions.php';
require_once WPCF7S_DIR . '/Admin.php';

/**
 * Save the WPCF7Submissions class for later
 */
function contact_form_submissions_init()
{
    global $contact_form_submissions;
    $contact_form_submissions = new WPCF7Submissions();
}
add_action('init', 'contact_form_submissions_init', 9);

/**
 * Save the WPCF7SAdmin class for later
 */
function contact_form_submissions_admin_init()
{
    global $contact_form_submissions_admin;
    $contact_form_submissions_admin = new WPCF7SAdmin();
}
add_action('admin_init', 'contact_form_submissions_admin_init');

/**
 * Load language file
 */
function contact_form_submissions_textdomain()
{
    load_plugin_textdomain('contact-form-submissions', false, basename( dirname( __FILE__ ) ) . '/languages/');

}
add_action('plugins_loaded', 'contact_form_submissions_textdomain');


/**
 * Adding Dashboard Widget containing latest messages
 */
add_action('wp_dashboard_setup', 'contact_form_submissions_dashboard_widgets');
function contact_form_submissions_dashboard_widgets() {
	global $wp_meta_boxes;
	wp_add_dashboard_widget('contact_form_submissions_dashboard_widget', __("CF7 submissions"), 'contact_form_submissions_widget_callback');
}
function contact_form_submissions_widget_callback() {
	$count = wp_count_posts( 'wpcf7s' )->publish;
	$recents = wp_get_recent_posts(array('post_type'=>'wpcf7s'));
    foreach($recents as $recent ){
        $list .= '<li>
		<a href="/wp-admin/post.php?action=edit&post=' . $recent["ID"]. '">' . date(get_option( 'date_format' ), strtotime($recent["post_modified"])). '</a>
		' . substr(strip_tags($recent["post_content"]), 0 , 125). '</li>';
    }
	echo "<p><a href='/wp-admin/edit.php?post_type=wpcf7s'>
        <button class='button button-primary'>" . $count . " " . __("messages") . "</button></a></p> 
        <p><ul>" . $list . "</ul></p>";
}
