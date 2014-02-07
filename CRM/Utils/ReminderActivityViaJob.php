<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * 
 *
 */
class CRM_Utils_ReminderActivityViaJob {

  protected $_value  = array();

  protected $_status = array();

  protected $_recipient = NULL;

  public    $_actionScheduleId = NULL;

  protected $_pcpActivityTypes = array('Created fundraising page');

  function __construct($jobID) {
    $query = "
          SELECT cas.id, cas.entity_value, cas.entity_status, cas.recipient
            FROM civicrm_mailing_job cmj
      INNER JOIN civicrm_custom_track_mailing cctm ON cmj.mailing_id = cctm.mailing_id
      INNER JOIN civicrm_action_schedule cas ON cctm.schedule_reminder_id = cas.id
      INNER JOIN civicrm_action_mapping cam  ON cas.mapping_id = cam.id AND cam.entity = 'civicrm_activity'
           WHERE cmj.id = %1";
    $actionSchedule = CRM_Core_DAO::executeQuery($query, array(1 => array($jobID, 'Integer')));
    if ($actionSchedule->fetch()) {
      $this->_value  = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_value, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $this->_status = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        trim($actionSchedule->entity_status, CRM_Core_DAO::VALUE_SEPARATOR)
      );
      $this->_recipient = $actionSchedule->recipient;
      $this->_actionScheduleId = $actionSchedule->id;
    }
  }

  function getActivityQueryVars($cids = array()) {
    $value  = implode(',', $this->_value);
    $status = implode(',', $this->_status);

    $recipientOptions = CRM_Core_OptionGroup::values('activity_contacts');
    $contactField = $join ="";
    switch (CRM_Utils_Array::value($this->_recipient, $recipientOptions)) {
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

    return array($whereClause, $join, $contactField);
  }

  function buildContributeActivityQuery($cids = array()) {
    list($whereClause, $join, $contactField) = $this->getActivityQueryVars($cids);
    $query = "
        SELECT {$contactField} as contactID, 
               con.total_amount as con_total_amount, 
               pcp.intro_text as pcp_intro_text,
               pcp.title      as pcp_title,
               screditor.first_name as screditor_first_name,
               screditor.last_name  as screditor_last_name
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
     LEFT JOIN civicrm_contact screditor      ON soft.contact_id = screditor.id
{$whereClause}
      GROUP BY {$contactField}";
    return $query;
  }


  function buildPCPActivityQuery($cids = array()) {
    list($whereClause, $join, $contactField) = $this->getActivityQueryVars($cids);
    $query = "
        SELECT {$contactField} as contactID, 
               pcp.intro_text as pcp_intro_text,
               pcp.title      as pcp_title,
               screditor.first_name as screditor_first_name,
               screditor.last_name  as screditor_last_name
          FROM civicrm_activity act
       {$join}
    INNER JOIN  (SELECT $contactField as contact_id, MAX(act.activity_date_time) as max_date 
                   FROM civicrm_activity act 
                {$join}
         {$whereClause} 
               GROUP BY {$contactField}) maxact  ON act.activity_date_time = maxact.max_date AND {$contactField} = maxact.contact_id
    INNER JOIN civicrm_pcp pcp                ON act.source_record_id = pcp.id
    INNER JOIN civicrm_contact screditor      ON pcp.contact_id = screditor.id
{$whereClause}
      GROUP BY {$contactField}";
    return $query;
  }

  function isActivityTypeBelongToContribute() {
    if (empty($this->_value)) {
      return FALSE;
    } else {
      $compId = CRM_Core_Component::getComponentID('CiviContribute');
      $contributionActTypes = 
        CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          " AND v.component_id={$compId}",
          'value'
        );
      if (array_intersect($this->_value, $contributionActTypes) == $this->_value) {
        return TRUE;
      }
      return FALSE;
    }
  }

  function isActivityTypeBelongToPCP() {
    if (empty($this->_value)) {
      return FALSE;
    } else {
      $activityTypeClause  = "v.label IN ('" . implode("', '", $this->_pcpActivityTypes) . "')";
      $activityTypes       = 
        CRM_Core_OptionGroup::values('activity_type',
          FALSE, FALSE, FALSE,
          " AND v.component_id IS NULL AND {$activityTypeClause} ",
          'value'
        );
      if (array_intersect($this->_value, $activityTypes) == $this->_value) {
        return TRUE;
      }
      return FALSE;
    }
  }
}

