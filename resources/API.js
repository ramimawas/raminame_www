var API = {
  host: "http://www.rami.name/resources/api.php",
  //host: 'http://localhost/rami.name/resources/api.php',
  buildApiUrl: function(query) {
    url = [API.host] + "?" + API.buildApiQs(query);
    console.log(url);
    return url;
  },
  buildApiQs: function(query) {
    var qs = [];
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
    return function(data) {
      if (data && data.status && data.status.code == 200 && data.data) {
        fn(data.data, map);
      }
    }
  }
}