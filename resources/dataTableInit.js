$(document).ready(function() {
  
  var timers = {};
  var counter = 0;
  timers[counter++] = new Date();
  
  var settings = {
    resetAllFiltersFlag: false,
    cumulativeFiltersFlag: false,
    showFiltersFlag: false,
    previewAvatars: true,
    slideHeader: false,
    limitPerRequest: 99,
    //limitPerRequest: 399,
    //limitPerRequest: 1999,
    maxLimit: 2000,
    //maxLimit: 90,
    allOptionsValue: '*',
    maxVisibleCast: 2,
    maxVisibleGenres: 4,
    maxVisibleDirectors: 1,
    empty: '_'
  };
  
  //$.fn.dataTableExt.sErrMode = 'throw';
  $.fn.dataTableExt.afnFiltering = [];
  
  $.fn.dataTableExt.oSort['RT-asc']  = function(a, b) {
    var x = $(a).text(),
      y = $(b).text();
    x = x == settings.empty ? -1 : parseFloat(x);
    y = y == settings.empty ? -1 : parseFloat(y);
    return ((x < y) ? -1 : ((x > y) ?  1 : 0));
  };
  
  $.fn.dataTableExt.oSort['RT-desc']  = function(a, b) {
    var x = $(a).text(),
      y = $(b).text();
    x = x == settings.empty ? -1 : parseFloat(x);
    y = y == settings.empty ? -1 : parseFloat(y);
    return ((x > y) ? -1 : ((x < y) ?  1 : 0));
  };
  
  var afnFiltering = function() {
    return function(oSettings, aData, iDataIndex ) {
        var found = true;
        for (var key in allData[iDataIndex])
          if(!fnFilter(key, allData[iDataIndex][key])) {
            found = false;
            break;
          }
          //found = found && fnFilter(key, allData[iDataIndex][key]);
        return found;
      }
      /*var found = true;
      var interval = allData.length/5;
      var count = 0;
      for (var key in enabled) {
        //console.log(count % interval);
        if(!fnFilter(key, allData[iDataIndex][key])) {
          found = false;
          break;
        }
        //if(count++ % interval == 0)
        //  progress.step(5);
      }
      console.log('iDataIndex: ', iDataIndex, found);
      return found;
    }*/
  }
    
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
  
  var topCallback = function(data, map) {
    var field = map.field;
    var html = [];
    top[field] = data;
    if (field == 'top')
      html.push('<div><span class="link" filter="' + field + '" value="all" title="' + name + '">All</span></div>');
    $.each(data, function(index, value) {
      var name = value._id;
      var count = value.count;
      var rating = value.rating.toString();
      if (rating.length == 3)
        rating += '0';
      else if (rating.length == 1)
        rating += '.00';
      var name2 = name;
      if (name2.length >= 19)
        name2 = name2.substring(0, 19-4) + '...';
      var preview = (field=='cast' || field=='directors')? ' preview' : '';
      if (field == 'top') {
        var names = {
          0: 'Unranked',
          10: 'Top 10',
          20: '11 to 20',
          30: '21 to 30',
          40: '31 to 40',
          50: '41 to 50'
        };
        if(name != 0)
          html.push('<div><span class="link" filter="' + field + '" value="' + name + '" title="' + name + '">' + names[name] + '</span></div>');
      } else
        html.push('<div><span class="link' + preview + '" filter="' + field + '" value="' + name + '" title="' + name + '">' + cap(name2) + '</span><span style="float: right; padding-right: 5px">' + count + ' | ' + rating + '</span></div>');
    });
    $(tagIds[field]).html(html.join(''));
    progress.step(14);
  };
  
  var top = {};
  
  var loadTop = function(field, count, sort, direction) {
    var query = {
      method: 'top',
      count: count,
      field: field,
      sort: sort,
      direction: direction
    };
    API.call(query, topCallback, {field: field});
  }

  var tableId = "dataTable";
  
  var enabled = ['title', 'year', 'rotten_critics_score', 'imdb_rating', 'genres', 'directors', 'runtime', 'imdb_id', 'rotten_id', 'position'];
  
  var mongo = {
    and: ['cast', 'directors', 'genres'],
    or: ['added', 'imdb_id', 'imdb_rating', 'mpaa_rating', 'position', 'rating', 'released', 'rotten_audience_score', 'rotten_critics_score', 'rotten_id', 'runtime', 'title', 'type', 'year', 'top']
  };
  
  var types = {
    string: ['added', 'cast', 'directors', 'genres', 'imdb_id', 'mpaa_rating', 'released', 'title', 'type'],
    integer: ['imdb_rating', 'position', 'rating', 'rotten_audience_score', 'rotten_critics_score', 'rotten_id', 'runtime', 'year', 'top']
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
    top: null,
    type: null,
    year: null
  };
  
  var tagIds = {
    filters: "#filters",
    table: "#table",
    progressBar: "#progressbar",
    cast: "#cast",
    directors: "#directors",
    genres: "#genres",
    year: "#years",
    rating: "#ratings",
    top: "#tops"
  };
  
  var fields = {
    cast: 8,
    directors: 2,
    genres: 1,
    year: 1,
    rating: 1,
    top: 1
  };

  
  $('.link2').live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter"),
      sort = _this.attr("sort");
      otherSort = sort == 'count' ? 'rating' : 'count';
    var clicked = _this.hasClass('clicked');
    _this.addClass('clicked');
    $('.link2' + '[filter="' + filter + '"][sort!="' + sort + '"]').toggleClass('clicked', false);
    var $nodes = {
      def: $('.link2' + '[filter="' + filter + '"][sort="default"]'),
      count: $('.link2' + '[filter="' + filter + '"][sort="count"]'),
      rating: $('.link2' + '[filter="' + filter + '"][sort="rating"]')
    };
    
    var oldDirection = parseInt(_this.attr('direction'));
    var direction = oldDirection == -1 ? 1 : -1;
    _this.attr('direction', direction)
    if (sort == 'default')
      _this.html(direction == 1 ? '&#9650;' : '&#9660;');
    if (sort == 'default') {
      $nodes.count.attr('direction', 1);
      $nodes.rating.attr('direction', 1);
    } else if (sort == 'count') {
      $nodes.def.attr('direction', -1);
      if (filter == 'year' || filter == 'rating')
        $nodes.def.attr('direction', 1);
      $nodes.rating.attr('direction', 1);
    } else if (sort == 'rating') {
      $nodes.def.attr('direction', -1);
      if (filter == 'year' || filter == 'rating')
        $nodes.def.attr('direction', 1);
      $nodes.count.attr('direction', 1);
    }
    
    loadTop(filter, fields[filter], sort, direction);
  });
  

  var buildUrl = function(skip, limit) {
    url = [mongoDb.host, mongoDb.dbName, "collections", mongoDb.collectionName, "documents"].join("/") + "?" + buildQs(skip, limit)
    //console.log(url);
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
    //console.log('resetFilter: ' + key);
    query[key] = null;
    buildFilterButttons();
  }
  
  var setFilter = function(key, value) {
    //console.log("setFilter: " + key + "/" + value);
    if($.inArray(key, types.integer) != -1)
      value = parseInt(value);
    query[key] = [value];
    buildFilterButttons();
  }

  var addFilter = function(key, value, resetAll) {
    //console.log("addFilter: " + key + "/" + value + ", " + resetAll);
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
    //console.log("removeFilter: " + key + "/" + value);
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
    //console.log("buildFilterButttons");
    var $filters = $(tagIds.filters).html(""),
        count = 0;
    $.each(query, function(key, value) {
      if (value != null && value.length > 0) {
        var $filter = $('<div class="filter">').append($('<label>').text(cap(key))),
          $list = $('<div>');
        $.each(value, function(index, item) {
          $list.append($('<button class="filter_button" filter="' + key +'" value="' + item + '">').text(cap(item)));
          count++;
        });
        $filters.append($filter.append($list));
      }
    });
    if (count>0) {
      $filters.append($('<div class="fg-toolbar ui-toolbar ui-widget-header ui-corner-bl ui-corner-br ui-helper-clearfix" style="padding:5px;">')
              .append($('<button class="filter_button" style="width: 100%;" filter="clear">').text("Clear")));
      $("button").button();
      $filters.show();
    } else {
      $filters.hide();
    }
  }

  var clearFilters = function () {
    //console.log("clearFilters:");
    $(tagIds.filters).hide().html('');
    for(var key in query)
      query[key] = null;
  }//84204649

  var render = function(filter) {
    return function(obj) {
      var filterValue = obj.aData[filter],
          strArray = [];
      if (filterValue == null)
        return '_';
      if(filterValue.constructor != Array)
        filterValue = [filterValue];
      var longList = false;
      var strArrayExtra = [];
      $.each(filterValue, function(index, value) {
        if (filter == 'title') {
          strArray.push('<span class="titlehover" moviedb_id="' + obj.aData['moviedb_id'] +'">' + value + '</span>');
        } else if (filter == 'imdb_id' || filter == 'rotten_id') {
          var url = filter == 'imdb_id' ? 'http://www.imdb.com/title/': 'http://www.rottentomatoes.com/m/';
          strArray.push('<a href="' + url + value + '/" target="_blank"><span filter="' + filter +'" value="' + value + '"></span></a>');
        } else if (filter == 'added') {
          strArray.push(new Date(value*1000).toLocaleDateString());
        } else if (filter == 'imdb_rating' || filter == 'rotten_critics_score') {
          var url = filter == 'imdb_rating' ? ('http://www.imdb.com/title/' + obj.aData['imdb_id']): ('http://www.rottentomatoes.com/m/' + obj.aData['rotten_id']);
          if (value == -1)
            value = settings.empty;
          var title =  filter == 'imdb_rating' ? 'IMDb' : 'Rotten Tomatoes';
          strArray.push('<div class="hover"><a style="border-bottom: 1px dotted violet; text-decoration: none;" href="' + url + '/" target="_blank"><span title="View on ' + title + '" >' + cap(value) + '</span></a></div>');
        } else {
          var preview = '', style = '';
          if (filter == 'rating')
            style = ' style="color: violet; font-weight:bold;"';
          if (filter == 'cast' && index == settings.maxVisibleCast)
            longList = true;
          else if (filter == 'directors' && index == settings.maxVisibleDirectors)
            longList = true;
          else if (filter == 'genres' && index == settings.maxVisibleGenres)
            longList = true;
          if (filter == 'cast' || filter == 'directors')
            preview = ' preview';
          var text = cap(value);
          //if (filter == 'directors' || filter == 'cast')
            //text = text.replace(/\s+/g, '_');
          var valueHtml = '<span class="link' + preview + '" filter="' + filter +'" value="' + value + '" ' + style + '>' + text + '</span>';
          if (longList)
            strArrayExtra.push(valueHtml);
          else
            strArray.push(valueHtml);
        }
      });
      var allHtml = [];
      allHtml.push(strArray.join(', '));
      if (longList) {
        allHtml.push('<div style="display:none;">');
        allHtml.push(', ' + strArrayExtra.join(', '));
        allHtml.push('</div>');
        allHtml.push('<span class="more" state="hidden" style="padding-left: 5px">&rarr;</span>');
      }
      return allHtml.join('');
    }
  }
  
  var structure = [
    {"sTitle": "Title", "mDataProp": "title", "sWidth": "100px", fnRender: render('title')},
    {"sTitle": "Year", "mDataProp": "year", fnRender: render('year'), "sWidth": "65px"},
    {"sTitle": String.fromCharCode(0x2764), "mDataProp": "rating", fnRender: render('rating'), "sWidth": "10px"},
    //{"sTitle": "RT " + String.fromCharCode(0x27A6), "mDataProp": "rotten_critics_score", fnRender: render('rotten_critics_score'), "sWidth": "79px", "sType": "RT"},
    {"sTitle": "RT", "mDataProp": "rotten_critics_score", fnRender: render('rotten_critics_score'), "sWidth": "60px", "sType": "RT"},
    //{"sTitle": "IMDB " + String.fromCharCode(0x27A6), "mDataProp": "imdb_rating", fnRender: render('imdb_rating'), "sWidth": "100px"},
    {"sTitle": "IMDB", "mDataProp": "imdb_rating", fnRender: render('imdb_rating'), "sWidth": "80px"},
    {"sTitle": "Cast", "mDataProp": "cast", fnRender: render('cast')},
    {"sTitle": "Directors", "mDataProp": "directors", fnRender: render('directors')},
    {"sTitle": "Genres", "mDataProp": "genres", fnRender: render('genres'), "sWidth": "100px"},
    {"sTitle": "Min", "mDataProp": "runtime", "sWidth": "10px"},
    //{"sTitle": "RT Aud", "mDataProp": "rotten_audience_score", fnRender: render('rotten_audience_score'), "sWidth": "90px"},
    //{"sTitle": "MPAA", "mDataProp": "mpaa_rating", fnRender: render('mpaa_rating'), "sWidth": "70px" },
    //{"sTitle": "Title Type", "mDataProp": "type", fnRender: render('type'), "sWidth": "70px" },
    {"sTitle": "IMDB", "mDataProp": "imdb_id", "sWidth": "30px", fnRender: render('imdb_id'), bVisible: false},
    {"sTitle": "RT", "mDataProp": "rotten_id", "sWidth": "30px", fnRender: render('rotten_id'), bVisible: false},
    {"sTitle": "MOVIEDB", "mDataProp": "moviedb_id", "sWidth": "30px", fnRender: render('moviedb_id'), bVisible: false},
    {"sTitle": "Top", "mDataProp": "top", "sWidth": "30px", fnRender: render('top'), bVisible: false},
    //{"sTitle": "Released", "mDataProp": "released", "sWidth": "170px"},
    //{"sTitle": "P", "mDataProp": "position", "sWidth": "75px"}
    {"sTitle": "Added", "mDataProp": "added", "sWidth": "100px", fnRender: render('added')}
  ];
  
  var allData = [];
  
  var loadDataCallback = function(data, map) {
    var skip = map.skip;
    var limit = map.limit;
    
    if (data != null) {
      var more = data.length > limit;
      if (more)
        data = _.initial(data);
      allData = _.union(allData, data);
      if(allData.length >= settings.maxLimit) {
        allData = _.first(allData, settings.maxLimit);
        more = false;
      }
      if (more) {
        multiload2(skip+limit, limit);
        progress.step(10);
      } else {
        buildTable(allData);
        $.fn.dataTableExt.afnFiltering.push(afnFiltering());
        progress.stop();
      }
    }
  }
  
  var multiload2 = function(skip, limit) {
    //console.log('Multiload2 skip: ', skip, ' limit: ', limit);
    var query = {
      method: 'list',
      skip: skip,
      limit: limit+1
    }
    API.call(query, loadDataCallback, {skip: skip, limit: limit});
  }
  
   var load = function() {
    progress.start();
    var limit = settings.maxLimit < settings.limitPerRequest? settings.maxLimit: settings.limitPerRequest;
    multiload2(0, limit);
  }
  
  var loadAllMovies = function() {
    API.call({method: 'all'}, function(data) {
      if (data != null) {
        allData = _.union(allData, data);
        buildTable(allData);
        $.fn.dataTableExt.afnFiltering.push(afnFiltering());
        progress.stop();
      }
    });
  }
  
  var loadAllTops = function() {
    loadTop('cast', fields.cast, 'count', -1);
    loadTop('directors', fields.directors,'count', -1);
    loadTop('genres', fields.genres,'count', -1);
    loadTop('year', fields.year,'default', -1);
    loadTop('rating', fields.rating,'default', -1);
    loadTop('top', fields.top,'default', 1);
  }
  
  var progress = {
    $: $(tagIds.progressBar),
    start: function() {
      progress.$[0].style.display = 'block';
      progress.$.progressbar({value: 4});
    },
    step: function(step) {
      progress.$.progressbar('value', progress.$.progressbar('value') + step);
    },
    stop: function() {
      for(var i = this.$.progressbar('value'); i<100; i++)
        progress.$.progressbar('value', i);
      progress.$.fadeOut(1000);
    }
  }
  
  var buildTable = function (data) {
    //timers[counter++] = new Date();
    //console.log(timers);
    //console.log(timers[1].getTime()-timers[0].getTime());
    //console.log(data);
    if(data && data.constructor == Array) {
      $(tagIds.table).html('<table cellpadding="0" cellspacing="0" border="0" class="display" id="'+ tableId + '"></table>');
      oTable = $('#' + tableId).dataTable( {
        "aaData": data,
        "aoColumns": structure,
        bJQueryUI: true,
        sPaginationType: "full_numbers",
        "iDisplayLength": 25,
        "aLengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "bLengthChange": true,
        "bPaginate": true,
        "aaSorting": [[13, "desc"]],
        "oLanguage": {
          "sZeroRecords": "No records to display"
        }
      });
      /*var keys = new KeyTable( {
        "table": document.getElementById(tableId),
        "datatable": oTable,
        'focus': []
      });*/
      //refreshListFilters();
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
  
  $(document).keyup(function (event) {
    $src = $(event.srcElement);
    if (!$src.is("input")) {
      if(event.keyCode == 72) {
        var dialog = $('#dialog-message');
        dialog.dialog( "isOpen" )? dialog.dialog('close'): dialog.dialog('open');
      } else if (event.keyCode == 37 || event.keyCode == 80) { // p or left-arrow-key
        oTable.fnPageChange( 'previous' );
      } else if (event.keyCode == 39 || event.keyCode == 78) { // n or right-arrow-key
        oTable.fnPageChange( 'next' );
      } else if (event.keyCode == 76) { // l
        oTable.fnPageChange( 'last' );
      } else if (event.keyCode == 70 || event.keyCode == 48) { // f or 1
        oTable.fnPageChange( 'first' );
      } else if (event.keyCode > 48 && event.keyCode <= 57) {
        oTable.fnPageChange(event.keyCode-49);
      } else if (event.keyCode == 27 || event.keyCode == 67) { // esc or c
        var dialog = $('#dialog-message');
        if(dialog.dialog('isOpen')) {
          dialog.dialog('close');
        } else {
          clearFilters();
          doFilter();
        }
      } else if (event.keyCode == 83) {
        $('#dataTable_filter input').focus().select();
      } 
    } else {
      if (event.keyCode == 27) {
        $('#dataTable_filter input').blur();
      }
    }
  });
  
  $( "#dialog-message" ).dialog({
    modal: true,
    closeOnEscape: false,
    autoOpen: false,
    draggable: false,
    resizable: false,
    buttons: {
      Ok: function() {
        $( this ).dialog( "close" );
      }
    }
  });
  
  var refreshListFilters = function(doNotFilter) {
    var filteredLists = calculateFilteredLists();
    doNotFilter = doNotFilter || '';
    $("thead th").each( function (i) {
      var filter = structure[i].mDataProp;
      if(doNotFilter != filter && _.indexOf(['year', 'rating', 'rotten_critics_score', 'imdb_rating', 'directors', 'genres'], filter) != -1) {
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
          doFilter(filter);
        });
        $this.append($select);
      }
    });
  }
  
  var doFilter = function(filter) {
    progress.start();
    oTable.fnFilter('');
    if(settings.showFiltersFlag)
      refreshListFilters(filter);
    progress.stop();
  }
  
  $('.hover').live('mouseenter', function() {
    $(this).children('.external').remove();
    $(this).append('<span class="external"> ' + String.fromCharCode(0x27A6) + '</span>');
  }).live('mouseleave', function() {
    $(this).children('.external').remove();
  });
  
  var count = 0;
 $('.titlehover').hoverIntent({
    sensitivity: 3, // number = sensitivity threshold (must be 1 or higher)
    interval: 200, // number = milliseconds for onMouseOver polling interval
    timeout: 0, // number = milliseconds delay before onMouseOut
    over: function() {
      var id = $(this).attr('moviedb_id');
      if (settings.previewAvatars) {
        var offset_pop = $(this).offset();
        http://api.themoviedb.org/3/search/person?api_key=a56c4c9722f90923979c4ed41b5c715f&query=Pete%20Travis
        var data = {
          api_key: 'a56c4c9722f90923979c4ed41b5c715f'
        };
        $.ajax({
          dataType: 'json',
          success: function(data, status) {
            if (data && data.poster_path) {
              //https://d3gtl9l2a4fn1j.cloudfront.net/t/p/w185/w8zJQuN7tzlm6FY9mfGKihxp3Cb.jpg
              var src = 'https://image.tmdb.org/t/p/w185' + data.poster_path;
              $("#avatar").empty().css({'left':offset_pop.left, 'top': offset_pop.top+20, 'zIndex': 100}).append($('<img class="avatar" src="' + src + '">')).show();
              var snap_count = ++count;
              setTimeout(function() {
                if (snap_count == count)
                  $('#avatar').hide();
              }, 5000);
            }
          },
          url: 'http://api.themoviedb.org/3/movie/' + id,
          data: data
        });
      }
    }, // function = onMouseOver callback (REQUIRED)
    out: function() { $('#avatar').hide(); } // function = onMouseOut callback (REQUIRED)
  });
  
  var clear =  function() {
    
  }
  
  $('.preview').hoverIntent({
    sensitivity: 3, // number = sensitivity threshold (must be 1 or higher)
    interval: 200, // number = milliseconds for onMouseOver polling interval
    timeout: 0, // number = milliseconds delay before onMouseOut
    over: function() {
      if (settings.previewAvatars) {
        var offset_pop = $(this).offset();
        http://api.themoviedb.org/3/search/person?api_key=a56c4c9722f90923979c4ed41b5c715f&query=Pete%20Travis
        var data = {
          api_key: 'a56c4c9722f90923979c4ed41b5c715f',
          query: $(this).attr('value')
        };
        $.ajax({
          dataType: 'json',
          success: function(data, status) {
            if (data && data.results && data.results.length>0 && data.results[0].profile_path) {
              //https://d3gtl9l2a4fn1j.cloudfront.net/t/p/w185/w8zJQuN7tzlm6FY9mfGKihxp3Cb.jpg
              var src = 'https://image.tmdb.org/t/p/w185' + data.results[0].profile_path;
              $("#avatar").empty().css({'left':offset_pop.left, 'top': offset_pop.top+20, 'zIndex': 100}).append($('<img class="avatar" src="' + src + '">')).show();
              var snap_count = ++count;
              setTimeout(function() {
                if (snap_count == count)
                  $('#avatar').hide();
              }, 5000);
            }
          },
          url: 'http://api.themoviedb.org/3/search/person',
          data: data
        });
      }
    }, // function = onMouseOver callback (REQUIRED)
    out: function() { $('#avatar').hide(); } // function = onMouseOut callback (REQUIRED)
  });
  
  $(".more").live("click", function() {
    $this = $(this);
    var hidden = $this.attr('state') == 'hidden';
    var arrow = hidden ? '&larr;' : '&rarr;';
    var state = hidden ? 'visible' : 'hidden';
    var $div = $this.html(arrow).attr('state', state).prev().toggle();
    if (hidden)
      $div.css('display', 'inline');
  });
  
  $(".filter_button").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter");
    filter == "clear"? clearFilters(): removeFilter(filter, _this.attr("value"));
    doFilter();
  });
  
  $(".link").live("click", function() {
    var _this = $(this),
      filter = _this.attr("filter"),
      value = _this.attr("value");
    if (filter == 'top' && value == 'all') {
        addFilter(filter, 10);
        addFilter(filter, 20);
        addFilter(filter, 30);
        addFilter(filter, 40);
        addFilter(filter, 50);
    } else if(settings.resetAllFiltersFlag) {
      clearFilters();
      setFilter(filter, value);
    } else  if(settings.cumulativeFiltersFlag) {
        addFilter(filter, value);
    } else {
      setFilter(filter, value);
    }
    doFilter();
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
      if (value && settings.showFiltersFlag)
        refreshListFilters();
    }
  });
  
  $('.ui-widget-overlay').live('click', function(){
    $('#dialog-message').dialog('close');
  });
  
  $('#showFilters').iphoneStyle({
    onChange: function(button, value) {
      settings.showFiltersFlag = value;
      $('.filterOptions').toggle();
      if(value)
        refreshListFilters();
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

$('#previewAvatars').iphoneStyle({
    onChange: function(button, value) {
      settings.previewAvatars = value;
    }
  });
  
  var $sidebar = $("#sidebar"),
        $window = $(window),
        offset = $sidebar.offset();
  
  var headerIsSliding = false;
  
  var enableHeaderSliding = function() {
    if(!headerIsSliding && window.scrollY > 100) {
      $('thead').clone().addClass('temp').appendTo($('thead').parent());
      $('thead').not('.temp').addClass('fixed2');
      return headerIsSliding = true;
    }
    return false;
  }
  
  var disableHeaderSliding = function() {
    if (headerIsSliding && window.scrollY < 100) {
      $('thead').removeClass('fixed2');
      $('.temp').remove();
      return headerIsSliding = false;
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
  
  $(function() {
    $( "#sidebar" ).accordion({
      //autoHeight: false,
      collapsible: true,
      heightStyle: 'content'
    });
  });

  var loadPage = function() {
    progress.start();
    loadAllMovies();
    //load(); // old way of multi-loading movies
    loadAllTops();
  }
  
  loadPage();
  
});