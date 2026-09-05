<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <title>ASDACA | Login</title>

  <link rel="stylesheet" href="plugins/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
  <link rel="stylesheet" href="css/style.css">

  <script src="js/auth.js"></script>

  <script>
    Auth.requireGuest();
  </script>
</head>
<body class="hold-transition login-page asdaca-login-page">

  <div class="login-box asdaca-login-box">

    <div class="login-logo asdaca-login-logo">
      <img src="images/logo.png" alt="ASDACA" class="asdaca-login-logo-img">
      <a href="login"><b>Associazione Canavesana Arbitri</b></a>
    </div>

    <div class="login-box-body asdaca-login-body">

      <p class="login-box-msg">
        Accedi al pannello di gestione
      </p>

      <form id="loginForm" novalidate>

        <div id="wrapUsername" class="form-group has-feedback">
          <input type="text"
                 id="username"
                 class="form-control"
                 placeholder="Username"
                 autocomplete="username"
                 required>
          <span class="fa fa-user form-control-feedback"></span>
        </div>

        <div id="wrapPassword" class="form-group has-feedback">
          <input type="password"
                 id="password"
                 class="form-control"
                 placeholder="Password"
                 autocomplete="current-password"
                 required>
          <span class="fa fa-lock form-control-feedback"></span>
        </div>

        <div id="loginError" class="alert alert-danger asdaca-login-error" role="alert"></div>

        <div class="row">
          <div class="col-xs-12">
            <button type="submit" class="btn btn-primary btn-block btn-flat">
              <i class="fa fa-sign-in"></i>
              Login
            </button>
          </div>
        </div>

      </form>

    </div>

  </div>

  <script>
  const loginForm = document.getElementById('loginForm');
  const loginError = document.getElementById('loginError');
  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');

  function showLoginError(message) {
    loginError.innerText = message;
    loginError.style.display = 'block';
  }

  function clearLoginError() {
    loginError.innerText = '';
    loginError.style.display = 'none';
  }

  loginForm.addEventListener('submit', async e => {
    e.preventDefault();
    clearLoginError();

    let res;

    try {
      res = await fetch('../api/v1/login', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          username: usernameInput.value,
          password: passwordInput.value
        })
      });
    } catch (e) {
      showLoginError('Errore di rete');
      return;
    }

    const contentType = res.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      showLoginError('Risposta non valida dal server');
      return;
    }

    let json;
    try {
      json = await res.json();
    } catch (e) {
      showLoginError('JSON non valido dal server');
      return;
    }

    if (!json.success) {
      if (json.errors) {
        showLoginError(Object.values(json.errors).join(' - '));
        return;
      }

      showLoginError(json.error || 'Login non riuscito');
      return;
    }

    Auth.login(json.data?.token);
  });
  </script>

</body>
</html>
