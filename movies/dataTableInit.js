$(document).ready(function() {
  
//$.fn.dataTableExt.sErrMode = 'throw';
  $.fn.dataTableExt.afnFiltering = [];
  $.fn.dataTableExt.afnFiltering.push(
    function(oSettings, aData, iDataIndex ) {
      var found = true;
      for (var key in allData[iDataIndex])
        found = found && filter(key, allData[iDataIndex][key]);
      return found;
    }
  );
    
  var filter = function(key, value) {
    var match = true;
    if ($.inArray(key, mongo.and)!=-1 && query[key] != null)
      match = _.intersection(query[key], value).length == query[key].length;
    else if ($.inArray(key, mongo.or)!=-1 && query[key] != null)
      match = $.inArray(value, query[key]) != -1;
    //console.log('filter [', key, '] ', value, ' == ', query[key], ' : ', match);
    return match;
  }
  
  var mongoDb = {
    host: "https://api.mongohq.com/databases",
    dbName: "Movies",
    collectionName: "watched",
    token: "cqebxoajr6eueff6j7ax"
  };

  var tableId = "dataTable";
  
  var mongo = {
    and: ['directors', 'genres'],
    or: ['title', 'year', 'rating', 'runtime', 'rotten_critics_score', 'position', 'type']
  };
  
  var types = {
    string: ['directors', 'genres', 'title', 'type'],
    integer: ['year', 'rating', 'runtime', 'rotten_critics_score', 'position']
  };

  var query = {
    directors: null,
    genres: null,
    rating: null,
    runtime: null,
    title: null,
    type: null,
    year: null
  };
  
  var tagIds = {
    filters: "#filters",
    table: "#table"
  };
  var progress = $("#progressbar");
  
  var headerSliding = false;
  
  var settings = {
    cumulativeFiltersFlag: false,
    showFiltersFlag: false,
    slideHeader: false
  };
  
  var globalLimit = 99;

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
        strArray.push('<span class="link" filter="' + filter +'" value="' + value + '">' + cap(value) + '</span>');
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
    //{"sTitle": "RT score", "mDataProp": "rotten_critics_score", fnRender: render('rotten_critics_score')},
    {"sTitle": "Added", "mDataProp": "added", "sWidth": "170px"},
  ];
  
  
  function fnCreateSelect( key, values ) {
    var hiddenClass = settings.showFiltersFlag? '': ' hidden';
    var $select = $('<select filter="' + key + '" class="filterOptions' + hiddenClass + '">');
    $select.append($('<option value="">All</option>'));
    for (var i=0 ; i<values.length ; i++) {
      $select.append($('<option value="' + values[i] + '">' + cap(values[i]) + '</option>'));
    }
    if (query[key] != null && ($.inArray(key, mongo.or) != -1 || ($.inArray(key, mongo.and) != -1 && query[key].length == 1)))
      $select.val(query[key]);
    return $select;
  };

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
    multiload(0, globalLimit);
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

      $("thead th").each( function (i) {
        var key = structure[i].mDataProp;
        $(this).append(fnCreateSelect(key, _.sortBy(_.unique(_.flatten(_.pluck(data, key))), function(v){return v;})));
        $('select', this).change( function () {
          var value = $(this).val();
          if (value != '')
            setFilter(key, value);
          else
            resetFilter(key);
          oTable.fnFilter('');
        } );
      } );
    }
  }  
  
  $("button").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter");
    filter == "clear"? clearFilters(): removeFilter(filter, _this.attr("value"), !settings.cumulativeFiltersFlag);
    oTable.fnFilter('');
  });

  $(".link").live("click", function() {
    var _this = $(this);
    addFilter(_this.attr("filter"), _this.attr("value"), !settings.cumulativeFiltersFlag);
    oTable.fnFilter('');
  });
  
  $('#cumulativeFilters').iphoneStyle({
    onChange: function(button, value) {
      settings.cumulativeFiltersFlag = value;
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

  $window.scroll(function() {
    if ($window.scrollTop() > offset.top)
      $sidebar.addClass('fixed');
    else
      $sidebar.removeClass('fixed');
  });
  
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

  load();
});