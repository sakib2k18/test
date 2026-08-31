<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bus Portfolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/test/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/test/index.php">Bus Portfolio</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/test/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
      </ul>
    </div>
  </div>
 </nav>
<main class="container py-4">
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bus Portfolio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/test/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="/test/index.php">Bus Portfolio</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/test/index.php">Home</a></li>
        <?php if (empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="/test/login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="/test/signup.php">Sign up</a></li>
        <?php else: ?>
          <?php if (!empty($_SESSION['is_admin'])): ?>
            <li class="nav-item"><a class="nav-link" href="/test/admin/index.php">Admin</a></li>
          <?php endif; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?=htmlspecialchars($_SESSION['user_name'])?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/test/logout.php">Logout</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
 </nav>
<main class="container py-4">
