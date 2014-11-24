<?php

/**
 * Set PHPMailer to use SendGrid for emails
 */

namespace EP\admin\sendgrid;

add_action( 'phpmailer_init', __NAMESPACE__ . '\action_phpmailer_init' );

function action_phpmailer_init( \PHPMailer $phpmailer ) {

    $phpmailer->Host = 'smtp.sendgrid.com';
    $phpmailer->Port = 2525;
    $phpmailer->Username = 'peder@earthpeople.se';
    $phpmailer->Password = 'peppe1';
    $phpmailer->SMTPAuth = true; // if required
    $phpmailer->IsSMTP();

}
