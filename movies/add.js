$(document).ready(function() {
  
  var api = {
    //host: "http://rami.name/localhost/api.php"
    host: "http://localhost/rami.name/movies/api.php",
    rotten: {
      host: "http://api.rottentomatoes.com/api/public/v1.0/movies.json?",
      apikey: "6k3szrykkcyqjpvxkkzpt2az"
    }
  };
  
  var $ids = {
    titleText: $("#title-text"),
    titleButton: $("#title-button"),
    omdbButton: $("#omdb-button"),
    rottenButton: $("#rotten-button"),
    imdbText: $("#imdb-text"),
    rottenText: $("#rotten-text"),
    findByIdButton: $("#findById-button"),
    ratingText: $("#rating-text"),
    addButton: $("#add-button"),
    confirm: $("#confirmation"),
    imdb: $("#imdb"),
    rotten: $("#rotten")
  };

  var buildUrl = function() {
    url = [api.host] + "?" + buildQs()
    console.log(url);
    return url;
  }

  var buildQs = function() {
    var qs = [];
    console.log(query);
    $.each(query, function(key, value) {
      console.log( key + " = " + value);
      if (value != null)
        qs.push(key + "=" + encodeURIComponent(value));
    });
    return qs.join("&");
  }
  
  var query = {
    m: null,
    t: null,
    i: null,
    rid: null,
    r: null
  };
  
  var reset = function() {
    query.m = null;
    query.t = null;
    query.i = null;
    query.rid = null;
    query.r = null;
  }
  
  var paint = function(data) {
    console.log(data);
    if (data && data.status && data.status.code == 200 && data.data) {
      $ids.imdbText.val(data.data.imdb_id);
      $ids.imdb.attr('src', 'http://www.imdb.com/title/' + data.data.imdb_id);
      $ids.rottenText.val(data.data.rotten_id);
      console.log('http://www.rottentomatoes.com/m/' + data.data.rotten_id);
      $ids.rotten.attr('src', 'http://www.rottentomatoes.com/m/' + data.data.rotten_id);
    }
  };
  
  $ids.omdbButton.click(function() {
    window.open('http://www.omdbapi.com/?s=' + encodeURIComponent($ids.titleText.val()), '_blank').focus();
  });
  
  $ids.rottenButton.click(function() {
    window.open(api.rotten.host + "q=" + encodeURIComponent($ids.titleText.val()) + "&apikey=" + api.rotten.apikey, '_blank').focus();
  });
  
  $ids.titleButton.click(function() {
    reset();
    $ids.imdbText.val('');
    $ids.rottenText.val('');
    query.m = 'find';
    query.t = $ids.titleText.val();
    call(paint);
  });
  
  $ids.findByIdButton.click(function() {
    reset();
    query.m = 'find';
    var imdbId = $ids.imdbText.val(),
        rottenId = $ids.rottenText.val();
    if (imdbId != '')
      query.i = imdbId;
    if (rottenId != '')
      query.rid = rottenId;
    call(paint);
  });
  
  $ids.addButton.click(function() {
    reset();
    query.m = 'add';
    query.i = $ids.imdbText.val();
    query.r = $ids.ratingText.val();
    call(function(data) {
      console.log(data);
      var status = {code: 500, text: "No data!"};
      if (data && data.status)
        status = data.status;
      $ids.confirm.html(status.code + " / " + status.text).show('fast');
      setTimeout(function() {$ids.confirm.html('').hide('slow');}, 3000);
    });
  });
  
  var call = function(fn) {
    console.log('LOAD query: ', query);
    $.get(
      buildUrl(),
      {},
      fn,
      "json"
    );
  };
});