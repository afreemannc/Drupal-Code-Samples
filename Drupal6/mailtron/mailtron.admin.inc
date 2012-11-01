<?php


/**
 * Build form.
 */
function mailtron_admin_build() {
  $form = array();
  // test for existing mail entries
  // if present, list.

  // list templates?
  // add template?

  // add email entry form.
  // template selection, optional event trigger
  // 2nd pass edit form provides opportunity to customize settings pulled in from template.
  return $form;
}

function mailtron_admin_build_validate() {

}

function mailtron_admin_build_submit() {

}

/**
 * Bog standard admin settings form.
 */
function mailtron_admin_settings() {
  $form = array();

  return system_settings_form($form);
}
