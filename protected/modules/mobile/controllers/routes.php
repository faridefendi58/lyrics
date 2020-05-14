<?php
// pos routes
$app->get('/mobile', function ($request, $response, $args) use ($user) {

});

foreach (glob(__DIR__.'/*_controller.php') as $controller) {
	$cname = basename($controller, '.php');
	if (!empty($cname)) {
		require_once $controller;
	}
}

foreach (glob(__DIR__.'/../components/*.php') as $component) {
    $cname = basename($component, '.php');
    if (!empty($cname)) {
        require_once $component;
    }
}

$app->group('/mobile', function () use ($user) {
    $this->group('/chord', function() use ($user) {
        new Mobile\Controllers\ChordController($this, $user);
    });
	$this->group('/artist', function() use ($user) {
        new Mobile\Controllers\ArtistController($this, $user);
    });
});

?>
