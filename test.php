<?php
  require ("./vendor/autoload.php");
  $yt = (new sharksonmgn\YtdlAdapter\Adapter())->downloadIfNotExist();
  $yt = (new sharksonmgn\YtdlAdapter\Adapter())->downloadFfmpegIfNotExists();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Adapter Test</title>
    <link href="https://code.jquery.com/ui/1.13.1/themes/smoothness/jquery-ui.css" rel="stylesheet" crossorigin="anonymous">    <!-- <link rel="stylesheet" href="css/main.css" /> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">    <!-- <link rel="stylesheet" href="css/main.css" /> -->
    <style type="text/css">
      html,body {
        width: 100%;
        margin: 0;
        padding: 0;
      }

      #body {
        font-size: 0.8em;
      }

      #urls {
        overflow: hidden;
      }

      .loader {
        border: 3px solid #e3e3e3; /* Light grey */
        border-top: 3px solid grey; /* Blue */
        border-radius: 50%;
        width: 48px;
        height: 48px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
      }

      .loader, #urlExample, #videoExample {
        display: none;
      }

      .thumb {
        position: relative;
        overflow: hidden;
      }
      .thumb .duration {
        position: absolute;
        bottom: 0;
        width: 100%;
        background: #ffffffbb;
        text-align: right;
        font-size: 0.7em;
        font-weight: bold;
      }

      .url a {
        color: white;
      }

      .thumb img {
        cursor: pointer;
      }

      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    </style>
    <link rel="icon" href="icon.png" />
  </head>

  <body>
    <div id="imgExample" class="thumb rounded">
      <img src="" style="width: 100%"/>
      <!-- <iframe type="text/html" width="196" height="110" frameborder="0"></iframe> -->
      <!-- <iframe width="196" height="110" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> -->
      <span class="duration px-2">dur</span>
    </div>

    <div class="row" id="urlExample">
      <div class="col-12 url rounded border fw-bold bg-secondary text-white p-3 mb-1">
        url
      </div>
      <div class="col-12 videos">
        <div class="loader"></div>
      </div>
    </div>

    <div class="container-flow p-1 videoObject" id="videoExample">
      <div class="row">
        <div class="col">
          <div class="row">
            <div class="col-12 fw-bold title">
              dyduł
            </div>
            <div class="col-12 fw-italic info">
              data, wyświetlenia
            </div>
            <div class="col-12 downloadArea">
              <span class="fromto">0:00 - </span>
              <div class="timeSlider">

              </div>
              <input type="button" class="btn btn-small btn-success mt-2 fw-bold mx-auto d-flex justify-content-center convertBtn"
                style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: 1rem; --bs-btn-font-size: .6rem; width: 10em;" value="Konwertuj" />
            </div>
          </div>
        </div>
        <div class="col-6 col-md-5 col-lg-4 imgCover">
          miniaturka
        </div>
      </div>
    </div>

    <div class="container-fluid" id="body">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-11 col-md-10 col-lg-8 col-xl-6">

          <div class="container-fluid">
            <div class="row justify-content-center">
              <nav class="col navbar navbar-dark bg-dark rounded-bottom">
                <div class="container-fluid">
                  <a class="navbar-brand" href="#">YTAdapter</a>
                </div>
              </nav>
            </div>
            <div class="row justify-content-center">
              <div class="col mt-3">
                <textarea class="form-control" id="inputUrls" rows="3" placeholder="Paste url here..." style="resize: none;"><?php if (isset($_GET['v'])) { echo $_GET['v']; } ?></textarea>
              </div>
            </div>
            <div class="row justify-content-center">
              <div class="col my-3">
                <div class="container-fluid" id="urls">

                </div>
              </div>
            </div>
            <div class="row justify-content-center">
              <div class="col my-3">
                <textarea class="form-control" rows="10" id="debug"></textarea>
                <div id="debugHTML"></div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.0.0/crypto-js.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>    <!-- <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> -->
    <script src="https://momentjs.com/downloads/moment-with-locales.min.js"></script>    <!-- <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script> -->
    <script src="./adapter.js"></script>

    <script type="text/javascript">
      (function(){

        // var q = function(data,callback = null) {
        //
        //   $.ajax({
        //     type: "POST",
        //     url: "api.php",
        //     // The key needs to match your method's input parameter (case-sensitive).
        //     data: JSON.stringify(data),
        //     contentType: "application/json; charset=utf-8",
        //     dataType: "json",
        //     success: function(res){
        //       document.querySelector('#debug').value = JSON.stringify(res);
        //       document.querySelector('#debugHTML').innerHTML = JSON.stringify(res);
        //       if (callback !== null)
        //         callback(res);
        //     },
        //     error: function(err) {
        //       document.querySelector('#debug').value = err.responseText;
        //       document.querySelector('#debugHTML').innerHTML = err.responseText;
        //     }
        //   });
        //
        // }
        //
        // var textarea = document.querySelector('#urls');
        // var prevLines;
        // var intervals = {};
        // var
        // var loader = $('<div class="loader"></div>');
        //
        // function _md5 (str) {
        //   return CryptoJS.MD5(str);
        // }
        //
        // function validateUrlsCallback(res) {
        //   for (let r of res) {
        //     let el = $('#urlExample').children().eq(0).clone();
        //     let md5 = _md5(r);
        //     el.attr('id',md5);
        //     el.find('.url:eq(0)').text(r);
        //     el.find('.videos:eq(0)').append(loader.clone());
        //     $('#videos').append(el);
        //     query({get:'requestInfo',url: r});
        //     intervals[md5] = setInterval(function () {
        //
        //     }, 10);
        //   }
        // }
        //
        // function validateUrls() {
        //   q({get:'validateUrls',urls: textarea.value},validateUrlsCallback);
        // }
        //
        // var textareaResize = function() {
        //   enteredText = textarea.value;
        //   numberOfLineBreaks = (enteredText.match(/\n/g)||[]).length;
        //
        //   if (prevLines == numberOfLineBreaks)
        //     return;
        //
        //     prevLines = numberOfLineBreaks;
        //
        //   textarea.style.height = 'auto';
        //   let currentRows = textarea.getAttribute('rows');
        //   textarea.setAttribute('rows',numberOfLineBreaks+2);
        //
        //   let height = $(textarea).height();
        //   textarea.setAttribute('rows',currentRows);
        //
        //   $(textarea).stop(true,true).animate({
        //     height: height + 'px'
        //   },function(){
        //     textarea.setAttribute('rows',numberOfLineBreaks+2);
        //   });
        //
        // }
        //
        // $('.loader').hide();
        //
        // "keyup blur".split(" ").forEach(function(e){
        //   textarea.addEventListener(e,() => {textareaResize(),
        //   validateUrls()},false);
        // });
        //
        // if (textarea.value !== '') {
        //   textareaResize();
        //   validateUrls();
        // }

      })();
    </script>
  </body>
</html>
