<?php namespace RockToken;

use ProcessWire\RockToken;
use ProcessWire\WireData;
use ProcessWire\WireRandom;

class Token extends WireData {

  public function __construct($settings = []) {
    $this->setArray([
      'created' => time(),
      'random' => [
        'minLength' => 30,
        'maxLength' => 50,
      ],
      'logins' => 1, // token can be used for 1 login
      'expire' => '15min', // time after that the login-link expires
      'expireTS' => null, // for sort order
      'logout' => '60min', // logout if user is inactive for that time
    ]);
    $this->setArray($settings ?: []);
    if(!$this->token) {
      $rand = $this->wire(new WireRandom()); /** @var WireRandom $rand */
      $this->token = $rand->alphanumeric(0, $this->random);
    }
    $this->name = RockToken::prefix.$this->token;
    $this->expireTS = strtotime($this->expire);
  }

  /**
   * Return formatted expire datetime
   * @return string
   */
  public function expire($format = "Y-m-d H:i:s") {
    return date($format, $this->expireTS);
  }

  /**
   * Create clickable link for this token
   *
   * If the preview param is set to TRUE the link will contain ?preview=1
   * This will show a confirmation page to the user and redirect it to the
   * success page. This is an additional step to prevent mail client previews
   * from unvalidating single-use-tokens!
   *
   * @return string
   */
  public function link($blank = true, $preview = false) {
    $url = $this->url();
    $blank = $blank ? " target='_blank'" : "";
    return "<a href='$url?preview=$preview' $blank>$url</a>";
  }

  /**
   * Check if logins are left for this tokens
   */
  public function loginsLeft($reduce = true) {
    $logins = $this->logins;
    if($logins < 1) return false;
    if($reduce) {
      $this->logins = $logins-1;
      $this->save();
    }
    return $logins;
  }

  public function save() {
    $this->wire->cache->save($this->name, $this->sleep(), $this->expire);
  }

  /**
   * Get sleep data for this token
   * @return array
   */
  public function sleep() {
    $sleep = $this->getArray();
    unset($sleep['name']);
    unset($sleep['random']);
    return $sleep;
  }

  public function url() {
    /** @var RockToken $token */
    $token = $this->wire->modules->get('RockToken');
    return $token->endpoint(true)."/{$this->token}";
  }

  public function __debugInfo() {
    return array_merge($this->sleep(), [
      'url()' => $this->url(),
    ]);
  }

}
