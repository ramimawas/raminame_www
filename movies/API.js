var API = {
  //shost: "http://www.rami.name/movies/api.php",
  host: 'http://localhost/rami.name/movies/api.php',
  buildApiUrl: function(query) {
    url = [API.host] + "?" + API.buildApiQs(query);
    console.log(url);
    return url;
  },
  buildApiQs: function(query) {
    var qs = [];
    console.log(query);
    $.each(query, function(key, value) {
      if (value != null)
        qs.push(key + "=" + encodeURIComponent(value));
    });
    return qs.join("&");
  },
  query: function() {
    return  {
      method: null,
      imdbid: null,
      rid: null,
      limit: null,
      skip: null,
      count: null,
      field: null
    }
  },
  call: function(query, fn, map)  {
    $.getJSON(
      API.buildApiUrl(query),
      API.callback(fn, map)
     )
  },
  callback: function(fn, map) {
    console.log(map);
    return function(data) {
      console.log(data);
      if (data && data.status && data.status.code == 200 && data.data) {
        fn(data.data, map);
      }
    }
  }
}