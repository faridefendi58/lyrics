<?php
// frontend url
$app->get('/lirik/search', function ($request, $response, $args) {
    $model = new \ExtensionsModel\SongModel();
    $params = $request->getParams();

    return $this->view->render($response, 'song_search.phtml', [
        'model' => $model,
        'params' => $params,
    ]);
});
$app->get('/lirik[/{artist}[/{title}]]', function ($request, $response, $args) {
    $theme = $this->settings['theme'];
    $model = new \ExtensionsModel\SongModel();

    if (empty($args['title'])) {
        $abjad = $model->getAbjad(['slug' => $args['artist']]);
        if (is_array($abjad) && !empty($abjad['id'])) {

            return $this->view->render($response, 'song_abjad.phtml', [
                'abjad' => $abjad,
                'model' => $model
            ]);
        }
    } else {
        $data = $model->getSong($args['title']);

        return $this->view->render($response, 'song_lyric.phtml', [
            'data' => $data,
            'msong' => $model
        ]);
    }

    return $this->response
        ->withStatus(500)
        ->withHeader('Content-Type', 'text/html')
        ->write('Page not found!');
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

$app->group('/song', function () use ($user) {
    $this->group('/summary', function() use ($user) {
        new Extensions\Controllers\SummaryController($this, $user);
    });
    $this->group('/lyrics', function() use ($user) {
        new Extensions\Controllers\LyricsController($this, $user);
    });
    $this->group('/artists', function() use ($user) {
        new Extensions\Controllers\ArtistController($this, $user);
    });
});

?>
