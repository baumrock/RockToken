<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <style>
  html,body {height: 100vh; margin: 0; padding: 0;}
  body {
    display: flex;
    justify-content: center;
    align-items: center;
  }
  body > div { text-align: center; }
  </style>
</head>
<body>
  <div>
    <h3>Please click this link to login</h3>
    <a href="<?= $url ?>"><?= $url ?></a>
    <script>window.location.href = "<?= $url ?>";</script>
  </div>
</body>
</html>
