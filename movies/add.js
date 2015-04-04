$(document).ready(function() {
  
 var rotten = {
      host: "http://api.rottentomatoes.com/api/public/v1.0/movies.json?",
      apikey: "6k3szrykkcyqjpvxkkzpt2az"
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
    fixButton: $("#fix-button"),
    confirm: $("#confirmation"),
    imdb: $("#imdb"),
    rotten: $("#rotten")
  };
  
  var paint = function(data) {
    console.log(data);
    $ids.imdbText.val(data.imdb_id);
    $ids.imdb.attr('href', 'http://www.imdb.com/title/' + data.imdb_id);
    $ids.rottenText.val(data.rotten_id);
    console.log('http://www.rottentomatoes.com/m/' + data.rotten_id);
    $ids.rotten.attr('src', 'http://www.rottentomatoes.com/m/' + data.rotten_id);
  };
  
  $ids.omdbButton.click(function() {
    window.open('http://www.omdbapi.com/?s=' + encodeURIComponent($ids.titleText.val()), '_blank').focus();
  });
  
  $ids.rottenButton.click(function() {
    window.open(rotten.host + "q=" + encodeURIComponent($ids.titleText.val()) + "&apikey=" + rotten.apikey, '_blank').focus();
  });
  
  $ids.titleButton.click(function() {
    $ids.imdbText.val('');
    $ids.rottenText.val('');
    $ids.ratingText.val('');
    var query = {};
    query.method = 'find';
    query.title = $ids.titleText.val();
    API.call(query, paint);
  });
  
  $ids.findByIdButton.click(function() {
    var query = {};
    query.method = 'find';
    var imdbId = $ids.imdbText.val(),
        rottenId = $ids.rottenText.val();
    if (imdbId != '')
      query.imdbid = imdbId;
    if (rottenId != '')
      query.rid = rottenId;
    API.call(query, paint);
  });
  
  $ids.addButton.click(function() {
    var query = {};
    query.method = 'add';
    query.imdbid = $ids.imdbText.val();
    query.rating = $ids.ratingText.val();
    API.call(query, function(data) {
      var status = {code: 200, text: "OK"};
      $ids.confirm.html("Added: " + status.code + " / " + status.text).show('fast');
      setTimeout(function() {$ids.confirm.html('').hide('slow');}, 3000);
    });
  });

  $ids.fixButton.click(function() {
      var query = {};
      query.method = 'fix';
      var imdbId = $ids.imdbText.val(),
        rottenId = $ids.rottenText.val(),
        rating = $ids.ratingText.val();
      if (imdbId != '' && rottenId != '') {
        query.rid = rottenId;
        query.imdbid = imdbId;
        if (rating != '')
          query.rating = rating;
        API.call(query, function(data) {
          var status = {code: 200, text: "OK"};
          $ids.confirm.html("Fixed: " + status.code + " / " + status.text).show('fast');
          setTimeout(function() {$ids.confirm.html('').hide('slow');}, 3000);
        });
      }
    });
});