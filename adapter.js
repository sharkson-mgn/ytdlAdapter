(function(){

  var el = {
    textarea: document.querySelector('#inputUrls'),
    debug: document.querySelector('#debug'),
    debugHTML: document.querySelector('#debugHTML'),
    urls: document.querySelector('#urls'),
    urlExample: document.querySelector('#urlExample'),
    videoExample: document.querySelector('#videoExample'),
    imgExample: document.querySelector('#imgExample'),
  },
  prevLines,
  validUrls = [],
  validHash = [],
  requested = {}
  errorTick = 0;

  var q = function(data,callback = null) {

    $.ajax({
      type: 'POST',
      url: "api.php",
      // The key needs to match your method's input parameter (case-sensitive).
      data: JSON.stringify(data),
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      success: function(res) {
        el.debug.value = JSON.stringify(res);
        el.debugHTML.innerHTML = JSON.stringify(res);
        if (callback !== null) {
          callback(res);
        }
      },
      error: function(err) {
        el.debug.value = err.responseText;
        el.debugHTML.innerHTML = err.responseText;
        console.log(err);
        q(
          data,callback
        )
      },
      timeout: 1000
    });

  }

  var detectdelimiter = function(str) {
    var delimiter = '\r\n';
    if (!str.includes(delimiter) && str.includes('\n'))
    {
      delimiter = '\n';
    }
    else {
      return false;
    }
    return delimiter;
  }

  var detectMultiline = function(str, delimiter = '\n') {
    str = str.split(delimiter);
    str = str.filter(n => n);

    return !(str.length <= 1);
  }

  var urlIsValid = function(url) {
    if (url != undefined || url != '') {
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
        var match = url.match(regExp);
        if (match && match[2].length == 11) {
            return true;
        }
    }
    return false;
  }

  var validateUrls = function() {

    var urls = el.textarea.value;
    var delimiter = detectdelimiter(urls);
    var isMultiline = (delimiter !== false) ? detectMultiline(urls,delimiter) : false;

    if (isMultiline && typeof urls === 'string') {
      urls = urls.split(delimiter);
    }

    let validUrls = [];
    validHash = [];
    if (isMultiline) {
      for (let url of urls) {
        if (urlIsValid(url))
        {
          validUrls.push(url);
          validHash.push(CryptoJS.MD5(url).toString());
        }
      }
    }
    else {
      if (urlIsValid(urls)) {
        validUrls.push(urls);
        validHash.push(CryptoJS.MD5(urls).toString());
      }
    }

    return validUrls;
  };

  drawUrl = function (i) {
    $(el.urls).append(
      $(el.urlExample)
        .clone()
        .attr('id',validHash[i])
    );
    $('#' + validHash[i] + ' .url').text(validUrls[i]);
    $('#' + validHash[i] + ' .loader').slideDown();
    $('#' + validHash[i]).slideDown();
  };

  exctratGetParams = function(url) {
    let attrs = url.split('?')[1].split('&');
    let ret = {};
    for (let i in attrs) {
      let a = attrs[i].split('=');
      ret[a[0]] = a[1];
    }
    return ret;
  };

  convertToHumanYear = function(ts) {
    return ts.substr(6,2) + '-' + ts.substr(4,2) + '-' + ts.substr(0,4);
  };

  calcDur = function(time, units = [60,60,24,7], delimiter = ':') {

    let r = [], d;
    for (let i in units) {
      d = time % units[i];
      if (d <= 0 && time == 0 && r.length > 1) break;
      r.push(d);
      time = Math.floor(time / units[i]);
    }

    return r.reverse().join(delimiter);
  };

  durationLead = function(cv,j) {
    return j > 0 && cv.toString().length == 1 ? '0'+cv : cv;
  };

  showError = function(res) {
    console.log(res);
  }

  drawVideos = function(i) {
    // console.log(requested[validHash[i]]);
    $('#' + validHash[i] + ' .loader').remove();
    let videos = [];
    if (typeof requested[validHash[i]].entries !== 'undefined') {
      videos = requested[validHash[i]].entries;
    }
    else {
      videos.push(requested[validHash[i]]);
    }
    let vid;
    let urlParams = exctratGetParams(validUrls[i]);
    $('#' + validHash[i] + ' .url').html('<a href="'+$('#' + validHash[i] + ' .url').text()+'" target="_blank">'+requested[validHash[i]].title+'</a>');

    for (let g in videos) {
      vid = $(el.videoExample)
        .clone()
        .attr('id',videos[g].id);
      $(vid).find('.title').html('<a href="https://youtube.com/watch?v='+videos[g].id+'" target="_blank">'+videos[g].title+'</a>');

      let img = $(el.imgExample).clone().removeAttr('id').show();
      // let imgImg = $(img).find('iframe:eq(0)').attr('src','http://www.youtube.com/embed/'+videos[g].id);
      let imgImg = $(img).find('img:eq(0)').attr('src',videos[g].thumbnails[2].url);
      $(imgImg).click(function(){
        let ifrejm = $('<iframe width="196" height="110" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
        ifrejm.attr('src','http://www.youtube.com/embed/'+videos[g].id+'?autoplay=1');
        $(this).replaceWith(ifrejm);
        ifrejm.animate({
          width: 246+'px',
          height: 138+'px',
        });
      });
      $(img).find('.duration:eq(0)').text(
        calcDur(videos[g].duration).split(':').map(durationLead).join(':')
      );
      $(vid).find('.imgCover').html(img);

      $(vid).find('.fromto').append(calcDur(videos[g].duration).split(':').map(durationLead).join(':'));
      $(vid).find('.timeSlider').slider({
        range: true,
        min: 0,
        max: videos[g].duration,
        values: [ 0, videos[g].duration ],
        slide: function( event, ui ) {
          $('#'+videos[g].id).find('.fromto:eq(0)').text(
            calcDur(ui.values[ 0 ]).split(':').map(durationLead).join(':') + " - " + calcDur(ui.values[ 1 ]).split(':').map(durationLead).join(':')
          );
        }
      });

      $(vid).find('.info').html(
        convertToHumanYear(videos[g].upload_date) +
        ' ('+moment(videos[g].upload_date).fromNow()+') by <i>' +
        videos[g].uploader + '</i>'
      );

      if (typeof urlParams.index !== 'undefined' && urlParams.index == parseInt(g) + 1) {
        $(vid).addClass('border border-2 rounded');
      }

      $(vid).find('.convertBtn:eq(0)').click(function(){downloadBegin(videos[g].id);});

      $('#' + validHash[i] + ' .videos').append(
        $(vid)
      );

      $('#' +  videos[g].id).slideToggle();

      setTimeout(function() {
        q(
          {
            get: 'downloadInfo',
            url: 'https://www.youtube.com/watch?v=' + videos[g].id
          },
          downloadProgress
        )
      },1);
    }
  }

  var createProgress = function(id) {
    let el = '#' + id;

    $(el + ' .downloadArea').children().slideUp({
      complete: function() {
        $(this).remove();
      }
    });

    let progressBar = $('<div class="progress mt-2" style="height: 3em;"></div>');
    progressBar.append('<div class="progress-bar bg-info progress-bar-striped progress-bar-animated barDownload" role="progressbar" aria-label="Segment one" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">Pobieranie 0%</div>');
    progressBar.append('<div class="progress-bar bg-success progress-bar-striped progress-bar-animated barConvert" role="progressbar" aria-label="Segment two" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">Konwertowanie 0%</div>');
    $(progressBar).hide();
    $(el).find('.downloadArea').append(progressBar);

    $(progressBar).delay(500).slideDown();
  }

  var downloadBegin = function(id) {
    createProgress(id);

    downloadProgress(id);

  }

  var downloadProgress = function(res = false) {
    //console.log([res, typeof res]);
    if (typeof res === 'string') {
      console.log('request...');
      q(
        {
          get: 'downloadRequest',
          url: 'https://www.youtube.com/watch?v=' + res
        },
        downloadProgress
      );
    }
    else if (res.response == 'ok' && (res.reason == 'downloadRequest' || res.reason == 'downloadInfo') && res.res !== false) {
      if (typeof res.res !== 'object' || res.res.status !== 'end')
      {
        setTimeout(function(){
          q(
            {
              get: 'downloadInfo',
              url: res.post.url
            },
            downloadProgress
          )
        },500);
      }
      if (typeof res.res === 'object') {
        let param = exctratGetParams(res.post.url);
        if ($('#'+param.v).find('.progress:eq(0)').is(":hidden") || !$('#'+param.v).find('.progress:eq(0)').length) {
          createProgress(param.v);
        }
        if (res.res.status == 'downloading')
          $('#' + param.v).find('.barDownload').css('width',res.res.percentage +'%').text('Pobieranie ' + res.res.percentage + '%');
        if (res.res.status == 'converting')
        {
          $('#' + param.v).find('.barDownload').css('width','0')
          $('#' + param.v).find('.barConvert').css('width',res.res.percentage +'%').text('Konwertowanie ' + res.res.percentage.toFixed(1) + '%');
        }
        if (res.res.status == 'end')
          $('#' + param.v).find('.progress:eq(0)').hide().stop(true,false).slideUp({
            complete: function() {
              let button = $('<a href="./api.php?get='+param.v+'" class="btn btn-small btn-danger mt-2 fw-bold mx-auto d-flex justify-content-center convertBtn" style="--bs-btn-padding-y: .25rem; --bs-btn-padding-x: 1rem; --bs-btn-font-size: .6rem; width: 10em;" download>Pobierz</a>');
              $(this).parent().append(button).hide().delay(500).slideDown();
              $(this).remove();
            }
          });
      }
    }

  }

  var getInfo = function(res = false) {
    if (res === false) {
      for (let i in validUrls) {
        drawUrl(i);
        q(
          {
            get: 'infoRequest',
            urls: validUrls[i],
          },
          getInfo
        );
      }
    }
    else {
      if (res === 'check' || res.response === 'ok')
      {
        if ((res.reason === 'infoRequest' || (res.reason === 'infoGet' && (res.res == 'inProgress' || res.res[Object.keys(res.res)[0]] === 'inProgress'))))
        {
          setTimeout(function() {
            q(
              {
                get: 'infoGet',
                urls: res.post.urls,
              },
              getInfo
            );
          },500);
        }
        else {
          let hash = Object.keys(res.res)[0];
          res.res[hash] = JSON.parse(res.res[hash]);
          requested[hash] = res.res[hash];
          // console.log([hash,validHash.indexOf(hash)]);
          drawVideos(validHash.indexOf(hash));
        }
      }
      else {
        if (res.response === 'error' && res.reason === 'infoRequest') {
          if (errorTick < 10) {
            errorTick++;
            getInfo();
          }
          else {
            showError(res);
          }
        }
      }
    }
  }

  var textareaResize = function() {
    let enteredText = el.textarea.value;
    let breaksMatches = enteredText.match(/\n/g);
    if (breaksMatches !== null) {
      breaksMatches = breaksMatches.filter(n => n);
    }
    let numberOfLineBreaks = (breaksMatches||[]).length;

    if (prevLines == numberOfLineBreaks)
      return;

      prevLines = numberOfLineBreaks;

    el.textarea.style.height = 'auto';
    let currentRows = el.textarea.getAttribute('rows');
    el.textarea.setAttribute('rows',numberOfLineBreaks+1);

    let height = $(el.textarea).outerHeight();
    el.textarea.setAttribute('rows',currentRows);

    $(el.textarea).stop(true,false).animate({
      height: height + 'px'
    },function(){
      el.textarea.setAttribute('rows',numberOfLineBreaks+1);
    });

  }



  $(['.loader',el.urlExample,el.videoExample,el.imgExample]).hide();
  $([el.debug,el.debugHTML]).hide();

  "keyup blur".split(" ").forEach(function(e){
    el.textarea.addEventListener(e,() => {
      textareaResize();
      validUrls = validateUrls();
      getInfo();
    },false);
  });

  if (el.textarea.value !== '') {
    textareaResize();
    validUrls = validateUrls();
    getInfo();
  }

})();
