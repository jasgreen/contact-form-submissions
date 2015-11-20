<?php
/*
Plugin Name: Contact Form Submissions
Description: Save all Contact Form 7 submissions in the database.
Version:     1.1
Author:      Jason Green
License:     GPLv3
*/

define('WPCF7S_TEXT_DOMAIN', 'wpcf7-submissions');
define('WPCF7S_DIR', realpath(dirname(__FILE__)));
define('WPCF7S_FILE', 'contact-form-7-submissions/contact-form-7-submissions.php');

require_once WPCF7S_DIR . '/Submissions.php';
require_once WPCF7S_DIR . '/Admin.php';

function contact_form_7_submissions_init() {
  global $contact_form_7_submissions;
  $contact_form_7_submissions = new WPCF7Submissions();
}
add_action( 'init', 'contact_form_7_submissions_init', 9 );

function contact_form_7_submissions_admin_init() {
  global $contact_form_7_submissions_admin;
  $contact_form_7_submissions_admin = new WPCF7SAdmin();
}
add_action( 'admin_init', 'contact_form_7_submissions_admin_init' );
