$(document).ready(function() {
  
//$.fn.dataTableExt.sErrMode = 'throw';
  $.fn.dataTableExt.afnFiltering = [];
  $.fn.dataTableExt.afnFiltering.push(
    function(oSettings, aData, iDataIndex ) {
      var found = true;
      //console.log('query: ', query);
      for (var key in allData[iDataIndex])
        found = found && fnFilter(key, allData[iDataIndex][key]);
      return found;
    }
  );
    
  var fnFilter = function(key, value) {
    var match = true;
    if ($.inArray(key, mongo.and)!=-1 && query[key] != null)
      match = _.intersection(query[key], value).length == query[key].length;
    else if ($.inArray(key, mongo.or)!=-1 && query[key] != null)
      match = $.inArray(value, query[key]) != -1;
    //console.log('filter [', key, '] ', value, ' == ', query[key], ' : ', match);
    return match;
  }
  
  var calculateFilteredLists = function() {
    var fitleredLists = [];
    $.each(query, function(key, value) {
      fitleredLists[key] = _.filter(allData, _fnFilter, key);
    });
    return fitleredLists;
  }

  var _fnFilter = function(row, currentKey) {
    var found = true;
    for (var key in row)
      if (currentKey != key)
        found = found && fnFilter(key, row[key]);    
    return found;
  }
  
  var mongoDb = {
    host: "https://api.mongohq.com/databases",
    dbName: "Movies",
    collectionName: "watched",
    token: "cqebxoajr6eueff6j7ax"
  };

  var tableId = "dataTable";
  
  var mongo = {
    and: ['cast', 'directors', 'genres'],
    or: ['added', 'imdb_id', 'imdb_rating', 'mpaa_rating', 'position', 'rating', 'released', 'rotten_audience_score', 'rotten_critics_score', 'rotten_id', 'runtime', 'title', 'type', 'year']
  };
  
  var types = {
    string: ['added', 'cast', 'directors', 'genres', 'imdb_id', 'mpaa_rating', 'released', 'title', 'type'],
    integer: ['imdb_rating', 'position', 'rating', 'rotten_audience_score', 'rotten_critics_score', 'rotten_id', 'runtime', 'year']
  };

  var query = {
    added: null,
    cast: null,
    directors: null,
    genres: null,
    imdb_id: null,
    imdb_rating: null,
    mpaa_rating:  null,
    position: null,
    rating: null,
    released: null,
    runtime: null,
    rotten_audience_score: null,
    rotten_critics_score: null,
    rotten_id: null,
    title: null,
    type: null,
    year: null
  };
  
  var tagIds = {
    filters: "#filters",
    table: "#table",
    progressBar: "#progressbar"
  };
  var progress = $(tagIds.progressBar);
  
  var headerSliding = false;
  
  var settings = {
    resetAllFiltersFlag: false,
    cumulativeFiltersFlag: false,
    showFiltersFlag: false,
    slideHeader: false,
    limitPerRequest: 99,
    maxLimit: 20,
    allOptionsValue: '*'
    
  };

  var buildUrl = function(skip, limit) {
    url = [mongoDb.host, mongoDb.dbName, "collections", mongoDb.collectionName, "documents"].join("/") + "?" + buildQs(skip, limit)
    console.log(url);
    return url;
  }

  var buildQs = function(skip, limit) {
    var q = [],
        qs = [];
    qs.push("_apikey=" + mongoDb.token);
    qs.push("skip=" + skip);
    qs.push("limit=" + limit);
    $.each(query, function(key, value) {
      if (value != null && value.length > 0) {
        var values = [];
        $.each(value, function(key, value) {
          if($.inArray(key, types.string) != -1)
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
  
  var resetFilter = function(key) {
    console.log('resetFilter: ' + key);
    query[key] = null;
    buildFilterButttons();
  }
  
  var setFilter = function(key, value) {
    console.log("setFilter: " + key + "/" + value);
    if($.inArray(key, types.integer) != -1)
      value = parseInt(value);
    query[key] = [value];
    buildFilterButttons();
  }

  var addFilter = function(key, value, resetAll) {
    console.log("addFilter: " + key + "/" + value + ", " + resetAll);
    if($.inArray(key, types.integer) != -1)
      value = parseInt(value);
    if (resetAll)
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
      if (query[key] != null && query[key] == 0)
        query[key] = null;
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
      if (value != null && value.length > 0) {
        var $filter = $('<div class="filter">').append($('<label>').text(cap(key))),
          $list = $('<div>');
        $.each(value, function(index, item) {
          $list.append($('<button filter="' + key +'" value="' + item + '">').text(cap(item)));
          count++;
        });
        $filters.append($filter.append($list));
      }
    });
    if (count>0) {
      $filters.append($('<div class="fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix" style="padding:5px;">')
              .append($('<button style="width: 100%;" filter="clear">').text("Clear")));
      $("button").button();
      $filters.show();
    } else
      $filters.hide();
  }

  var clearFilters = function () {
    console.log("clearFilters:");
    $(tagIds.filters).hide();
    for(var key in query)
      query[key] = null;
  }

  var render = function(filter) {
    return function(obj) {
      var filterValue = obj.aData[filter],
          strArray = [];
      if (filterValue == null)
        return '_';
      if(filterValue.constructor != Array)
        filterValue = [filterValue];
      filterValue.forEach(function(value, index, array) {
        console.log(filter);
        if (filter == 'imdb_id' || filter == 'rotten_id') {
          var url = filter == 'imdb_id' ? 'http://www.imdb.com/title/': 'http://www.rottentomatoes.com/m/';
          var img = value == -1 ? '': '<img src="movies/external.png" style="width: 30px">';
          strArray.push('<span filter="' + filter +'" value="' + value + '"><a href="' + url + value + '/" target="_blank">' + img + '</a></span>');
        } else
          strArray.push('<span class="link" filter="' + filter +'" value="' + value + '">' + cap(value) + '</span>');
      });
      return strArray.join(', ');
    }
  }
  
  var structure = [
    {"sTitle": "Title", "mDataProp": "title", "sWidth": "150px"},
    {"sTitle": "Year", "mDataProp": "year", fnRender: render('year'), "sWidth": "65px"},
    {"sTitle": "Rating", "mDataProp": "rating", fnRender: render('rating'), "sWidth": "65px"},
    {"sTitle": "RT", "mDataProp": "rotten_critics_score", fnRender: render('rotten_critics_score'), "sWidth": "90px"},
    {"sTitle": "IMDB", "mDataProp": "imdb_rating", fnRender: render('imdb_rating'), "sWidth": "65px"},
    {"sTitle": "Runtime", "mDataProp": "runtime", fnRender: render('runtime'), "sWidth": "80px"},
    {"sTitle": "Genres", "mDataProp": "genres", fnRender: render('genres')},
    //{"sTitle": "Cast", "mDataProp": "cast", fnRender: render('cast')},
    {"sTitle": "Directors", "mDataProp": "directors", fnRender: render('directors')},
    //{"sTitle": "RT Aud", "mDataProp": "rotten_audience_score", fnRender: render('rotten_audience_score'), "sWidth": "90px"},
    {"sTitle": "MPAA", "mDataProp": "mpaa_rating", fnRender: render('mpaa_rating'), "sWidth": "70px" },
    //{"sTitle": "Added", "mDataProp": "added", "sWidth": "170px"},
    {"sTitle": "Title Type", "mDataProp": "type", "sWidth": "90px", fnRender: render('type')},
    {"sTitle": "IMDB", "mDataProp": "imdb_id", "sWidth": "30px", fnRender: render('imdb_id')},
    {"sTitle": "RT", "mDataProp": "rotten_id", "sWidth": "30px", fnRender: render('rotten_id')},
    //{"sTitle": "Released", "mDataProp": "released", "sWidth": "170px"},
    {"sTitle": "P", "mDataProp": "position", "sWidth": "75px"}
  ];
  
  var allData = [];
  
  var multiload = function(skip, limit) {
    console.log('LOAD skip: ', skip, ' limit: ', limit);
    $.get(
      buildUrl(skip, limit+1),
      {},
      function(data) {
        if (data != null) {
          var more = data.length > limit;
          if (more)
            data = _.initial(data);
          allData = _.union(allData, data);
          console.log(allData.length);
          if(allData.length >= settings.maxLimit) {
            allData = _.first(allData, settings.maxLimit);
            more = false;
          }
          if (more) {
            multiload(skip+limit, limit);
            progress.progressbar('value', progress.progressbar('value')+10);
          } else {
            for(var i=progress.progressbar('value'); i<100; i++)
              progress.progressbar('value', 100);
            buildTable(allData);
            progress.fadeOut(1000);
          }
        }
      },
      "json"
    );
  };
  
  var load = function() {
    console.log(progress);
    progress.show();
    progress.progressbar({value: 10});
    var limit = settings.maxLimit < settings.limitPerRequest? settings.maxLimit: settings.limitPerRequest;
    multiload(0, limit);
  };
  
  var buildTable = function (data) {
    console.log(data);
    if(data && data.constructor == Array) {
      $(tagIds.table).html('<table cellpadding="0" cellspacing="0" border="0" class="display" id="'+ tableId + '"></table>');
      oTable = $('#' + tableId).dataTable( {
        "aaData": data,
        "aoColumns": structure,
        bJQueryUI: true,
        sPaginationType: "full_numbers",
        "iDisplayLength": -1, 
        "bLengthChange": false,
        "bPaginate": false,
        "aaSorting": [[ 0, "desc" ]],
        "oLanguage": {  
          "sZeroRecords": "No records to display"
        },
        sDom: 'lfrtip'
      });
      refreshListFilters();
    }
  }
  
  function fnCreateSelect(key, values) {
    var hiddenClass = settings.showFiltersFlag? '': ' hidden';
    var $select = $('<select filter="' + key + '" class="filterOptions' + hiddenClass + '">');
    $select.append($('<option value="' + settings.allOptionsValue + '">All</option>'));
    for (var i=0 ; i<values.length ; i++) {
      $select.append($('<option value="' + values[i] + '">' + cap(values[i]) + '</option>'));
    }
    //console.log(query[key], settings.resetAllFiltersFlag, settings.cumulativeFiltersFlag)
    if (query[key] != null) {
      if(settings.resetAllFiltersFlag || !settings.cumulativeFiltersFlag)
        $select.val(query[key]);
    }
    return $select;
  }
  
  var refreshListFilters = function(doNotFilter) {
    var filteredLists = calculateFilteredLists();
    doNotFilter = doNotFilter || '';
    $("thead th").each( function (i) {
      var filter = structure[i].mDataProp;
      if(doNotFilter != filter) {
        $this = $(this);
        $this.children('select').remove();
        var $select = fnCreateSelect(filter, _.sortBy(_.unique(_.flatten(_.pluck(filteredLists[filter], filter))), function(v){return v;}));
        $select.change( function () {
          var value = $(this).val();
          if (value == settings.allOptionsValue)
            resetFilter(filter);
          else if(settings.resetAllFiltersFlag) {
            clearFilters();
            setFilter(filter, value);
          } else if(settings.cumulativeFiltersFlag) {
            addFilter(filter, value);
            $(this).val('');
          } else {
            setFilter(filter, value);
          }
          refreshListFilters(filter);
          oTable.fnFilter('');
        });
        $this.append($select);
      }
    });
  }
  
  $("button").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter");
    filter == "clear"? clearFilters(): removeFilter(filter, _this.attr("value"));
    oTable.fnFilter('');
    refreshListFilters();
  });
  
  $(".link").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter"),
      value = _this.attr("value");
    if(settings.resetAllFiltersFlag) {
      clearFilters();
      setFilter(filter, value);
    } else  if(settings.cumulativeFiltersFlag) {
      addFilter(filter, value);
    } else {
      setFilter(filter, value);
    }
    oTable.fnFilter('');
    refreshListFilters();
  });
  
  var resetAllButton = $('#resetAllFilters').iphoneStyle({
    onChange: function(button, value) {
      settings.resetAllFiltersFlag = value;
      if(value) {
        settings.cumulativeFiltersFlag = false;
        cumulitiveButton.attr('checked', false).iphoneStyle("refresh");
        cumulitiveButton.attr('disabled', 'disabled').iphoneStyle("refresh");
      } else
        cumulitiveButton.removeAttr('disabled').iphoneStyle("refresh");
    }
  });
  
  var cumulitiveButton = $('#cumulativeFilters').iphoneStyle({
    onChange: function(button, value) {
      settings.cumulativeFiltersFlag = value;
      if (value)
        refreshListFilters();
    }
  });
  
  $('#showFilters').iphoneStyle({
    onChange: function(button, value) {
      settings.showFiltersFlag = value;
      $('.filterOptions').toggle();
    }
  });
  
  $('#slideHeader').iphoneStyle({
    onChange: function(button, value) {
      settings.slideHeader = value;
      if (settings.slideHeader)
        enableHeaderSliding()
      else
        disableHeaderSliding()
    }
  });
  
  var $sidebar = $("#sidebar"),
        $window = $(window),
        offset = $sidebar.offset();
  
  var enableHeaderSliding = function() {
    if(!headerSliding && window.scrollY > 100) {
      $('thead').clone().addClass('temp').appendTo($('thead').parent());
      $('thead').not('.temp').addClass('fixed2');
      return headerSliding = true;
    }
    return false;
  }
  
  var disableHeaderSliding = function() {
    if (headerSliding && window.scrollY < 100) {
      $('thead').removeClass('fixed2');
      $('.temp').remove();
      return headerSliding = false;
    }
    return false;
  }
  
  $(window).scroll(function (event) {
    if (settings.slideHeader)
      enableHeaderSliding() || disableHeaderSliding();
  });

  //$("#back-top").hide();
	
  $(function () {
    $(window).scroll(0);
    $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
                    $('#back-top').fadeIn();
            } else {
                    $('#back-top').fadeOut();
            }
    });

    // scroll body to 0px on click
    $('#back-top a').click(function () {
            $('body,html').animate({
                    scrollTop: 0
            }, 800);
            return false;
    });
  });


  load();
});