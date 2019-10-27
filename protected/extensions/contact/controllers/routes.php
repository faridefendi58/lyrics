<?php

$app->post('/kontak-kami', function ($request, $response, $args) {
    $message = 'Pesan Anda gagal dikirimkan.';
    $success = true;
    $settings = $this->get('settings');
    if (isset($_POST['Contact'])){
        $model = new \ExtensionsModel\ContactModel();
        $model->name = $_POST['Contact']['name'];
        $model->email = $_POST['Contact']['email'];
        $model->phone = $_POST['Contact']['phone'];
        $model->message = $_POST['Contact']['message'];
        $model->created_at = date("Y-m-d H:i:s");
        $save = \ExtensionsModel\ContactModel::model()->save($model);
        if ($save) {
            $success = true;
            $message = 'Pesan Anda berhasil dikirim. Kami akan segera merespon pesan Anda.';
        } else {
            $success = false;
        }
    }

    return $this->view->render($response, 'kontak-kami.phtml', [
        'success' => $success,
        'message' => $message
    ]);
});

foreach (glob(__DIR__.'/*_controller.php') as $controller) {
	$cname = basename($controller, '.php');
	if (!empty($cname)) {
		require_once $controller;
	}
}

$app->group('/contact', function () use ($user) {
    $this->group('/messages', function() use ($user) {
        new Extensions\Controllers\MessagesController($this, $user);
    });
});

?>
