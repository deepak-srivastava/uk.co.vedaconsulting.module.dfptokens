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
    'uk_co_vedaconsulting_pcp.intro_text' => 'Veda: Fundraising Page: Intro Text',
    'uk_co_vedaconsulting_pcp.title'      => 'Veda: Fundraising Page: Title',
  );
}

function dfptokens_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  if (!empty($tokens['uk_co_vedaconsulting_pcp']) && $job) {
    $query = "
          SELECT cas.entity_value, cas.entity_status, cas.recipient
            FROM  civicrm_mailing_job cmj
      INNER JOIN civicrm_custom_track_mailing cctm ON cmj.mailing_id = cctm.mailing_id
      INNER JOIN civicrm_action_schedule cas ON cctm.schedule_reminder_id = cas.id
      INNER JOIN civicrm_action_mapping cam  ON cas.mapping_id = cam.id AND cam.entity = 'civicrm_activity'
      WHERE cmj.id = %1";
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($job, 'Integer')));
    if ($dao->fetch()) {
      $value = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($dao->entity_value, CRM_Core_DAO::VALUE_SEPARATOR)
      );

      // make sure activity types belong to that contribution component
      if (empty($value)) {
        return;
      } else {
        $compId = CRM_Core_Component::getComponentID('CiviContribute');
        $contributionActTypes = CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          " AND v.component_id={$compId}",
          'value'
        );
        if (array_intersect($value, $contributionActTypes) !== $value) {
          CRM_Core_Error::debug_log_message("Activity Type doesn't belong to that contribution component.");
          return;
        }
      }
      $value  = implode(',', $value);
      $status = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_status, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $status = implode(',', $status);

      $recipientOptions = CRM_Core_OptionGroup::values('activity_contacts');
      $contactField = $join ="";
      switch (CRM_Utils_Array::value($dao->recipient, $recipientOptions)) {
      case 'Activity Assignees':
        $contactField = 'r.assignee_contact_id';
        $join = 'INNER JOIN civicrm_activity_assignment r ON  r.activity_id = act.id';
        break;

      case 'Activity Source':
        $contactField = 'act.source_contact_id';
        break;

      case 'Activity Targets':
        $contactField = 'r.target_contact_id';
        $join = 'INNER JOIN civicrm_activity_target r ON  r.activity_id = act.id';
        break;
      }

      if (!empty($value)) {
        $where[] = "act.activity_type_id IN ({$value})";
      }
      if (!empty($status)) {
        $where[] = "act.status_id IN ({$status})";
      }
      $where[] = "act.is_current_revision = 1";
      $where[] = "act.is_deleted = 0";
      $where[] = "act.source_record_id IS NOT NULL";
      $where[] = "{$contactField} IN (" . implode(',', $cids) . ")";
      $whereClause  = 'WHERE ' . implode(' AND ', $where);

      $query = "
        SELECT {$contactField} as contactID, act.id as activityID, act.source_record_id, con.*, pcp.*
          FROM civicrm_activity act
       {$join}
    INNER JOIN  (SELECT $contactField as contact_id, MAX(act.activity_date_time) as max_date 
                   FROM civicrm_activity act 
                {$join}
         {$whereClause} 
               GROUP BY {$contactField}) maxact  ON act.activity_date_time = maxact.max_date AND {$contactField} = maxact.contact_id
    INNER JOIN civicrm_contribution con       ON act.source_record_id = con.id
     LEFT JOIN civicrm_contribution_soft soft ON con.id = soft.contribution_id
     LEFT JOIN civicrm_pcp pcp                ON soft.pcp_id = pcp.id
{$whereClause}
      GROUP BY {$contactField}";
      $data = CRM_Core_DAO::executeQuery($query);
      while ($data->fetch()) {
        $values[$data->contactID]['uk_co_vedaconsulting_pcp.intro_text'] = $data->intro_text;
      }
    }
  }
}

