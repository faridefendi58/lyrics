<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class ChordsController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/create', [$this, 'create']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
        $app->map(['POST'], '/get-slug', [$this, 'get_slug']);
        $app->map(['POST'], '/upload-images', [$this, 'get_upload_images']);
        $app->map(['GET', 'POST'], '/direct-upload', [$this, 'get_direct_upload']);
        $app->map(['GET'], '/scraping-task', [$this, 'scraping_task']);
        $app->map(['GET', 'POST'], '/scrap/[{id}]', [$this, 'scrap']);
        $app->map(['GET', 'POST'], '/generate-artist', [$this, 'generate_artist']);
        $app->map(['GET', 'POST'], '/generate-song', [$this, 'generate_song']);
        $app->map(['GET', 'POST'], '/generate-lyric', [$this, 'generate_lyric']);
        $app->map(['POST'], '/delete-artist/[{id}]', [$this, 'delete_artist']);
        $app->map(['POST'], '/delete-song/[{id}]', [$this, 'delete_song']);
        $app->map(['GET', 'POST'], '/scraping-job/[{limit}]', [$this, 'scraping_job']);
        $app->map(['GET', 'POST'], '/quick-scrap', [$this, 'quick_scrap']);
        $app->map(['GET', 'POST'], '/list-view/[{approved}]', [$this, 'list_view']);
        $app->map(['GET'], '/generate-cache', [$this, 'generate_cache']);
        $app->map(['GET', 'POST'], '/import-json', [$this, 'import_json']);
        $app->map(['GET', 'POST'], '/recovery', [$this, 'recovery']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'create', 'update', 'delete',
                    'generate-song', 'generate-artist', 'delete-artist', 'delete-song',
                    'quick-scrap', 'import-json', 'recovery'],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel();
        $need_approvals = $model->getSongs([
            'status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED]);
        $approveds = $model->getSongs([
            'status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_APPROVED]);

        return $this->_container->module->render($response, 'songs/view_chord.html', [
            'need_approvals' => $need_approvals,
            'approveds' => $approveds,
            'model' => $model
        ]);
    }

    public function create($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel('create');
        $samodel = new \ExtensionsModel\SongArtistModel();
        $artists = $samodel->getArtistsWithAbjad();
        $genres = \ExtensionsModel\SongGenreModel::model()->findAll();
        $song_id = 0;

        if (isset($_POST['Songs'])){
            $model->title = $_POST['Songs']['title'];
            $model->slug = $_POST['Songs']['slug'];
            $model->artist_id = $_POST['Songs']['artist_id'];
            $model->genre_id = $_POST['Songs']['genre_id'];
            if (isset($_POST['Songs']['album_id'])) {
                $model->album_id = $_POST['Songs']['album_id'];
            }
            if (!empty($_POST['Songs']['story'])) {
                $model->story = $_POST['Songs']['story'];
            }
            $model->status = \ExtensionsModel\SongModel::STATUS_DRAFT;
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            $create = \ExtensionsModel\SongModel::model()->save(@$model);
            if ($create > 0) {
                $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                $model2->song_id = $model->id;
                $model2->url = $_POST['Songs']['refference_url'];
                $model2->section = $_POST['Songs']['refference_section'];

                if (empty($model2->url))
                    $model2->url = '#';
                $model2->section = $_POST['Songs']['refference_section'];
                if (empty($model2->section))
                    $model2->section = '#';

                if (isset($_POST['Songs']['permalink']) && !empty($_POST['Songs']['permalink'])) {
                    $model2->permalink = $_POST['Songs']['permalink'];
                }

                if (isset($_POST['Songs']['content']) && !empty($_POST['Songs']['content'])) {
                    $model2->result = $_POST['Songs']['content'];
                }
                if (!empty($_POST['Songs']['meta_title']))
                    $model2->meta_title = $_POST['Songs']['meta_title'];
                if (!empty($_POST['Songs']['meta_keyword']))
                    $model2->meta_keyword = $_POST['Songs']['meta_keyword'];
                if (!empty($_POST['Songs']['meta_description']))
                    $model2->meta_description = $_POST['Songs']['meta_description'];
                $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_PENDING;
                $model2->created_at = date('Y-m-d H:i:s');
                $model2->updated_at = date('Y-m-d H:i:s');
                $create2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);

                $message = 'Your chord is successfully created.';
                $success = true;
                $song_id = $model->id;
            } else {
                $message = 'Failed to create new chord.';
                $success = false;
            }
        }

        return $this->_container->module->render($response, 'songs/create_chord.html', [
            'status_list' => $model->getListStatus(),
            'model' => $model,
            'artists' => $artists,
            'genres' => $genres,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'song_id' => $song_id
        ]);
    }

    public function update($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $model = \ExtensionsModel\SongModel::model()->findByPk($args['id']);
        $smodel = new \ExtensionsModel\SongModel();
        $samodel = new \ExtensionsModel\SongArtistModel();
        $artists = $samodel->getArtistsWithAbjad();
        $genres = \ExtensionsModel\SongGenreModel::model()->findAll();
        $song_detail = $smodel->getSongDetail($args['id']);

        if (isset($_POST['Songs'])){
            $model->title = $_POST['Songs']['title'];
            $slug = $_POST['Songs']['slug'];
            if ($_POST['Songs']['slug'] != $model->slug) {
                $cek_slug = \ExtensionsModel\SongModel::model()->findByAttributes(['slug' => $_POST['Songs']['slug']]);
                if ($cek_slug instanceof \RedBeanPHP\OODBBean) {
                    $model->slug = $_POST['Songs']['slug'].'2';
                }
            }

            $model->slug = $slug;
            $model->artist_id = $_POST['Songs']['artist_id'];
            $model->genre_id = $_POST['Songs']['genre_id'];
            if (isset($_POST['Songs']['album_id'])) {
                $model->album_id = $_POST['Songs']['album_id'];
            }
            if (!empty($_POST['Songs']['story'])) {
                $model->story = $_POST['Songs']['story'];
            }
            $model->status = $_POST['Songs']['status'];
            if ($model->status == \ExtensionsModel\SongModel::STATUS_PUBLISHED && empty($model->published_at)) {
                $model->published_at = date('Y-m-d H:i:s');
            }

            $model->updated_at = date('Y-m-d H:i:s');
            $update = \ExtensionsModel\SongModel::model()->update($model);
            if ($update) {
                $model2 = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $model->id]);
                $is_new_record = false;
                if (!$model2 instanceof \RedBeanPHP\OODBBean) {
                    $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                    $is_new_record = true;
                }
                $model2->url = $_POST['Songs']['refference_url'];
                $model2->section = $_POST['Songs']['refference_section'];

                if (empty($model2->url))
                    $model2->url = '#';
                $model2->section = $_POST['Songs']['refference_section'];
                if (empty($model2->section))
                    $model2->section = '#';

                if (isset($_POST['Songs']['permalink']) && !empty($_POST['Songs']['permalink'])) {
                    $model2->permalink = $_POST['Songs']['permalink'];
                }

                if (isset($_POST['Songs']['content']) && !empty($_POST['Songs']['content'])) {
                    $model2->result = $_POST['Songs']['content'];
                }

                if (!empty($_POST['Songs']['meta_title']))
                    $model2->meta_title = $_POST['Songs']['meta_title'];
                if (!empty($_POST['Songs']['meta_keyword']))
                    $model2->meta_keyword = $_POST['Songs']['meta_keyword'];
                if (!empty($_POST['Songs']['meta_description']))
                    $model2->meta_description = $_POST['Songs']['meta_description'];

                if ($model->status == \ExtensionsModel\SongModel::STATUS_PUBLISHED) {
                    $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_APPROVED;
                    $model2->approved_at = date("Y-m-d H:i:s");
                }

                $model2->featured = (int)$_POST['Songs']['featured'];
                $model2->top_track = (int)$_POST['Songs']['top_track'];
                $model2->tags = $_POST['Songs']['tags'];

                $model2->updated_at = date('Y-m-d H:i:s');
                if ($is_new_record) {
                    $model2->song_id = $model->id;
                    $model2->created_at = date('Y-m-d H:i:s');
                    $create2 = \ExtensionsModel\SongCordRefferenceModel::model()->save($model2);
                } else {
                    $update2 = \ExtensionsModel\SongCordRefferenceModel::model()->update($model2);
                }

                if ($update2) {
                    // also update the media if any
                    $model3 = \ExtensionsModel\SongMediaRefferenceModel::model()->findByAttributes(['song_id' => $model->id]);
                    if (isset($_POST['Songs']['mp3_url']) && !empty($_POST['Songs']['mp3_url'])) {
                        if (!$model3 instanceof \RedBeanPHP\OODBBean) {
                            $model3 = new \ExtensionsModel\SongMediaRefferenceModel('create');
                            $model3->mp3_url = $_POST['Songs']['mp3_url'];
                            if (!empty($_POST['Songs']['video_url'])) {
                                $model3->video_url = $_POST['Songs']['video_url'];
                            }
                            $model3->song_id = $model->id;
                            $model3->status = \ExtensionsModel\SongMediaRefferenceModel::STATUS_ENABLED;
                            $model3->created_at = date('Y-m-d H:i:s');
                            $model3->updated_at = date('Y-m-d H:i:s');
                            $save3 = \ExtensionsModel\SongMediaRefferenceModel::model()->save($model3);
                        } else {
                            $model3->mp3_url = $_POST['Songs']['mp3_url'];
                            if (!empty($_POST['Songs']['video_url'])) {
                                $model3->video_url = $_POST['Songs']['video_url'];
                            }
                            $model3->status = \ExtensionsModel\SongMediaRefferenceModel::STATUS_ENABLED;
                            $model3->updated_at = date('Y-m-d H:i:s');
                            $save3 = \ExtensionsModel\SongMediaRefferenceModel::model()->update($model3);
                        }
                    }
                    $song_detail = $smodel->getSongDetail($model->id);
                    $message = 'Your chord is successfully updated.';
                    $success = true;

                    // update the cache
                    try {
                        $hook_params = $smodel->getSong($model2->permalink);
                        $this->onAfterChordSaved($hook_params);
                    } catch (\Exception $e){}
                }
            } else {
                $message = 'Failed to update your chord.';
                $success = false;
            }
        }

        return $this->_container->module->render($response, 'songs/update_chord.html', [
            'status_list' => $smodel->getListStatus(),
            'artists' => $artists,
            'genres' => $genres,
            'song' => $song_detail,
            'model' => $smodel,
            'message' => ($message) ? $message : null,
            'success' => $success,
        ]);
    }

    public function delete($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $model = \ExtensionsModel\SongModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\SongModel::model()->delete($model);
        if ($delete) {
            $delete2 = \ExtensionsModel\SongLyricRefferenceModel::model()->deleteAllByAttributes(['song_id'=>$args['id']]);
            $delete3 = \ExtensionsModel\SongCordRefferenceModel::model()->deleteAllByAttributes(['song_id'=>$args['id']]);
            $message = 'Your chord is successfully deleted.';
            echo true;
        }
    }

    public function get_slug($request, $response, $args)
    {
        if ($this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        if (!isset($_POST['title'])) {
            return false;
        }

        $model = new \ExtensionsModel\PostModel();
        return $model->createSlug($_POST['title']);
    }

    public function scraping_task($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel();
        $songs = $model->getSongs(['status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_PENDING]);

        return $this->_container->module->render($response, 'songs/scraping_task_chord.html', [
            'songs' => $songs
        ]);
    }

    public function scrap($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $result = null;
        $model = \ExtensionsModel\SongModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $result = $this->_scrap_execute(['id' => $args['id']]);
        }

        $ret = [ 'success' => 0 ];
        if (!empty($result)) {
            $lrmodel = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $args['id']]);
            if ($lrmodel instanceof \RedBeanPHP\OODBBean) {
                $lrmodel->result = $result;
                $lrmodel->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                $lrmodel->executed_at = date("Y-m-d H:i:s");
                $lrmodel->updated_at = date("Y-m-d H:i:s");
                $update = \ExtensionsModel\SongCordRefferenceModel::model()->update($lrmodel);
                if ($update) {
                    $ret['success'] = 1;
                }
            }
        }

        return $response->withJson($ret);
    }

    /**
     * Execution procedure
     * @param $id
     */
    private function _scrap_execute($params) {
        if (!isset($params['chord_url']) && !isset($params['chord_section'])) {
            $smodel = new \ExtensionsModel\SongModel();
            $data = $smodel->getSongDetail($params['id']);
            $params['chord_url'] = $data['chord_url'];
            $params['chord_section'] = $data['chord_section'];
        }

        // create HTML DOM
        $html = file_get_html($params['chord_url']);

        $lyrics = '';
        foreach($html->find($params['chord_section']) as $div) {
            $links = $div->find('a');
            foreach ($links as $link) {
                $link->href = "#";
            }
            //remove div if any
            if (strpos($div->innertext, "<div") != false) {
                $divs = $div->find('div');
                foreach ($divs as $div) {
                    $div->outhertext = "";
                }
            }
            $lyrics .= $div->innertext."<br/>";
        }

        /*if (strpos($lyrics, "<div") != false) {
            $lyrics = preg_replace('#(<div.*?>).*?(</div>)#', '$1$2', $lyrics);
        }*/
        $lyrics = preg_replace('#(<h.*?>).*?(</h.*?>)#', '$1$2', $lyrics);
        if (strpos($lyrics, "<p") !=false) {
            $lyrics = preg_replace('#(<p.*?>).*?(</p>)#', '$1$2', $lyrics);
        }
        if (strpos($lyrics, "<label") !=false) {
            $lyrics = preg_replace('#(<label.*?>).*?(</label>)#', '$1$2', $lyrics);
        }
        if (strpos($lyrics, "<button") !=false) {
            $lyrics = preg_replace('#(<button.*?>).*?(</button>)#', '$1$2', $lyrics);
        }
        $lyrics = str_replace(['showTip', 'reff'], ['chord', 'Reff'], $lyrics);

        $lyrics = strip_tags($lyrics, '<a><br/><br>');

        $html->clear();
        unset($html);

        return $lyrics;
    }

    public function generate_artist($request, $response, $args) {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $smodel = new \ExtensionsModel\SongModel();
        $amodel = new \ExtensionsModel\SongAbjadModel();
        $samodel = new \ExtensionsModel\SongArtistModel();
        $alphabets = $amodel->getItems();
        $artist_slugs = $samodel->getSlugs();

        $params = $request->getParams();
        $items = [];
        $message = null; $success = false;
        if (isset($params['Artist'])) {
            if ($params['Submit'] == 'Generate') {
                $html = file_get_html($params['Artist']['url']);

                foreach($html->find($params['Artist']['section']) as $i => $div) {
                    $name = $div->plaintext;
                    array_push($items, ['name' => $name, 'url' => $div->href]);
                }
                $html->clear();
                unset($html);
            } else {
                if (is_array($params['choose'])) {
                    $counter = 0;
                    foreach ($params['choose'] as $i => $on) {
                        $model = new \ExtensionsModel\SongArtistModel('create');
                        $model->name = $params['Artist']['name'][$i];
                        $model->slug = $smodel->createSlug($model->name);
                        if (!in_array($model->slug, $artist_slugs)) {
                            $model->chord_url = $params['Artist']['song_url'][$i];
                            $model->chord_section = $params['Artist']['song_section'];
                            $model->abjad_id = array_search (substr(ucwords($model->name), 0, 1), $alphabets);
                            $model->created_at = date("Y-m-d H:i:s");
                            $model->updated_at = date("Y-m-d H:i:s");
                            $song_artist = \ExtensionsModel\SongArtistModel::model();
                            $save = $song_artist->save($model);
                            if ($save > 0) {
                                $counter = $counter + 1;
                            }
                        }
                    }
                    if ($counter > 0) {
                        $message = $counter.' daftar artis telah berhasil disimpan.';
                        $success = true;
                    }
                }
            }
        }

        return $this->_container->module->render($response, 'songs/generate_artist_chord.html', [
            'params' => $params['Artist'],
            'items' => $items,
            'artists' => $samodel->getGenerateResults(),
            'message' => ($message) ? $message : null,
            'success' => $success,
        ]);
    }

    public function delete_artist($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $ret = ['success' => 0];
        $model = \ExtensionsModel\SongArtistModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $song = \ExtensionsModel\SongModel::model()->findByAttributes(['artist_id' => $model->id]);
            if (!$song instanceof \RedBeanPHP\OODBBean) { //hanya yang belum memiliki lagu
                $delete = \ExtensionsModel\SongArtistModel::model()->delete($model);
                if ($delete) {
                    $ret['message'] = 'Your artist is successfully deleted.';
                    $ret['success'] = 1;
                }
            } else {
                $ret['message'] = 'Unable to delete due to this artist has song.';
                $ret['success'] = 0;
            }
        }

        return $response->withJson($ret);
    }

    public function generate_song($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $smodel = new \ExtensionsModel\SongModel();
        $samodel = new \ExtensionsModel\SongArtistModel();
        $song_slugs = $smodel->getSlugs();

        $params = $request->getParams();
        $items = [];
        $message = null; $success = false;
        if (isset($params['Songs'])) {
            if ($params['Submit'] == 'Generate') {
                $html = file_get_html($params['Songs']['src_website']);
                foreach($html->find($params['Songs']['section']) as $i => $div) {
                    $title = $div->plaintext;
                    if (isset($params['Songs']['use_slug_as_title'])
                        && $params['Songs']['use_slug_as_title'] == 'on') {
                        $pecah = explode("/", $div->href);
                        if (count($pecah) > 0) {
                            $last_char = $pecah[count($pecah) - 1];
                            if (strpos($last_char, ".")) {
                                $last_char = explode(".", $last_char)[0];
                            }
                            $title = $last_char;
                            if (strpos($title, "-")) {
                                $title = str_replace("-"," ", $title);
                            }
                            $title = ucwords(preg_replace("/[^[:alnum:][:space:]]/u", '', $title));
                        }
                    }
                    if (isset($params['Songs']['filter_content'])) {
                        if (strpos(strtolower($title), strtolower($params['Songs']['filter_content'])) != false
                            || strpos(strtolower($div->href), strtolower($params['Songs']['filter_content'])) != false) {
                            array_push($items, ['title' => $title, 'url' => $div->href]);
                        }
                    } else {
                        array_push($items, ['title' => $title, 'url' => $div->href]);
                    }
                }
                $html->clear();
                unset($html);
            } else {
                if (is_array($params['choose'])) {
                    $counter = 0;
                    foreach ($params['choose'] as $i => $on) {
                        $model = new \ExtensionsModel\SongModel('create');
                        $model->title = $params['Songs']['title'][$i];
                        $model->slug = $smodel->createSlug($model->title);
                        $model->artist_id = $params['Songs']['artist_id'];
                        if (!in_array($model->slug, $song_slugs)) {
                            $model->status = \ExtensionsModel\SongModel::STATUS_DRAFT;
                            $model->created_at = date("Y-m-d H:i:s");
                            $model->updated_at = date("Y-m-d H:i:s");
                            $song = \ExtensionsModel\SongModel::model();
                            $save = $song->save(@$model);
                            if ($save > 0) {
                                $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                                $model2->song_id = $model->id;
                                $model2->url = $params['Songs']['song_url'][$i];
                                $model2->section = $params['Songs']['chord_section'];
                                $model2->created_at = date("Y-m-d H:i:s");
                                $model2->updated_at = date("Y-m-d H:i:s");
                                $save2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);
                                if ($save2 > 0) {
                                    $model3 = \ExtensionsModel\SongArtistModel::model()->findByPk($model->artist_id);
                                    if ($model3 instanceof \RedBeanPHP\OODBBean) {
                                        $model3->chord_url = $params['Songs']['src_website'];
                                        $model3->chord_section = $params['Songs']['section'];
                                        $model3->updated_at = date("Y-m-d H:i:s");
                                        $update3 = \ExtensionsModel\SongArtistModel::model()->update($model3);
                                    }
                                    $counter = $counter + 1;
                                }
                            }
                        } else {
                            $slug = $smodel->createSlug($params['Songs']['title'][$i]);
                            $model = \ExtensionsModel\SongModel::model()->findByAttributes(['slug' => $slug]);
                            if ($model instanceof \RedBeanPHP\OODBBean) {
                                $model2 = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $model->id]);
                                if (!$model2 instanceof \RedBeanPHP\OODBBean) {
                                    $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                                    $model2->song_id = $model->id;
                                    $model2->url = $params['Songs']['song_url'][$i];
                                    $model2->section = $params['Songs']['chord_section'];
                                    $model2->created_at = date("Y-m-d H:i:s");
                                    $model2->updated_at = date("Y-m-d H:i:s");
                                    $save2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);
                                    if ($save2 > 0)
                                        $counter = $counter + 1;
                                }
                            }
                        }
                    }
                    if ($counter > 0) {
                        $message = $counter.' daftar lagu telah berhasil disimpan.';
                        $success = true;
                    }
                }
            }
        }

        $song_artist_params = [ 'abjad_id' => 1 ];
        if (isset($params['Songs']['abjad_id'])) {
            $song_artist_params['abjad_id'] = $params['Songs']['abjad_id'];
        }

        return $this->_container->module->render($response, 'songs/generate_song_chord.html', [
            'params' => $params['Songs'],
            'items' => $items,
            'artists' => $samodel->getGenerateResults($song_artist_params),
            'songs' => $smodel->getGenerateResults(),
            'smodel' => $smodel,
            'use_for_chord' => true,
            'message' => ($message) ? $message : null,
            'success' => $success,
        ]);
    }

    public function delete_song($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $ret = ['success' => 0];
        $model = \ExtensionsModel\SongModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean
            && $model->status == \ExtensionsModel\SongModel::STATUS_DRAFT) {
            $lyric = \ExtensionsModel\SongLyricRefferenceModel::model()->findByAttributes(['song_id' => $model->id]);
            if ($lyric instanceof \RedBeanPHP\OODBBean
                && $lyric->status == \ExtensionsModel\SongLyricRefferenceModel::STATUS_PENDING) {
                $delete = \ExtensionsModel\SongModel::model()->delete($model);
                if ($delete) {
                    $delete2 = \ExtensionsModel\SongLyricRefferenceModel::model()->delete($lyric);
                    $ret['message'] = 'Your song is successfully deleted.';
                    $ret['success'] = 1;
                }
            } else {
                $ret['message'] = 'Unable to delete the song. Just the draft song whom can be deleted.';
                $ret['success'] = 0;
            }
        }

        return $response->withJson($ret);
    }

    public function scraping_job($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['limit'])) {
            return false;
        }

        $smodel = new \ExtensionsModel\SongModel();
        $items = $smodel->getSongs([
            'limit' => $args['limit'],
            'status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_PENDING,
            'status' => \ExtensionsModel\SongModel::STATUS_DRAFT
        ]);

        $result = null; $success = [];
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                $params = [
                    'chord_url' => $item['chord_src_url'],
                    'chord_section' => $item['chord_section']
                ];
                $result = $this->_scrap_execute($params);
                if (!empty($result)) {
                    $lrmodel = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $item['id']]);
                    if ($lrmodel instanceof \RedBeanPHP\OODBBean) {
                        $lrmodel->result = $result;
                        $lrmodel->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                        $lrmodel->executed_at = date("Y-m-d H:i:s");
                        $lrmodel->updated_at = date("Y-m-d H:i:s");
                        $update = \ExtensionsModel\SongCordRefferenceModel::model()->update($lrmodel);
                        if ($update) {
                            array_push($success, $item['id']);
                        }
                    }
                }
            }
        }

        return $response->withJson($success);
    }

    public function generate_lyric($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $params = $request->getParams();
        $model = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $params['song_id']]);
        $results = ['status' => 0];
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $slmodel = \ExtensionsModel\SongLyricRefferenceModel::model()->findByAttributes(['song_id' => $params['song_id']]);
            if (!$slmodel instanceof \RedBeanPHP\OODBBean) {
                $lmodel = new \ExtensionsModel\SongLyricRefferenceModel('create');
                $lmodel->song_id = $params['song_id'];
                $lmodel->url = $model->url;
                $lmodel->section = $model->section;
                if (strpos($model->result, "<a") !=false) {
                    $model->result = preg_replace('#(<a.*?>).*?(</a>)#', '$1$2', $model->result);
                    $model->result = str_replace("&nbsp;"," ", $model->result);
                }
                $lmodel->result = html_entity_decode(strip_tags($model->result, '<p><br/><br>'));
                $lmodel->status = $model->status;
                $lmodel->executed_at = date("Y-m-d H:i:s");
                $lmodel->created_at = date("Y-m-d H:i:s");
                $lmodel->updated_at = date("Y-m-d H:i:s");
                $save = \ExtensionsModel\SongLyricRefferenceModel::model()->save(@$lmodel);
                if ($save > 0) {
                    $results['status'] = 1;
                    $results['message'] = "The lyrics has been successfully generated.";
                } else {
                    $results['message'] = \Model\SongLyricRefferenceModel::model()->getErrors(false);;
                }
            }
        }

        return $response->withJson($results);;
    }

    public function quick_scrap($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $smodel = new \ExtensionsModel\SongModel();
        $samodel = new \ExtensionsModel\SongArtistModel();

        $params = $request->getParams();

        $song_artist_params = [ 'abjad_id' => 1 ];
        if (isset($params['Songs']['abjad_id'])) {
            $song_artist_params['abjad_id'] = $params['Songs']['abjad_id'];
        }

        $success =  false;
        $message = false;
        $chord_content = "";
        if (isset($params['Songs'])) {
            if ($params['Submit'] == 'Generate') {
                try {
                    $html = file_get_html($params['Songs']['src_website']);
                } catch (\Exception $e) {
                    var_dump($e->getMessage()); exit;
                }

                if (strpos($params['Songs']['src_website'], 'rdtela.co') !== false) {
                    $chord_section = $html->find($params['Songs']['chord_section'], 0);

                    if (strpos($chord_section->outertext, "<meta") != false) {
                        $metas = $chord_section->find('meta');
                        foreach ($metas as $meta) {
                            $meta->outertext = "";
                        }
                    }

                    if (strpos($chord_section->outertext, "<span") != false) {
                        $divs = $chord_section->find('span');
                        foreach ($divs as $div) {
                            $div->outertext = "";
                        }
                    }

                    foreach ($chord_section->find('a.tbi-tooltip') as $it => $tooltip) {
                        $tooltip->outertext = $tooltip->innertext;
                    }

                    $chord_content = $chord_section->innertext;
                } elseif (strpos($params['Songs']['src_website'], 'rdindonesia.co') !== false) {
                    $chord_section = $html->find($params['Songs']['chord_section'], 0);
                    if (strpos($chord_section->outertext, "<h5") != false) {
                        $metas = $chord_section->find('h5');
                        foreach ($metas as $meta) {
                            $meta->outertext = "";
                        }
                    }

                    foreach ($chord_section->find('a.showTip') as $it => $tooltip) {
                        $tooltip->outertext = $tooltip->innertext;
                    }

                    $chord_content = $chord_section->innertext;
                } elseif (strpos($params['Songs']['src_website'], 'ordvisa.co') !== false) {
                    $chord_section = $html->find($params['Songs']['chord_section'], 0);

                    $chord_content = $chord_section->innertext;
                    $chord_content = str_replace("&nbsp; &nbsp;", "&nbsp;", $chord_content);
                } elseif (strpos($params['Songs']['src_website'], 'ords-and-tab') !== false) {
                    $chord_section = $html->find($params['Songs']['chord_section'], 0);

                    if (strpos($chord_section->outertext, "<sup") != false) {
                        $divs = $chord_section->find('sup');
                        foreach ($divs as $div) {
                            $div->outertext = "[". $div->innertext ."]";
                        }
                    }

                    if (strpos($chord_section->outertext, "<b") != false) {
                        $divs = $chord_section->find('b');
                        foreach ($divs as $div) {
                            $div->outertext = "  {". $div->innertext ."}";
                        }
                    }

                    foreach ($chord_section->find('div.song') as $is => $dsong) {
                        $dsong->outertext = $dsong->innertext;
                    }

                    $chord_content = $chord_section->innertext;
                    $chord_content = str_replace("[intro]", "<b>Intro :</b>  ", $chord_content);
                    $chord_content = str_replace("[chorus]", "<b>Reff :</b>  ", $chord_content);
                } else {
                    $chord_section = $html->find($params['Songs']['chord_section'], 0);
                    $chord_content = $chord_section->innertext;
                }
                $html->clear();
                unset($html);
            } else {
                if (!empty($params['Songs']['content']) && !empty($params['Songs']['title'])) {
                    $artist_id = $params['Songs']['artist_id'];
                    // save the artist name first if any
                    if (!empty($params['Songs']['artist_name'])) {
                        $adata = $samodel->getArtistByName($params['Songs']['artist_name']);
                        if (is_array($adata)) {
                            $artist_id = $adata['id'];
                        } else {
                            $artist_slugs = $samodel->getSlugs();
                            $amodel = new \ExtensionsModel\SongAbjadModel();
                            $alphabets = $amodel->getItems();

                            $amodel = new \ExtensionsModel\SongArtistModel('create');
                            $amodel->name = $params['Songs']['artist_name'];
                            $amodel->slug = $smodel->createSlug($amodel->name);
                            if (!in_array($amodel->slug, $artist_slugs)) {
                                $amodel->chord_url = $params['Songs']['src_website'];
                                $amodel->chord_section = $params['Songs']['chord_section'];
                                $amodel->abjad_id = array_search (substr(ucwords($amodel->name), 0, 1), $alphabets);
                                $amodel->created_at = date("Y-m-d H:i:s");
                                $amodel->updated_at = date("Y-m-d H:i:s");
                                $song_artist = \ExtensionsModel\SongArtistModel::model();
                                $save = $song_artist->save(@$amodel);
                                if ($save > 0) {
                                    $artist_id = $amodel->id;
                                }
                            }
                        }
                    }
                    // saving the song
                    $model = new \ExtensionsModel\SongModel('create');
                    $model->title = $params['Songs']['title'];
                    $model->slug = $smodel->createSlug($model->title);
                    $model->artist_id = $artist_id;
                    $song_slugs = $smodel->getSlugs();
                    if (!in_array($model->slug, $song_slugs)) {
                        $model->status = \ExtensionsModel\SongModel::STATUS_DRAFT;
                        $model->created_at = date("Y-m-d H:i:s");
                        $model->updated_at = date("Y-m-d H:i:s");
                        $song = \ExtensionsModel\SongModel::model();
                        $save = $song->save(@$model);
                        if ($save > 0) {
                            $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                            $model2->song_id = $model->id;
                            $model2->url = $params['Songs']['src_website'];
                            $model2->section = $params['Songs']['chord_section'];
                            $model2->result = $params['Songs']['content'];
                            $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                            $model2->executed_at = date("Y-m-d H:i:s");
                            $model2->created_at = date("Y-m-d H:i:s");
                            $model2->updated_at = date("Y-m-d H:i:s");
                            $save2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);
                            if ($save2 > 0) {
                                $message = 'Data telah berhasil disimpan.';
                                $success = true;
                            }
                        }
                    } else {
                        $slug = $smodel->createSlug($params['Songs']['title']);
                        $model = \ExtensionsModel\SongModel::model()->findByAttributes(['slug' => $slug]);
                        if ($model instanceof \RedBeanPHP\OODBBean) {
                            $model2 = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => $model->id]);
                            if (!$model2 instanceof \RedBeanPHP\OODBBean) {
                                $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                                $model2->song_id = $model->id;
                                $model2->url = $params['Songs']['song_url'];
                                $model2->section = $params['Songs']['chord_section'];
                                $model2->result = $params['Songs']['content'];
                                $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                                $model2->executed_at = date("Y-m-d H:i:s");
                                $model2->created_at = date("Y-m-d H:i:s");
                                $model2->updated_at = date("Y-m-d H:i:s");
                                $save2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);
                                if ($save2 > 0) {
                                    $message = 'Data telah berhasil disimpan.';
                                    $success = true;
                                }
                            } else {
                                $model2->result = $params['Songs']['content'];
                                $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                                $model2->executed_at = date("Y-m-d H:i:s");
                                $model2->updated_at = date("Y-m-d H:i:s");
                                $update2 = \ExtensionsModel\SongCordRefferenceModel::model()->update($model2);
                                if ($update2) {
                                    $message = 'Data telah berhasil disimpan.';
                                    $success = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->_container->module->render($response, 'songs/quick_scrap.html', [
            'params' => $params['Songs'],
            'message' => ($message) ? $message : null,
            'success' => $success,
            'smodel' => $smodel,
            'artists' => $samodel->getGenerateResults($song_artist_params),
            'songs' => $smodel->getGenerateResults(),
            'chord_content' => $chord_content,
        ]);
    }

    public function list_view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel();
        if ($args['approved'] == 1) {
            $items = $model->getSongs([
                'status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_APPROVED]);
        } else {
            $items = $model->getSongs([
                'status_chord' => \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED]);
        }

        return $response->withJson($items, 201);
    }

    public function generate_cache($request, $response, $args)
    {
        $params = $request->getParams();
        $data = [];
        if (is_array($params) && count($params) > 0 && array_key_exists('cached_name', $params)) {
            $song = new \ExtensionsModel\SongModel();
            $data = $song->getSongs($params);

            $file = 'protected/data/'. $params['cached_name'] .'.json';
            if(!file_exists($file)) {
                $new_file = fopen($file, "w");
            }
            file_put_contents($file, json_encode($data));

            echo count($data); exit;
        }

        return false;
    }

    private function onAfterChordSaved($hook_params = []) {
        $jobs = [
            'chord_latest' => [
                "status" => "published",
                "order_by" => "published_at",
                "limit" => 20,
                "type" => "chord",
                "top_track" => 1,
                "cached_name" => "chord_latest"
            ],
            'chord_featured' => [
                "status" => "published",
                "order_by" => "published_at",
                "limit" => 6,
                "type" => "chord",
                "featured" => 1,
                "cached_name" => "chord_featured"
            ],
            'chord_recommended' => [
                "status" => "published",
                "limit" => 10,
                "type" => "chord",
                "tag" => "recommended",
                "cached_name" => "chord_recommended"
            ],
            'chord_mostly_played' => [
                "status" => "published",
                "limit" => 10,
                "type" => "chord",
                "order_by" => "c.viewed",
                "cached_name" => "chord_mostly_played"
            ]
        ];

        $success = 0;
        foreach ($jobs as $i => $job) {
            $qry = http_build_query($job);
            $url = $this->_settings['params']['site_url'] .'/song/chords/generate-cache?'. $qry;
			if (function_exists('curl_version')) {
		        $ch = curl_init();

		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		        curl_setopt($ch, CURLOPT_URL, $url);

		        if(curl_exec($ch) === false) {
		            echo 'Curl error: ' . curl_error($ch);
		        } else {
		            $success = $success + 1;
		        }
		        curl_close($ch);
			} else {
				$html = file_get_html($url);
				$html->clear();
                unset($html);
				$success = $success + 1;
			}
        }

        // also update cached song
        if (array_key_exists('artist_slug', $hook_params) && array_key_exists('chord_permalink', $hook_params)) {
            $dir = 'protected/data/songs/';
            $file = $dir. $hook_params['artist_slug'].'_'.$hook_params['chord_permalink'].'.json';
            if(!file_exists($file)) {
                $new_file = fopen($file, "w");
                file_put_contents($file, json_encode($hook_params));
            } else {
                file_put_contents($file, json_encode($hook_params));
            }
        }

        return $success;
    }

    public function import_json($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel();
        $samodel = new \ExtensionsModel\SongArtistModel();

        $params = $request->getParams();

        $items = [];
        $message = null; $success = false;
        $failed_to_save = [];
        if (isset($params['Songs'])) {
            if ($params['Submit'] == 'Generate') {
                if (isset($_FILES['Songs'])) {
                    if ($_FILES['Songs']['type']['json_file'] == 'application/json') {
                        $file = file_get_contents($_FILES['Songs']['tmp_name']['json_file']);
                        $results = json_decode($file, true);
                        if (is_array($results) && array_key_exists('data', $results[2])) {
                            $items = $results[2]['data'];
                            $_SESSION['json_entry'] = $items;
                        }
                    }
                }
            } else {
                if (is_array($params['choose'])) {
                    $items = $_SESSION['json_entry'];
                    if (isset($params['check_all'])) {
                        $params['choose'] =  array_keys($items);//range(0, count($items) - 1);
                    }

                    foreach ($params['choose'] as $i => $on) {
                        if (!empty($params['Songs']['artist_name'])) {
                            $adata = $samodel->getArtistByName($params['Songs']['artist_name'][$i]);
                            $artist_name = ucwords(strtolower($params['Songs']['artist_name'][$i]));
                            if (is_array($adata)) {
                                $artist_id = $adata['id'];
                                $artist_name = $adata['name'];
                            } else {
                                $artist_slugs = $samodel->getSlugs();
                                $abmodel = new \ExtensionsModel\SongAbjadModel();
                                $alphabets = $abmodel->getItems();

                                $amodel = new \ExtensionsModel\SongArtistModel('create');
                                $amodel->name = $artist_name;
                                $amodel->slug = $model->createSlug($amodel->name);
                                if (!in_array($amodel->slug, $artist_slugs)) {
                                    $amodel->chord_url = '#';
                                    $amodel->chord_section = '#';
                                    $amodel->abjad_id = array_search(substr(ucwords($amodel->name), 0, 1), $alphabets);
                                    $amodel->created_at = date("Y-m-d H:i:s");
                                    $amodel->updated_at = date("Y-m-d H:i:s");
                                    $song_artist = \ExtensionsModel\SongArtistModel::model();
                                    $save = $song_artist->save(@$amodel);
                                    if ($save > 0) {
                                        $artist_id = $amodel->id;
                                    }
                                } else {
                                    $a_data = \ExtensionsModel\SongArtistModel::model()->findByAttributes(['slug' => $amodel->slug]);
                                    if ($a_data instanceof \RedBeanPHP\OODBBean) {
                                        $artist_id = $a_data->id;
                                    }
                                }
                            }

                            if ($artist_id > 0) {
                                // saving the song
                                $model1 = new \ExtensionsModel\SongModel('create');
                                $model1->title = ucwords(strtolower($params['Songs']['song_title'][$i]));
                                if (strpos($model1->title, " - ") !== false) {
                                    if (strpos($model1->title, $artist_name) !== false) {
                                        $model1->title = str_replace($artist_name,"",$model1->title);
                                    }
                                    $model1->title = str_replace(" - ","",$model1->title);
                                }
                                $model1->slug = $model->createSlug($model1->title);
                                $model1->artist_id = $artist_id;
                                $song_slugs = $model1->getSlugs();
                                $create_new_song = true;
                                if (in_array($model1->slug, $song_slugs)) {
                                    $model1->slug = $model->createSlug($params['Songs']['artist_name'][$i].' '.$model1->title);
                                    if (in_array($model1->slug, $song_slugs)) { //if any in database, skip it
                                        $create_new_song = false;
                                        array_push($failed_to_save, $model1->title);
                                    }
                                }

                                if ($create_new_song) {
                                    $model1->status = \ExtensionsModel\SongModel::STATUS_DRAFT;
                                    $model1->created_at = date("Y-m-d H:i:s");
                                    $model1->updated_at = date("Y-m-d H:i:s");
                                    $song = \ExtensionsModel\SongModel::model();
                                    $save = $song->save(@$model1);
                                    if ($save > 0) {
                                        $model2 = new \ExtensionsModel\SongCordRefferenceModel('create');
                                        $model2->song_id = $model1->id;
                                        $model2->url = '#';
                                        $model2->section = '#';
                                        $model2->result = $items[$i]['chord'];
                                        /*$meta_title = $model1->title.' '.$params['Songs']['artist_name'][$i].' guitar chord';
                                        $model2->permalink = $model->createSlug($meta_title);
                                        $model2->meta_title = $meta_title;*/
                                        $model2->status = \ExtensionsModel\SongCordRefferenceModel::STATUS_EXECUTED;
                                        $model2->executed_at = date("Y-m-d H:i:s");
                                        $model2->created_at = date("Y-m-d H:i:s");
                                        $model2->updated_at = date("Y-m-d H:i:s");
                                        $save2 = \ExtensionsModel\SongCordRefferenceModel::model()->save(@$model2);
                                        if ($save2 > 0) {
                                            $message = 'Data telah berhasil disimpan.';
                                            $success = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->_container->module->render($response, 'songs/import_json.html', [
            'model' => $model,
            'items' => $items,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'failed_data' => (count($failed_to_save) > 0)? implode(", ", $failed_to_save) : null
        ]);
    }

    public function recovery($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $smodel = new \ExtensionsModel\SongModel();
        $samodel = new \ExtensionsModel\SongArtistModel();
        $artist_slugs = $samodel->getSlugs();

        $slugs = $smodel->getSlugs();
        $items = [];
        $artists = [];
        $chords = [];
        foreach(glob( $this->_settings['basePath']. '/data/songs/*.json') as $jfile) {
            $pathinfo = pathinfo($jfile);
            $content = file_get_contents($jfile);
            if (!empty($content)) {
                $json_data = json_decode($content, true);
                if ($json_data['id'] == 523) {
                    $scmodel = \ExtensionsModel\SongCordRefferenceModel::model()->findByAttributes(['song_id' => 523]);
                    $scmodel->result = $json_data['chord'];
                    $scmodel->permalink = $json_data['chord_permalink'];
                    $scmodel->meta_title = $json_data['chord_meta_title'];
                    $scmodel->meta_keyword = $json_data['chord_meta_keyword'];
                    $scmodel->meta_description = $json_data['chord_meta_description'];
                    $pdate = \ExtensionsModel\SongCordRefferenceModel::model()->update($scmodel);
                    var_dump($json_data); exit;
                }
                if (is_array($json_data) && array_key_exists('slug', $json_data) && !in_array($json_data['slug'], $slugs)) {
                    $items[] = $json_data;
                    $chords[$json_data['id']] = $json_data;
                    // create artist data
                    $artist = \ExtensionsModel\SongArtistModel::model()->findByPk($json_data['artist_id']);
                    if (!$artist instanceof \RedBeanPHP\OODBBean && !in_array($json_data['artist_slug'], $artist_slugs)) {
                        $artists[$json_data['artist_id']] = [
                            'id' => $json_data['artist_id'],
                            'artist_name' => $json_data['artist_name'],
                            'artist_slug' => $json_data['artist_slug']
                        ];
                    }
                }
            }
        }

        ksort($chords);
        ksort($artists);
        $abmodel = new \ExtensionsModel\SongAbjadModel();
        $alphabets = $abmodel->getItems();
        /*foreach ($artists as $i => $artist) {
            $amodel = new \ExtensionsModel\SongArtistModel();
            $amodel->name = $artist['artist_name'];
            $amodel->slug = $artist['artist_slug'];
            $amodel->abjad_id = array_search (substr(ucwords($amodel->name), 0, 1), $alphabets);
            $amodel->song_url = $i;
            $amodel->created_at = date("Y-m-d H:i:s");
            $amodel->updated_at = date("Y-m-d H:i:s");
            $save = \ExtensionsModel\SongArtistModel::model()->save(@$amodel);
            if ($save) {
                $artists[$i]['saved_id'] = $amodel->id;
            } else {
                $errors = \ExtensionsModel\SongArtistModel::model()->getErrors(true, true);
                var_dump($errors);
            }
        }*/

        //save the song
        /*foreach ($chords as $it => $item) {
            $smodel = new \ExtensionsModel\SongModel();
            //$smodel->id = $item['id'];
            $smodel->title = $item['title'];
            $smodel->slug = $item['slug'];
            $smodel->artist_id = $item['artist_id'];
            $smodel->genre_id = $item['genre_id'];
            $smodel->album_id = $item['album_id'];
            $smodel->story = $item['id']; //$item['story'];
            $smodel->status = $item['status'];
            $smodel->published_at = $item['published_at'];
            $smodel->created_at = $item['created_at'];
            $smodel->updated_at = $item['updated_at'];
            $save2 = \ExtensionsModel\SongModel::model()->save(@$smodel);
            if ($save2) {
                $scmodel = new \ExtensionsModel\SongCordRefferenceModel();
                $scmodel->song_id = $item['id'];
                $scmodel->url = '#';
                $scmodel->section = '#';
                $scmodel->result = $item['chord'];
                $scmodel->permalink = $item['chord_permalink'];
                $scmodel->meta_title = $item['chord_meta_title'];
                $scmodel->meta_keyword = $item['chord_meta_keyword'];
                $scmodel->meta_description = $item['chord_meta_description'];
                $scmodel->featured = 0;
                $scmodel->top_track = 1;
                $scmodel->status = $item['chord_status'];
                $scmodel->executed_at = $item['updated_at'];
                $scmodel->approved_at = $item['updated_at'];
                $scmodel->created_at = $item['created_at'];
                $scmodel->updated_at = $item['updated_at'];
                $save3 = \ExtensionsModel\SongCordRefferenceModel::model()->save($scmodel);
                if ($save3 > 0) {

                }
            }
        }*/

        return $response->withJson(['songs' => $items, 'artits' => $artists], 201);
    }
}
