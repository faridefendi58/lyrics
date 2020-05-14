<?php
foreach (glob(__DIR__.'/../components/*.php') as $component) {
    $cname = basename($component, '.php');
    if (!empty($cname)) {
        require_once $component;
    }
}

foreach (glob(__DIR__.'/*_controller.php') as $controller) {
    $cname = basename($controller, '.php');
    if (!empty($cname)) {
        require_once $controller;
    }
}

$app->group('/twofa', function () use ($user) {
    $this->group('/users', function() use ($user) {
        new Extensions\Controllers\FausersController($this, $user);
    });
});

?>
