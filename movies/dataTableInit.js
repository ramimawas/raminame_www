$(document).ready(function() {
  var mongoDb = {
    host: "https://api.mongohq.com/databases",
    dbName: "Movies",
    collectionName: "watched",
    token: "cqebxoajr6eueff6j7ax"
  };

  var tableId = "dataTable";
  
  var mongo = {
    and: ["directors", "genres"],
    or: ["year", "rating"]
  };

  var query = {
    directors: null,
    genres: null,
    rating: null,
    year: null
  };
  
  var tagIds = {
    filters: "#filters",
    table: "#table"
  };
  
  var cumulativeFilterFlag = false;

  var buildUrl = function() {
    console.log(query);
    return [mongoDb.host, mongoDb.dbName, "collections", mongoDb.collectionName, "documents"].join("/") + "?" + buildQs();
  }

  var buildQs = function() {
    var q = [],
        qs = [];
    qs.push("_apikey=" + mongoDb.token);
    $.each(query, function(key, value) {
      if (value != null && value.length > 0) {
        var values = [];
        $.each(value, function(key, value) {
          if($.inArray(key, mongo.and) != -1)
            value = '"' + value + '"'
          values.push(value);
        });
        var mongoComand = $.inArray(key, mongo.and)!=-1? "$all": "$in";
        q.push(key + ':{'+ mongoComand + ':[' + values.join(',') + ']}');
      }
    });
    if (q.length > 0)
      qs.push('q=' + "{" + q.join(',') + "}");
    return qs.join("&");
  }

  var addFilter = function(key, value, reset) {
    console.log("addFilter: " + key + "/" + value + ", " + reset);
    if (reset)
      clearFilters(); 
    if(query[key] == null)
      query[key] = [];
    if($.inArray(value, query[key]) == -1) {
      query[key].push(value);
      buildFilterButttons();
    }
  }

  var removeFilter = function(key, value) {
    console.log("removeFilter: " + key + "/" + value);
    if (value == null) {
      query[key] = null;
    } else if (query[key] != null) {
      query[key] = $.grep(query[key], function(item) {
          return item != value;
      });
    }
    buildFilterButttons();
  }
  
  var cap = function(string) {
    return typeof string == "string"? string.charAt(0).toUpperCase() + string.slice(1): string;
  }

  var buildFilterButttons = function () {
    console.log("buildFilterButttons");
    var $filters = $(tagIds.filters).html(""),
        count = 0;
    $.each(query, function(key, value) {
      console.log(key + ": " + value);
      if (value != null && value.length > 0) {
        var $filter = $('<div class="filter">').append($('<label>').text(cap(key))),
          $list = $('<div>');
        $.each(value, function(index, item) {
          $list.append($('<button filter="' + key +'">').text(cap(item)));
          count++;
        });
        $filters.append($filter.append($list));
      }
    });
    if (count>0) {
      $filters.append($('<div class="fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix" style="padding:5px;">').append($('<button style="width: 100%;" filter="clear">').text("Clear")));
      $("button").button();
      $filters.show();
    } else
      $filters.hide();
  }

  var clearFilters = function () {
    console.log("clearFilters:");
    $(tagIds.filters).hide();
    query.director = null;
    query.genres = null;
    query.rating = null;
    query.year = null;
  }

  var render = function(filter) {
    return function(obj) {
      var filterValue = obj.aData[filter],
          strArray = [];
      if(filterValue.constructor != Array)
        filterValue = [filterValue];
      filterValue.forEach(function(value, index, array) {
        strArray.push('<div class="link" filter="' + filter +'">' + cap(value) + '</div>');
      });
      return strArray.join(', ');
    }
  }
  
  var structure = [
    {"sTitle": "Position", "mDataProp": "position", "sWidth": "75px"},
    {"sTitle": "Title", "mDataProp": "title"},
    {"sTitle": "Rating", "mDataProp": "rating", fnRender: render('rating'), "sWidth": "65px"},
    {"sTitle": "Year", "mDataProp": "year", fnRender: render('year'), "sWidth": "65px"},
    {"sTitle": "Runtime", "mDataProp": "runtime", fnRender: render('runtime'), "sWidth": "80px"},
    {"sTitle": "Genres", "mDataProp": "genres", fnRender: render('genres')},
    {"sTitle": "Title Type", "mDataProp": "type", "sWidth": "90px"},
    {"sTitle": "Directors", "mDataProp": "directors", fnRender: render('directors')},
    {"sTitle": "Added", "mDataProp": "added", "sWidth": "170px"}
  ];

  var load = function() {
    console.log("LOAD");
    $.get(
      buildUrl(),
      {},
      function(data) {
        console.log(data);
        if(data && data.constructor == Array) {
          $(tagIds.table).html('<table cellpadding="0" cellspacing="0" border="0" class="display" id="'+ tableId + '"></table>');
          $('#' + tableId).dataTable( {
            "aaData": data,
            "aoColumns": structure,
            bJQueryUI: true,
            sPaginationType: "full_numbers",
            "iDisplayLength": -1, 
            "bLengthChange": false,
            "bPaginate": false,
            "aaSorting": [[ 0, "desc" ]]
          });
        }
      },
      "json"
    );
  };

  $("button").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter");
    if (filter == "clear")
      clearFilters();
    else
      removeFilter(filter, _this.text().toLowerCase(), !cumulativeFilterFlag);
    load();
  });

  $(".link").live("click", function() {
    var _this = $(this);
    addFilter(_this.attr("filter"), _this.text().toLowerCase(), !cumulativeFilterFlag);
    load();
  });
  
  $(':checkbox').iphoneStyle({
    onChange: function(button, value) {
      cumulativeFilterFlag = value;
      console.log(value);
    }
  });
  
  var $sidebar = $("#sidebar"),
        $window = $(window),
        offset = $sidebar.offset();

  $window.scroll(function() {
    if ($window.scrollTop() > offset.top)
      $sidebar.addClass('fixed');
    else
      $sidebar.removeClass('fixed');
  });

  load();
});