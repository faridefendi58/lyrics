<?php

namespace PanelAdmin\Controllers;

use Components\BaseController as BaseController;

class DefaultController extends BaseController
{
    protected $_login_url = '/panel-admin/default/login';

    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET', 'POST'], '/login', [$this, 'login']);
        $app->map(['GET'], '/logout', [$this, 'logout']);
        $app->map(['GET', 'POST'], '/change-password', [$this, 'change_password']);
    }

    public function login($request, $response, $args)
    {
        if (!$this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        $r = null;
        if (!empty($_GET['r'])) {
            $r = $_GET['r'];
        }
        $remember = false;

        $need_tfa = false; // 2fa
        if (isset($_POST['LoginForm'])){
            $username = strtolower($_POST['LoginForm']['username']);
            $model = \Model\AdminModel::model()->findByAttributes(['username'=>$username]);
            if ($model instanceof \RedBeanPHP\OODBBean){
                if (!empty($_POST['LoginForm']['auth_code'])) {
                    $tfa_model = \ExtensionsModel\TwofaModel::model()->findByAttributes(['admin_id' => $model->id]);
                    if ($tfa_model instanceof \RedBeanPHP\OODBBean) {
                        $ga = new \ExtensionsComponents\PHPGangsta_GoogleAuthenticator();
                        if ($ga->verifyCode($tfa_model->secret_code, $_POST['LoginForm']['auth_code'])) {
                            $login = $this->_user->login($model, $_POST['LoginForm']['remember']);
                            if ($login){
                                setcookie( 'auth_code', $_POST['LoginForm']['auth_code'], strtotime("+1 week", time()), "/");
                                if (!empty($_POST['LoginForm']['r'])) {
                                    return $response->withRedirect($_POST['LoginForm']['r']);
                                } else {
                                    return $response->withRedirect('/panel-admin');
                                }
                            }
                        } else {
                            $args['error']['message'] = 'Kode Autentikasi yang Anda masukkan salah.';
                            if (isset($_POST['LoginForm']['r'])) {
                                $r = $_POST['LoginForm']['r'];
                            }
                            if (isset($_POST['LoginForm']['remember'])) {
                                $remember = $_POST['LoginForm']['remember'];
                            }
                            $need_tfa = true;
                        }
                    }
                } else {
                    $has_password = \Model\AdminModel::hasPassword($_POST['LoginForm']['password'], $model->salt);
                    if ($model->password == $has_password){
                        $remember = false;
                        if ($_POST['LoginForm']['remember'] > 0)
                            $remember = true;
                        // check still has cookie or not
                        if (!isset($_COOKIE['auth_code'])) {
                            // 2fa
                            $extensions = json_decode($this->_container->get('settings')['params']['extensions'], true);
                            if (in_array('twofa', $extensions)) {
                                $tfa_model = \ExtensionsModel\TwofaModel::model()->findByAttributes(['admin_id' => $model->id]);
                                if ($tfa_model instanceof \RedBeanPHP\OODBBean) {
                                    if ($tfa_model->status > 0) {
                                        $need_tfa = true;
                                    }
                                }
                            }
                        }

                        if (!$need_tfa) {
                            $login = $this->_user->login($model, $remember);
                            if ($login){
                                if (isset($_GET['r']))
                                    return $response->withRedirect($_GET['r']);
                                else
                                    return $response->withRedirect('/panel-admin');
                            }
                        }
                    } else {
                        $args['error']['message'] = 'Password yang Anda masukkan salah.';
                    }
                }
            } else {
                $args['error']['message'] = 'User tidak ditemukan';
            }
        }

        return $this->_container->module->render($response, 'default/login.html', [
            'result' => $args,
            'need_tfa' => $need_tfa,
            'username' => (!empty($username))? $username : '',
            'r' => $r,
            'remember' => $remember,
        ]);
    }

    public function logout($request, $response, $args)
    {
        if ($this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        $logout = $this->_user->logout();
        if ($logout){
            return $response->withRedirect($this->_login_url);
        }
    }

    public function change_password($request, $response, $args)
    {
        if ($this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        $model = \Model\AdminModel::model()->findByPk($this->_user->id);

        $errors = []; $success = false; $message = null;
        if (isset($_POST['PasswordForm'])){
            if (empty($_POST['PasswordForm']['old_password']))
                array_push( $errors, 'Password lama tidak boleh dikosongi.' );
            if (empty($_POST['PasswordForm']['new_password']))
                array_push( $errors, 'Password baru tidak boleh dikosongi.' );
            if (empty($_POST['PasswordForm']['confirm_new_password']))
                array_push( $errors, 'Masukkan sekali lagi password baru Anda.' );

            $old_password_input = \Model\AdminModel::hasPassword($_POST['PasswordForm']['old_password'], $model->salt);
            if ($model->password != $old_password_input) {
                array_push( $errors, 'Password lama yang Anda masukkan salah.' );
            }
            if ($_POST['PasswordForm']['new_password'] != $_POST['PasswordForm']['confirm_new_password']) {
                array_push( $errors, 'Silakan ulangi password baru dengan benar.' );
            }

            if (count($errors) <= 0) {
                $has_password = \Model\AdminModel::hasPassword($_POST['PasswordForm']['new_password'], $model->salt);
                $model->password = $has_password;
                $model->updated_at = date("Y-m-d H:i:s");
                $save = \Model\AdminModel::model()->update($model);
                if ($save) {
                    $message = 'Password Anda telah berhasil diubah.';
                    $success = true;
                }
            }
        }

        return $this->_container->module->render($response, 'default/change-password.html', [
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'data' => (!empty($_POST['PasswordForm'])) ? $_POST['PasswordForm'] : array(),
        ]);
    }
}