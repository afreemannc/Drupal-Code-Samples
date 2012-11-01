<?php

require_once('mailtron.module');

/**
 * @file Mailtron helper functions for 3rd party module development.
 */

/**
 * Drop this into your code to add/edit/delete mailtron mail settings.
 * 
 * Be sure to provide module and name (unique per module, much like block deltas) to ensure mailtron can load your module-specific settings.
 *
 * @param $edit
 * Placeholder for form API edit cruft.
 *
 * @param $params
 * Associative array of additional parameters. Supported values:
 * - module (required): your module name
 * - name (required): the name of your email configuration (unique per module, similar to block deltas)
 * - send_callback (optional): callback function to invoke after a mail is sent.
 * - recipient_callback (optional): callback function to invoke to get mail recipient address. This is required if you aren't hardcoding the recipient in
 *   the mail configuration.
 */
function mailtron_mail_form($edit, $params = array()) {
  $settings = mailtron_mail_load($params);
  $form['mid'] = array(
    '#type' => 'value',
    '#value' => isset($settings->mid) ? $settings->mid : '',
  );
  $form['module'] = array(
    '#type' => 'value',
    '#value' => isset($settings->module) ? $settings->module : $params['module'],
  );
  $form['name'] = array(
    '#type' => 'value',
    '#value' => isset($settings->name) ? $settings->name : $params['name'],
  ); 
  // currently unused but potentially useful if we integrate with Email Confirmations confirmation template nodes.
  $form['tid'] = array(
    '#type' => 'value',
    '#value' => isset($settings->tid) ? $settings->tid : 0,
  );
  $form['recipient_callback'] = array(
    '#type' => 'value',
    '#value' => isset($params['recipient_callback']) ? $params['recipient_callback'] : '',
  );
  $form['send_callback'] = array(
    '#type' => 'value',
    '#value' => isset($params['send_callback']) ? $params['send_callback'] : '',
  );
  $form['headers'] = array(
    '#type' => 'fieldset',
    '#title' => t('mail headers'),
    '#description' => t(''),
    '#collapsible' => TRUE,
  );
  $form['headers']['from_name'] = array(
    '#type' => 'textfield',
    '#title' => t('from name'),
    '#description' => t(''),
    '#default_value' => isset($settings->from_name) ? $settings->from_name : '',
    '#required' => TRUE,
  );
   $form['headers']['from_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('From mail'),
    '#description' => t(''),
    '#default_value' => isset($settings->from_mail) ? $settings->from_mail : '',
    '#required' => TRUE,
  );
   $form['headers']['recipients'] = array(
    '#type' => 'textfield',
    '#title' => t('Recipient email address'),
    '#description' => t('The primary recipient email address(es). For more than one address, addresses should be separated by a comma. Example: test1@example.com,test2@example.com'),
    '#default_value' => isset($settings->recipients) ? $settings->recipients : '',
    '#required' => TRUE,
  );
  $form['headers']['cc'] = array(
    '#type' => 'textfield',
    '#title' => t('CC email address'),
    '#description' => t('Email addresses to CC when sending this mail. For more than one address, addresses should be separated by a comma. Example: test3@example.com,test4@example.com'),
    '#default_value' => isset($settings->cc) ? $settings->cc : '',
  );
  $form['headers']['bcc'] = array(
    '#type' => 'textfield',
    '#title' => t('BCC email address'),
    '#description' => t('Email addresses to BCC when sending this mail. For more than one address, addresses should be separated by a comma. Example: test5@example.com,test6@example.com'),
    '#default_value' => isset($settings->bcc) ? $settings->bcc : '',
  );


  $form['message'] = array(
    '#type' => 'fieldset',
    '#title' => t('mail body'),
    '#description' => t(''),
    '#collapsible' => TRUE,
  );
  $form['message']['subject'] = array(
    '#type' => 'textfield',
    '#title' => t('Subject'),
    '#description' => t(''),
    '#default_value' => isset($settings->subject) ? $settings->subject : '',
    '#required' => TRUE,
  );
  $form['message']['html_body'] = array(
    '#type' => 'textarea',
    '#title' => t('HTML message version'),
    '#description' => t('If you wish to send HTML or multipart HTML/text email enter the HTML version of the message body here.'),
    '#default_value' => isset($settings->html_body) ? $settings->html_body : '',
  );
  // TODO: replace with input format UI
  $form['message']['html_body_format'] = array(
    '#type' => '',
    '#title' => t(''),
    '#description' => t(''),
    '#default_value' => isset($settings->html_body_format) ? $settings->html_body_format : '',
  );
  $form['message']['text_body'] = array(
    '#type' => 'textarea',
    '#title' => t('Text message version'),
    '#description' => t('If you wish to send plaintext or multipart HTML/text email enter the text-only version of the message body here.'),
    '#default_value' => isset($settings->text_body) ? $settings->text_body : '',
  );
  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );
  $form['#validate'][] = 'mailtron_mail_form_validate';
  $form['#submit'][] = 'mailtron_mail_form_submit';
  return $form;
}

function mailtron_mail_form_validate(&$form, $form_state) {
  if (!mailtron_validate_mail_input($form_state['values']['from_mail'])) {
    form_set_error('from_mail', t('You have entered an invalid email address in the "from mail" field: %mail', array('%mail' => $form_state['values']['from_mail'])));
  }
  
  if ($form_state['values']['recipients']) {
    $recipients = explode(',', $form_state['values']['recipients']);
    if (!count($recipients)) {
     $recipients = array($recipients);
    }
    foreach ($recipients as $recipient) {
      if (!mailtron_validate_mail_input($recipient)) {
        form_set_error('recipients', t('You have entered an invalid email address in the "recipients" field: %mail', array('%mail' => $recipient)));
      } 
    }
  }

  if ($form_state['values']['cc']) {
    $ccs = explode(',', $form_state['values']['recipients']);
    if (!count($ccs)) {
      $ccs = array($ccs);
    }
    foreach ($ccs as $cc) {
      if (!mailtron_validate_mail_input($cc)) {
        form_set_error('cc', t('You have entered an invalid email address in the "cc" field: %mail', array('%mail' => $cc)));
      }
    }
  }

  if ($form_state['values']['bcc']) {
    $bccs = explode(',', $form_state['values']['bcc']);
    if (!count($bccs)) {
      $bccs = array($bccs);
    }
    foreach ($bccs as $bcc) {
      if (!mailtron_validate_mail_input($bcc)) {
        form_set_error('bcc', t('You have entered an invalid email address in the "bcc" field: %mail', array('%mail' => $bcc)));
      }
    }
  }

  if (!$form_state['values']['html_body'] && !$form_state['values']['text_body']) {
    form_set_error('html_body', t('You must include either a text or html body.'));
  }
}

function mailtron_mail_form_submit($form, $form_state) {
  $settings = _mailtron_concat_settings_from_submission($form_state['values']);
  if (!$settings->mid) {
    mailtron_save($settings);
  }
  else {
    mailtron_update($settings);
  }
}

/**
 * 
 */
function mailtron_validate_mail_input($mail) {
  return filter_var($mail, FILTER_VALIDATE_EMAIL);
}


/**
 * Mail configuration loader.
 */
function mailtron_mail_load($params = array()) {
  $mid = isset($params['mid']) ? $params['mid'] : FALSE;

  static $settings = array();
  if ($mid && $settings{$mid]) {
    return $settings[$mid];
  }
  elseif ($mid) {
    $settings['mid'] = db_fetch_object(db_query('SELECT * FROM {mailtron} WHERE mid = %d', $mid));
  }
  elseif (isset($params['module']) && isset($params['name'])) {
    $result = db_query("SELECT * FROM {mailtron} WHERE module = '%s' AND name = '%s'", $params['module'], $params['name']);
    $mail = db_fetch_object($result);

    if (isset($mail->mid)) {
      $mid = $mail->mid;
      $settings[$mid] = $mail; 
    }
  }

  return $mid ? $settings[$mid] : FALSE;
}


/**
 * Helper function, call this to send mail.
 *
 * @param $mail
 * mail object
 *
 * @param $params
 * Optional associative array containing additional parameters to send to callback functions.
 */
function mailtron_send_mail($mail, $params = array()) {
  // package settings for consumption by mimemail,
    module_load_include('module', 'mimemail');
    module_load_include('inc', 'mimemail', 'theme/mimemail.theme');
    if (isset($mail->recipient_callback) && $mail->recipient_callback) { 
      $function = $mail->recipient_callback;
      $mail->recipients = $function($mail, $params);
    }
    
    module_invoke_all('mimemail_send_mail_alter', $mail);

    $settings = mailtron_convert_settings($mail);
    mimemail($settings['sender'], $settings['recipient'], $settings['subject'], $settings['body'], FALSE, $settings['headers'], $settings['text']);
 
  // optionally call callback provided in $mail
  if (isset($mail->send_callback) && $mail->send_callback) {
    $function = $mail->send_callback;
    $function($mail, $params);
  }
}


/**
 * Converts mailtron $mail object into mimemail $settings array.
 *
 * @param $mail
 * mail object
 *
 * @return settings array formatted for consumption by mimemail.
 */
function mailtron_convert_settings($mail) {
  return array(
    'sender' => array(
      'name' => $mail->from_name,
      'mail' => $mail->from_mail,
    ),
    'recipient' => $mail->recipients,
    'subject' => $mail->subject,
    'body' => $mail->html_body,
    'headers' => array(
      'cc' => $mail->cc,
      'bcc' => $mail->bcc,
    ),
    'text' => $mail->text_body,
  );
}

/**
 * Convert submitted form values into a mail object.
 */
function _mailtron_concat_settings_from_submission($values) {
  $settings = array(
    'mid' => $values['mid'] ? $values['mid'] : NULL,
    'tid' => $values['tid'] ? $values['tid'] : NULL,
    'name' => $values['name'],
    'module' => $values['module'],
    'from_name' => $values['from_name'],
    'from_mail' => $values['from_mail'],
    'recipients' => $values['recipients'],
    'cc' => $values['cc'],
    'bcc' => $values['bcc'],
    'subject' => $values['subject'],
    'html_body' => $values['html_body'],
    'html_body_format' => $values['html_body_format'],
    'text_body' => $values['text_body'],
    'send_callback' => isset($values['send_callback']) ? $values['send_callback'] : NULL,
    'recipient_callback' => isset($values['recipient_callback']) ? $values['recipient_callback'] : NULL,
  );
  return (object) $settings;
}
