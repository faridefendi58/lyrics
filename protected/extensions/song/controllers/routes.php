<?php
// just redirection detected error by google
$app->get('/search', function ($request, $response, $args) {
    $params = $request->getParams();
    if (empty($params['q'])) {
        $params['q'] = 'lirik';
    }

    return $response->withStatus(301)->withHeader('Location', '/lirik/search?q='. $params['q']);
});
// also just redirection detected error by google
$app->get('/artist/[{name}]', function ($request, $response, $args) {
    return $response->withStatus(301)->withHeader('Location', '/');
});
// frontend url
$app->get('/lirik/search', function ($request, $response, $args) use ($app) {
    $model = new \ExtensionsModel\SongModel();
    $params = $request->getParams();
    if ($params['type'] == 'chord' || $params['type'] == 'kord') {
        return $response->withRedirect('/kord/search?q='.$params['q']);
    }

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
                $songs = $model->getSongs(['artist_id' => $amodel->id, 'type' => 'lyric']);
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
                $artists = $model->getArtists(['abjad_id' => $abmodel->id, 'has_lyric' => true]);
            }

            return $this->view->render($response, 'song_lyric_index.phtml', [
                'model' => $model,
                'selected_abjad' => strtoupper($args['artist']),
                'artists' => $artists
            ]);
        }
    } else {
        $data = $model->getSong($args['title']);
        if (empty($data)) {
            // check by title
            $data = $model->getSongByTitle(strtolower($args['title']), $args['artist']);

            if (!empty($data['lyric_permalink']))
                return $response->withRedirect('/lirik/'.$args['artist'].'/'.$data['lyric_permalink']);
            else
                return $response->withRedirect('/lirik/'.$args['artist'].'/'.$data['lyric_slug']);
        } else {
            if (!empty($data['lyric_permalink']) && $data['lyric_permalink'] != $args['title']) {
                return $response->withRedirect('/lirik/'.$args['artist'].'/'.$data['lyric_permalink']);
            }
        }

        return $this->view->render($response, 'song_lyric.phtml', [
            'data' => $data,
            'msong' => $model
        ]);
    }

    return $this->view->render($response, '404.phtml');
});

$app->get('/kord/search', function ($request, $response, $args) {
    $model = new \ExtensionsModel\SongModel();
    $params = $request->getParams();

    return $this->view->render($response, 'chord_search.phtml', [
        'model' => $model,
        'params' => $params,
    ]);
});

$app->get('/kord[/{artist}[/{title}]]', function ($request, $response, $args) {
    $theme = $this->settings['theme'];
    $model = new \ExtensionsModel\SongModel();

    if (empty($args['title'])) {
        // it should be the title = artist slug if the length > 0
        if (strlen($args['artist']) > 1){
            $amodel = \ExtensionsModel\SongArtistModel::model()->findByAttributes(['slug' => $args['artist']]);
            $songs = null;
            if ($amodel instanceof \RedBeanPHP\OODBBean) {
                $songs = $model->getSongs(['artist_id' => $amodel->id, 'type'=>'chord']);
            }

            return $this->view->render($response, 'song_chord_index.phtml', [
                'model' => $model,
                'songs' => $songs,
                'amodel' => $amodel
            ]);
        } else { //if the title is Abjad
            $abmodel = \ExtensionsModel\SongAbjadModel::model()->findByAttributes(['title' => strtoupper($args['artist'])]);
            $artists = null;
            if ($abmodel instanceof \RedBeanPHP\OODBBean) {
                $artists = $model->getArtists(['abjad_id' => $abmodel->id, 'has_chord' => true]);
            }

            return $this->view->render($response, 'song_chord_index.phtml', [
                'model' => $model,
                'selected_abjad' => strtoupper($args['artist']),
                'artists' => $artists
            ]);
        }
    } else {
        $data = $model->getSong($args['title']);
        if (empty($data)) {
            // check by title
            $data = $model->getSongByTitle(strtolower($args['title']), $args['artist']);

            if (!empty($data['chord_permalink']))
                return $response->withRedirect('/kord/'.$args['artist'].'/'.$data['chord_permalink']);
            else
                return $response->withRedirect('/kord/'.$args['artist'].'/'.$data['chord_slug']);
        } else {
            if (!empty($data['chord_permalink']) && $data['chord_permalink'] != $args['title']) {
                return $response->withRedirect('/kord/'.$args['artist'].'/'.$data['chord_permalink']);
            }
        }

        return $this->view->render($response, 'song_chord.phtml', [
            'data' => $data,
            'msong' => $model
        ]);
    }

    return $this->view->render($response, '404.phtml');
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
    $this->group('/chords', function() use ($user) {
        new Extensions\Controllers\ChordsController($this, $user);
    });
});

?>
