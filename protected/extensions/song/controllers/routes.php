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
        // it should be the title = artist slug if the length > 0
        if (strlen($args['artist']) > 1){
            $amodel = \ExtensionsModel\SongArtistModel::model()->findByAttributes(['slug' => $args['artist']]);
            $songs = null;
            if ($amodel instanceof \RedBeanPHP\OODBBean) {
                $songs = $model->getSongs(['artist_id' => $amodel->id]);
            }

            return $this->view->render($response, 'song_lyric_index.phtml', [
                'model' => $model,
                'songs' => $songs,
                'amodel' => $amodel
            ]);
        } else { //if the title is Abjad
            $abmodel = \ExtensionsModel\SongAbjadModel::model()->findByAttributes(['title' => strtoupper($args['artist'])]);
            $artists = null;
            if ($abmodel instanceof \RedBeanPHP\OODBBean) {
                $artists = $model->getArtists(['abjad_id' => $abmodel->id]);
            }

            return $this->view->render($response, 'song_lyric_index.phtml', [
                'model' => $model,
                'selected_abjad' => strtoupper($args['artist']),
                'artists' => $artists
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
