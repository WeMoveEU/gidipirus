<?php

class CRM_Gidipirus_Model_Activity extends CRM_Gidipirus_Model {

  const FORGETME_FULFILLMENT = 'Forgetme Fulfillment';
  const INBOUND_EMAIL = 'Inbound Email';

  /**
   * Get activity type id for Forgetme Fulfillment
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function forgetmeFulfillmentId() {
    $key = __CLASS__ . '.' .  __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::FORGETME_FULFILLMENT);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get activity type id for Inbound Email
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function inboundEmailId() {
    $key = __CLASS__ . '.' . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::INBOUND_EMAIL);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get or create activity type
   *
   * @param $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private static function set($name) {
    return self::optionValue('activity_type', $name);
  }

}
