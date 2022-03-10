# RockToken

## Quickstart

Create a token (eg after form submission):

```php
/** @var RockToken $token */
$token = $this->wire('modules')->get('RockToken')
  ->create([
    // link to login via token will expire 15min after creation
    'expire' => '15min',

    // user will be logged out after 1 hour of inactivity
    'logout' => '1hour',

    // you can define any property you like
    // here we store the current submitted email that we later use for login
    'email' => $form->data->email,
  ]);
```

Then define what should happen on login, failure and logout:

```php
// site/ready.php
$rocktoken->setup([
  'login' => function($token) {
    $email = $token->email;
    $user = $this->wire->users->get("email=$email");
    if($user->id) {
      $this->wire->session->forceLogin($user);
      return $this->wire->pages->get("/your-success-page");
    }
    // return false triggers the failure callback
    // that means we show /something-went-wrong-page here
    return false;
  },
  'failure' => "/something-went-wrong-page",
  'logout' => "/your-token-logout-page",
]);
```

You can also set callbacks for the other properties if you need:

```php
$rocktoken->setup([
  'login' => function($token) {
    $this->wire->users->forceLogin($token->user);
    return $this->wire->pages->get("/your-success-page");
  },
  'failure' => function() {
    return $this->wire->pages->get("/your-token-login-failure-page");
  },
  'logout' => function() {
    return $this->wire->pages->get("/your-token-logout-page");
  },
]);
```

## Logout

Create an logout link like this:

```php
<a href="<?= $rocktoken->logoutUrl() ?>">Logout</a>
```
