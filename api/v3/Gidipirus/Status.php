<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_status_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => E::ts('Contact Id'),
    'description' => E::ts('Contact Id'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
}

/**
 * Calculate Forgetme Status
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_gidipirus_status(&$params) {
  $start = microtime(TRUE);
  $contactId = $params['contact_id'];
  $queryDonor = "SELECT count(id) is_donor
                 FROM civicrm_contribution
                 WHERE contact_id = %1 AND contribution_status_id = 1";
  $queryDonorParams = [
    1 => [$contactId, 'Integer'],
  ];
  $isDonor = CRM_Core_DAO::singleValueQuery($queryDonor, $queryDonorParams);
  if ($isDonor) {
    $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::BLOCKED_VALUE;
  }
  else {
    $query = "SELECT
                CASE
                  WHEN af.status_id = 1 AND DATE_FORMAT(af.activity_date_time, '%Y-%m-%d') >= CURRENT_DATE THEN %3
                  WHEN af.status_id = 1 AND DATE_FORMAT(af.activity_date_time, '%Y-%m-%d') < CURRENT_DATE THEN %4
                  WHEN af.status_id = 2 THEN %5
                END forgetme_status
              FROM civicrm_activity af
                JOIN civicrm_activity_contact acf ON acf.activity_id = af.id AND acf.record_type_id = 3
              WHERE acf.contact_id = %1 AND af.activity_type_id = %2";
    $queryParams = [
      1 => [$contactId, 'Integer'],
      2 => [CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId(), 'Integer'],
      3 => [CRM_Gidipirus_Model_ForgetmeStatus::IN_PROGRESS_VALUE, 'Integer'],
      4 => [CRM_Gidipirus_Model_ForgetmeStatus::OBSOLETE_VALUE, 'Integer'],
      5 => [CRM_Gidipirus_Model_ForgetmeStatus::COMPLETED_VALUE, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->N > 1) {
      $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::TOO_MANY_REQUESTS_VALUE;
    }
    elseif ($dao->N == 1) {
      $dao->fetch();
      $forgetmeStatus = $dao->forgetme_status;
    }
    else {
      $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::READY_VALUE;
    }
    $dao->free();
  }

  $values = [
    $contactId => [
      'status' => $forgetmeStatus,
    ],
  ];
  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
