<?php

use CRM_Gidipirus_ExtensionUtil as E;
require_once 'CRM/Core/Form.php';

class CRM_Gidipirus_Form_Forgetme extends CRM_Core_Form {

  private $buttons = [];
  private $fields = [];
  private $contactId;
  private $activityId;
  private $subName;
  private $statusId;

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    CRM_Utils_System::setTitle(E::ts('ForgetMe'));
    $this->fields = [
      'request_date' => [
        'type' => 'datepicker',
        'label' => E::ts("Request date"),
        'options' => [],
        'required' => TRUE,
        'extra' => array('time' => FALSE, 'date' => 'yy-mm-dd'),
        'default' => date('Y-m-d'),
        'order' => 10,
      ],
      'request_channel' => [
        'type' => 'Select',
        'label' => E::ts("Request channel"),
        'options' => CRM_Gidipirus_Model_RequestChannel::$values,
        'required' => TRUE,
        'default' => '',
        'order' => 20,
      ],
    ];
    $this->buttons = [
      'register' => [
        'type' => 'submit',
        'name' => ts('Register forget request'),
        'isDefault' => TRUE,
        'icon' => 'fa-envelope-open',
        'subName' => 'register',
      ],
      'forget' => [
        'type' => 'done',
        'name' => ts('Force forget'),
        'isDefault' => FALSE,
        'icon' => 'fa-eraser',
        'subName' => 'forget',
      ],
    ];
    parent::__construct($state, $action, $method, $name);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    $this->contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->activityId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE);
    $this->subName = $this->controller->_actionName[1];

    $this->statusId = $this->getStatus($this->contactId);
    $this->setButtonsState($this->statusId);

    $this->assign('displayName', $this->getDisplayName($this->contactId));
    $this->assign('statusId', $this->statusId);
    $this->assign('contactId', $this->contactId);
    $this->assign('subName', $this->subName);
    $this->assign('forgetmeValue', CRM_Gidipirus_Model_ForgetmeStatus::$value);
    $this->assign('forgetmeStatus', CRM_Gidipirus_Model_ForgetmeStatus::$nameToValue);
    $this->assign('extensionKey', E::LONG_NAME);
  }

  public function buildQuickForm() {
    foreach ($this->fields as $key => $field) {
      $this->add($field['type'], $key, $field['label'], ['' => '- select -'] + $field['options'], $field['required'], @$field['extra']);
    }
    $this->addButtons($this->buttons);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $defaults = array();
    foreach ($this->fields as $key => $setting) {
      if (array_key_exists('default', $setting)) {
        $defaults[$key] = $setting['default'];
      }
    }
    return $defaults;
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    $channel = $this->_submitValues['request_channel'];
    $requestDate = $this->_submitValues['request_date'];
    switch ($this->subName) {
      case 'submit':
        $result = $this->register($this->contactId, $channel, $requestDate);
        $this->setMessageRegister($result);
        break;

      case 'done':
        $result = $this->register($this->contactId, $channel, $requestDate);
        if ($result) {
          $result = $this->forget($this->contactId);
          $this->setMessageForget($result);
        }
        else {
          $this->setMessageRegister($result);
        }
        break;
    }
    $url = CRM_Utils_System::url('civicrm/gidipirus/forgetme', ['cid' => $this->contactId]);
    CRM_Utils_System::redirect($url);
  }

  private function disableForget() {
    $this->disableButton('forget');
  }

  private function disableRegister() {
    $this->disableButton('register');
  }

  private function disableButton($type) {
    if (CRM_Utils_Array::value('js', $this->buttons[$type])) {
      $this->buttons[$type]['js'] = array_merge($this->buttons[$type]['js'], ['disabled' => 'disabled']);
    }
    else {
      $this->buttons[$type]['js'] = ['disabled' => 'disabled'];
    }
  }

  /**
   * @param $contactId
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getStatus($contactId) {
    $result = civicrm_api3('Gidipirus', 'status', [
      'sequential' => 1,
      'contact_id' => $contactId,
    ]);
    return (int) $result['values'][0]['status'];
  }

  /**
   * @param $contactId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function getDisplayName($contactId) {
    $params = [
      'sequential' => 1,
      'id' => $contactId,
      'return' => 'display_name',
    ];
    $result = civicrm_api3('Contact', 'get', $params);
    return $result['values'][0]['display_name'];
  }

  /**
   * @param int $statusId
   */
  private function setButtonsState($statusId) {
    switch ($statusId) {
      case CRM_Gidipirus_Model_ForgetmeStatus::READY_VALUE:
        break;

      case CRM_Gidipirus_Model_ForgetmeStatus::IN_PROGRESS_VALUE:
      case CRM_Gidipirus_Model_ForgetmeStatus::OBSOLETE_VALUE:
        $this->disableRegister();
        break;

      case CRM_Gidipirus_Model_ForgetmeStatus::COMPLETED_VALUE:
      case CRM_Gidipirus_Model_ForgetmeStatus::BLOCKED_VALUE:
      case CRM_Gidipirus_Model_ForgetmeStatus::TOO_MANY_REQUESTS_VALUE:
        $this->disableRegister();
        $this->disableForget();
        break;
    }
  }

  /**
   * @param int $contactId
   * @param string $channel
   * @param string $requestedDate
   * @param int $parentActivityId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function register($contactId, $channel, $requestedDate, $parentActivityId = 0) {
    $params = [
      'sequential' => 1,
      'contact_ids' => $contactId,
      'channel' => $channel,
      'requested_date' => $requestedDate,
      'activity_parent_id' => $parentActivityId,
    ];
    $result = civicrm_api3('Gidipirus', 'register', $params);
    return $result['values'][0]['result'];
  }

  /**
   * @param int $contactId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function forget($contactId) {
    $params = [
      'sequential' => 1,
      'contact_ids' => $contactId,
    ];
    $result = civicrm_api3('Gidipirus', 'forget', $params);
    return $result['values'][0];
  }

  /**
   * @param $result
   */
  private function setMessageRegister($result) {
    if ($result) {
      CRM_Core_Session::setStatus(E::ts('Registered request'), 'Gidipirus', 'success');
    }
    else {
      CRM_Core_Session::setStatus(E::ts('There is a problem with registering request'), 'Gidipirus');
    }
  }

  /**
   * @param $result
   */
  private function setMessageForget($result) {
    if ($result['result']) {
      CRM_Core_Session::setStatus(E::ts('Contact is forgotten now'), 'Gidipirus', 'success');
    }
    else {
      CRM_Core_Session::setStatus(E::ts('There is a problem with forgetting the contact: %1', [1 => $result['error']]), 'Gidipirus');
    }
  }

}
