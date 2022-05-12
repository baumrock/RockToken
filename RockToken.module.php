<?php namespace ProcessWire;

use RockToken\Token;

/**
 * @author Bernhard Baumrock, 16.02.2022
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
require_once('Token.php');
class RockToken extends WireData implements Module, ConfigurableModule {

  const endpoint = "token-login";
  const logoutUrl = "token-logout";
  const prefix = "rocktoken-";

  public static function getModuleInfo() {
    return [
      'title' => 'RockToken',
      'version' => '1.0.2',
      'summary' => 'Helper for token-based login',
      'autoload' => true,
      'singular' => true,
      'icon' => 'lock',
      'requires' => [],
      'installs' => [],
    ];
  }

  public function init() {
    $this->wire('rocktoken', $this);
    $this->checkLogout(); // logout if inactive

    // add hook to logout endpoint
    $this->wire->addHookAfter($this->logoutUrl(), function() {
      return $this->logout();
    });
  }

  public function checkLogout() {
    $session = $this->wire->session;
    $token = $this->getSessionToken();
    // bd($token, 'session token');

    // if no logout timestamp was set we exit early
    if(!$token->logout) return;

    // this user has been logged in via rocktoken!
    // check if the logout timestamp has been reached
    if(time() > $token->logout) $this->logout();

    // valid session, renew logout timestamp
    $token->logout = strtotime($token->duration);
    $session->rocktoken = $token->getArray();
  }

  /**
   * Create a new token
   * @return Token
   */
  public function create($settings = []) {
    $token = $this->wire(new Token($settings)); /** @var Token $token */
    $token->save();
    return $token;
  }

  /**
   * Get endpoint url
   * @return string
   */
  public function endpoint($host = false) {
    $url = trim($this->endpoint ?: self::endpoint, "/");
    $endpoint = $host
      ? $this->wire->pages->get(1)->httpUrl.$url
      : "/$url";
    return $endpoint;
  }

  /**
   * Get token data from session
   * @return WireData
   */
  public function getSessionToken() {
    $token = $this->wire(new WireData()); /** @var WireData $data */
    $token->setArray($this->wire->session->rocktoken ?: []);
    if($token->logout) {
      $token->set('logout (human)', date("Y-m-d H:i:s", $token->logout));
    }
    return $token;
  }

  /**
   * @return Token
   */
  public function getToken($name) {
    $arr = $this->wire->cache->get(self::prefix.$name);
    $token = $this->wire(new Token($arr)); /** @var Token $token */
    return $token;
  }

  public function logout() {
    $session = $this->wire->session;
    $token = $this->getSessionToken();

    // reset the rocktoken session storage
    $session->rocktoken = null;

    // if the user is logged in: log out (and clear session)
    if($this->wire->user->isLoggedin()) $this->wire->session->logout();

    // if a logout page redirect is defined we try to get the page now
    if($token->logoutPage) {
      $page = $this->wire->pages->get($token->logoutPage);
      if($page->id) return $session->redirect($page->url);
    }
    // otherwise redirect to frontpage
    else return $session->redirect($this->wire->pages->get(1)->url);
  }

  /**
   * @return string
   */
  public function logoutUrl($host = false) {
    $url = trim($this->logoutUrl ?: self::logoutUrl, "/");
    $endpoint = $host
      ? $this->wire->pages->get(1)->httpUrl.$url
      : "/$url";
    return $endpoint;
  }

  /**
   * Handle login via web url
   *
   * Usage (in ready.php):
   * $rocktoken->setup([
   *   'login' => function($token) {
   *     return $this->wire->pages->get('/your-token-success-page');
   *   },
   *   'failure' => function() { ... },
   *   'logout' => function() { ... },
   * ]);
   *
   * @return void
   */
  public function setup($callbacks) {
    $cb = $this->wire(new WireData()); /** @var WireData $cb */
    $cb->setArray($callbacks);

    $endpoint = $this->endpoint ?: self::endpoint;
    $this->wire->addHookAfter("/$endpoint/{token}", function($event) use($cb) {

      $session = $this->wire->session;
      $pages = $this->wire->pages;
      $token = $this->getToken($event->token);

      if($this->wire->input->get('preview', 'int')) {
        return $this->wire->files->render(__DIR__."/Preview.php", [
          'token' => $token,
          'url' => $token->url(),
        ]);
      }

      if($token AND $token->loginsLeft()) {
        // successful login

        // setup logout timestamp
        $logout_timestamp = strtotime($token->logout);
        $sessiondata = [
          'logout' => $logout_timestamp,
          'duration' => $token->logout,
        ];

        // execute login callback
        if(is_string($cb->login)) $page = $pages->get($cb->login);
        elseif($cb->login) {
          $page = $cb->login->__invoke($token);
          if($page === false) $page = $this->getFailurePage($cb);
        }

        // save logout redirect callback to session
        if(is_string($cb->logout)) $logoutPage = $pages->get($cb->logout);
        elseif($cb->logout) $logoutPage = $cb->logout->__invoke();
        $sessiondata['logoutPage'] = $logoutPage->id;

        // save data to session
        $session->rocktoken = $sessiondata;
      }
      else $page = $this->getFailurePage($cb);

      // redirect to page
      if($page->id) $session->redirect($page->url);
      else $session->redirect($this->wire->pages->get(1)->url);
    });
  }

  public function getFailurePage($cb) {
    if(is_string($cb->failure)) $page = $pages->get($cb->failure);
    elseif($cb->failure) $page = $cb->failure->__invoke();
    return $page;
  }

  /**
  * Config inputfields
  * @param InputfieldWrapper $inputfields
  */
  public function getModuleConfigInputfields($inputfields) {

    $inputfields->add([
      'name' => 'endpoint',
      'type' => 'text',
      'label' => $this->_('Login Endpoint'),
      'value' => $this->endpoint(),
      'notes' => $this->endpoint(true)."/{token}",
    ]);

    $inputfields->add([
      'name' => 'logoutUrl',
      'type' => 'text',
      'label' => $this->_('Logout Endpoint'),
      'value' => $this->logoutUrl(),
      'notes' => $this->logoutUrl(true),
    ]);

    return $inputfields;
  }

}
