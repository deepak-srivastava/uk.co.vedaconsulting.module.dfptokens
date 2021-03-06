<?php

require_once 'dfptokens.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function dfptokens_civicrm_config(&$config) {
  _dfptokens_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function dfptokens_civicrm_xmlMenu(&$files) {
  _dfptokens_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function dfptokens_civicrm_install() {
  return _dfptokens_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function dfptokens_civicrm_uninstall() {
  return _dfptokens_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function dfptokens_civicrm_enable() {
  return _dfptokens_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function dfptokens_civicrm_disable() {
  return _dfptokens_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function dfptokens_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _dfptokens_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function dfptokens_civicrm_managed(&$entities) {
  return _dfptokens_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function dfptokens_civicrm_caseTypes(&$caseTypes) {
  _dfptokens_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function dfptokens_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _dfptokens_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function dfptokens_civicrm_tokens(&$tokens) {
  $tokens['uk_co_vedaconsulting_pcp'] = array(
    'uk_co_vedaconsulting_pcp.intro_text' => 'DFP: Fundraising Page: Intro Text',
    'uk_co_vedaconsulting_pcp.title'      => 'DFP: Fundraising Page: Title',
    'uk_co_vedaconsulting_pcp.url'        => 'DFP: Fundraising Page: URL',
    'uk_co_vedaconsulting_pcp.thanku'     => 'DFP: Fundraising Page: Thank You Message',
    'uk_co_vedaconsulting_pcp.target'     => 'DFP: Fundraising Page: Target Amount',
    'uk_co_vedaconsulting_pcp.raised'     => 'DFP: Fundraising Page: Amount Raised So Far',
  );
  $tokens['uk_co_vedaconsulting_screditor'] = array(
    'uk_co_vedaconsulting_screditor.first_name' => 'DFP: Fundraiser: First Name',
    'uk_co_vedaconsulting_screditor.last_name'  => 'DFP: Fundraiser: Last Name',
  );
  $tokens['uk_co_vedaconsulting_donor'] = array(
    'uk_co_vedaconsulting_donor.total_amount' => 'DFP: Donation: Amount',
    'uk_co_vedaconsulting_donor.first_name'   => 'DFP: Donor: First Name',
    'uk_co_vedaconsulting_donor.last_name'    => 'DFP: Donor: Last Name',
  );
}

function dfptokens_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  if ((array_key_exists('uk_co_vedaconsulting_pcp', $tokens) ||
    array_key_exists('uk_co_vedaconsulting_screditor', $tokens) ||
    array_key_exists('uk_co_vedaconsulting_donor',  $tokens) ) && $job) {

    $remActObj = new CRM_Utils_ReminderActivityViaJob($job);
    if ($remActObj->_actionScheduleId) {
      if ($contribute = $remActObj->isActivityTypeBelongToContribute()) {
        $query = $remActObj->buildContributeActivityQuery($cids);
      } else if ($pcp = $remActObj->isActivityTypeBelongToPCP()) {
        $query = $remActObj->buildPCPActivityQuery($cids);
      } else {
        CRM_Core_Error::debug_log_message("Activity Type doesn't belong to contribution component or PCP.");
        return;
      }

      $data = CRM_Core_DAO::executeQuery($query);
      while ($data->fetch()) {
        $values[$data->contactID]['uk_co_vedaconsulting_pcp.title']            = $data->pcp_title;
        $values[$data->contactID]['uk_co_vedaconsulting_pcp.intro_text']       = $data->pcp_intro_text;
          $values[$data->contactID]['uk_co_vedaconsulting_pcp.target']         = $data->pcp_goal_amount;
        $values[$data->contactID]['uk_co_vedaconsulting_screditor.first_name'] = $data->screditor_first_name;
        $values[$data->contactID]['uk_co_vedaconsulting_screditor.last_name']  = $data->screditor_last_name;
        if ($contribute) {
          $values[$data->contactID]['uk_co_vedaconsulting_donor.total_amount'] = $data->donor_total_amount;
          $values[$data->contactID]['uk_co_vedaconsulting_donor.first_name']   = $data->donor_first_name;
          $values[$data->contactID]['uk_co_vedaconsulting_donor.last_name']    = $data->donor_last_name;
        }
        if ($data->pcp_id) {
          if ($node = $remActObj->getDFPNode($data->pcp_id)) {
            $values[$data->contactID]['uk_co_vedaconsulting_pcp.url']    = CRM_Utils_System::url("node/{$node->nid}", NULL, TRUE);
            $values[$data->contactID]['uk_co_vedaconsulting_pcp.thanku'] = $node->field_dfp_thank_you[LANGUAGE_NONE][0]['value'];
          }
          $values[$data->contactID]['uk_co_vedaconsulting_pcp.raised']   = $remActObj->getDFPRaisedAmount($data->pcp_id);
        }
      }
    }
  }
}

