<?php

class CRM_Gidipirus_Model_Campaign {

  function __construct($campaignId) {
    $this->fieldLanguage = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_language');
    $this->fieldSenderMail = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_sender_mail');
    $this->fieldSubjectNew = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_subject_new');
    $this->fieldMessageNew = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_message_new');
    $this->fieldConsentIds = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_campaign_consent_ids');
    $this->campArray = CRM_Speakcivi_Logic_Cache_Campaign::getCampaignByLocalId($campaignId);
  }

  public function getLanguage() {
    return $this->campArray[$this->fieldLanguage];
  }

  public function getSenderMail() {
    return $this->campArray[$this->fieldSenderMail];
  }

  public function getSubjectNew() {
    return $this->campArray[$this->fieldSubjectNew];
  }

  public function getSubjectCurrent() {
    return $this->campArray[$this->fieldSubjectCurrent];
  }

  public function getMessageNew() {
    return $this->campArray[$this->fieldMessageNew];
  }

  public function getConsentIds() {
    return explode(',', $this->campArray[$this->fieldConsentIds]);
  }
}
