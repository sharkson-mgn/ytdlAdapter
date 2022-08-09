<?php

  require ("./vendor/autoload.php");

  function returnJson ($data) {

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();

  }

  if (empty($_POST))
  {
    $_POST = json_decode(file_get_contents('php://input'), true);
  }

  if (!empty($_POST)) {

    $post = $_POST;

    if (!isset($post['get']))
      returnJson(['response'=>'error','reason'=>'post/get not isset','post'=>$post]);

    switch ($post['get']) {
      case 'validateUrls':
        $urls = (new sharksonmgn\YtdlAdapter\Adapter())->getValidUrls($post['urls'],null);
        returnJson($urls);
        break;
      case 'infoRequest':
        if (isset($post['urls']) && !empty($post['urls'])) {
          $res = sharksonmgn\YtdlAdapter\Adapter::infoRequest($post['urls']);
          returnJson(['response'=>'ok','reason'=>'infoRequest','post'=>$post, 'res'=>$res]);
        }
        else {
          returnJson(['response'=>'error','reason'=>'infoRequest','post'=>$post]);
        }
        break;
      case 'infoGet':
        if (isset($post['urls']) && !empty($post['urls'])) {
          $res = sharksonmgn\YtdlAdapter\Adapter::infoRequestStatus($post['urls']);
          returnJson(['response'=>'ok','reason'=>'infoGet','post'=>$post,'res'=>$res]);
        }
        else {
          returnJson(['response'=>'error','reason'=>'infoGet','post'=>$post]);
        }
        break;
      case 'downloadRequest':
        if (isset($post['url']) && !empty($post['url'])) {
          try {
            $res = sharksonmgn\YtdlAdapter\Adapter::downloadRequest($post['url']);
          } catch (Exception $e) {
            returnJson(['response'=>'error','reason'=>'downloadRequest','post'=>$post, 'res'=>$e]);
          }
          returnJson(['response'=>'ok','reason'=>'downloadRequest','post'=>$post,'res'=>$res]);
        }
        else {
          returnJson(['response'=>'error','reason'=>'downloadRequest','post'=>$post]);
        }
        break;
      case 'downloadInfo':
        if (isset($post['url']) && !empty($post['url'])) {
          $res = (new sharksonmgn\YtdlAdapter\Adapter($post['url']))->downloadProgres();
          returnJson(['response'=>'ok','reason'=>'downloadInfo','post'=>$post,'res'=>$res]);
        }
        else {
          returnJson(['response'=>'ok','reason'=>'downloadInfo','post'=>$post,'res'=>$res]);
        }
        break;
      default:
        returnJson(['response'=>'ok','reason'=>'default case','post'=>$post]);
        exit();
    }


  }

  if (!empty($_GET)) {
    $get = $_GET;
    if (isset($get['get'])) {
      $yt = new sharksonmgn\YtdlAdapter\Adapter('https://www.youtube.com/watch?v=' . $get['get']);
      $file_url = $yt->getDownloadPath();
      header('Content-Type: application/octet-stream');
      header("Content-Transfer-Encoding: Binary");
      header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
      readfile($file_url);
      // var_dump($file_url);
      exit();
    }
  }

  returnJson(['response'=>'error','reason'=>'post/get not isset']);

  exit();



  // $urls = [
    // 'https://www.youtube.com/watch?v=wlOdTniCvt0&list=RDwlOdTniCvt0&start_radio=1',
    // 'https://www.youtube.com/watch?v=027MPSAyEqk',
    // 'https://www.youtube.com/watch?v=EZPacMmxn54&list=PL433Q3_t34xsFeGMMqqOGIdWq6GCdhfmm',
    // '',
    // 'https://sharkson.eu',
    // null,
    // false
  // ];
  $urls = include('urls.php');

  foreach ($urls as $u) {
    $yt = new sharksonmgn\YtdlAdapter\Adapter($u);
    var_dump(
      [
        'url'           => $yt->getUrl(),
        'valid'         => $yt->isValid(),
        'playlist'      => $yt->isPlaylist(),
        'info'          => $yt->getInfo(),
        'output'        => $yt->getExecOutput(),
        'retval'        => $yt->getExecRetval(),
        'params'        => $yt->getLastParams(),
        'action'        => $yt->getLastAction(),
        'execStatus'    => $yt->getExecStatus(),
        'infoStatus'    => $yt->getInfoStatus(),
      ]
    );
  }

  $yt = (new sharksonmgn\YtdlAdapter\Adapter())->downloadIfNotExist();

?>
