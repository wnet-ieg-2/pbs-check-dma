jQuery(document).ready(function($) {

  var args = {};

  if (typeof pbs_check_dma_args !== "undefined") {
    args = pbs_check_dma_args;
  }

  var client_ip = false;
  var client_zip = false;
  var client_station = false;

  var cboxOptions = {
      inline: true,
      closeButton: false,
      width: "600",
      scrolling: false 
    }

  function checkForStation() {
    var station_cookie = readCookie('pbs_station_json');
    if(station_cookie_obj = JSON.parse(station_cookie)) {
      if (station_cookie_obj.call_letters == args.station_call_letters) {
        $(args.match_dma_showdiv).show(); 
      } else {
        $(args.mismatch_dma_showdiv).show();
        $('.regionalDefaultStation').text(station_cookie_obj.common_name);
        $('.regionalStationDonateLink').html('<a href="' + station_cookie_obj.donate_url + '">become a member of ' + station_cookie_obj.common_name + '</a>');
        $('.unSetStation').click(function(e) {
          e.preventDefault();
          unSetStation();
        });
      }
    } else {
      getClientIP();
    }
  }

  function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
      }
    return null;
  }

  function getClientIP() {
    var ip_url = args.ip_endpoint;
    if (client_ip) {
      getClientZip();
    } else {
      $.ajax({
        url: ip_url,
        dataType: 'jsonp',
        jsonpCallback: 'placeholdercallback',
        cache: true,
        success: function(response) {
          if (typeof response.client_ip_address !== "undefined") {
            client_ip = response.client_ip_address;
            getClientZip();
          }
        }             
      });
    }
  }

  function getClientZip() {
    if (! client_ip) {
      console.log('client ip not set');
      return;
    }
    var zip_url = '//services.pbs.org/zipcodes/ip/';
    if (client_zip) {
      getStationsByZip(client_zip);
    } else {
      $.ajax({
        url: zip_url + client_ip + '.json?format=jsonp',
        dataType: 'jsonp',
        jsonpCallback: 'callme',
        cache: true,
        success: function(response) {
          client_zip = response.$items[0].zipcode;
          getStationsByZip(client_zip);
        },
        timeout: 1000,
        error: function(parsedjson, textStatus, errorThrown) {
          console.log( "parsedJson status: " + JSON.stringify(parsedjson) + ' : ' + "errorStatus: " + textStatus + ' : ' + "errorThrown: " + errorThrown);  
        }
      });
    }
  }



  function getStationsByState(state) {
    var stations_url = '//services.pbs.org/stations/state/';
    $.ajax({
      url: stations_url + state + '.json?format=jsonp',
      dataType: 'jsonp',
      jsonpCallback: 'callme',
      cache: true,
      success: function(response) {
        if (typeof response.$items[0] !== "undefined") {
          // normalize the array to look like the zip array
          var outarr = [];
          for (var i = 0, len=response.$items.length; i < len; i++) {
            var thisarr = { $links : [ response.$items[i].$links[0] ] };
            outarr[i] = { $links : [ thisarr ] };
          }
          renderStationFinder(outarr);
        }
      }
    });
  }


  function getStationsByZip(zipcode) {
    var stations_url = '//services.pbs.org/callsigns/zip/';
    $.ajax({
      url: stations_url + zipcode + '.json?format=jsonp',
      dataType: 'jsonp',
      jsonpCallback: 'callme',
      cache: true,
      success: function(response) {
        if (typeof response.$items[0] !== "undefined") {
          checkForStationMatch(response.$items);
        }
      }             
    });
  }

  function checkForStationMatch(arr) {
    var station_call_letters = args.station_call_letters;
    for (var i = 0, len=arr.length; i < len; i++) {
      var callsign = arr[i].$links[0].callsign;
      if ((arr[i].confidence == 100) && (callsign == station_call_letters)) {
        $(args.match_dma_showdiv).show();
        setStationCookie(station_call_letters);
        console.log(JSON.stringify(arr[i]));
        return;
      }
    }
    renderStationFinder(arr);
  }


  function renderStationFinder(arr) {
    var first_option_callsign = false;
    var first_option_short_common_name = false;
    var station_button_list = '';
    var flagships = [];
    for (var i = 0, len=arr.length; i < len; i++) {
      var relationship = arr[i].$links[0].$links[0].$links[1].$relationship;
      var callsign = arr[i].$links[0].$links[0].$links[1].callsign;
      if (relationship == 'flagship' && ($.inArray(callsign, flagships) == -1)) {
        flagships.push(callsign);
        var common_name = arr[i].$links[0].$links[0].common_name;
        var short_common_name = arr[i].$links[0].$links[0].short_common_name;
        var mailing_city = arr[i].$links[0].$links[0].mailing_city;
        var mailing_state = arr[i].$links[0].$links[0].mailing_state;
        var membership_url = arr[i].$links[0].$links[0].membership_url;
        var website = arr[i].$links[0].$links[0].website;
        var activeclass = '';
        var ariapressed = 'false';
        if (! first_option_callsign) {
          first_option_callsign = callsign;
          first_option_short_common_name = short_common_name;
          activeclass = ' active';
          ariapressed = 'true';
        }
        if ((typeof arr[i].confidence !== 'undefined') && arr[i].confidence < 100) {
          continue;
        }
        station_button_list += '<button id="' + callsign + '" class="stationItem' + activeclass + '" data-donate_url="' + membership_url + '" data-common_name="' + common_name + '" aria-pressed="' + ariapressed +'"><h3><strong class="commonName">' + common_name + '</strong><strong class="shortCommonName">' + short_common_name + '</strong></h3><span>' + mailing_city + ', ' + mailing_state + '</span></button>';
      }
    }
    cboxOptions['height'] = 567;
    $.colorbox(cboxOptions);

    var confirm_wrapper_template = '<div id="pbs-stations-list" class="pbs-modal pbs-stations-list" role="dialog" aria-labeledby="enterZipCodeTitle" tabindex="0" data-pass-focus-on-shift-tab-to="pbs-close-popup"> <!-- modal window --> <!-- title --><div class="modalTitle"><h2 id="enterZipCodeTitle">Confirm Your Local Station</h2></div> <!-- content --><div class="modalContent" role="document"><div class="autoLocalizationContainer localizationStationList"><div class="autoLocalizationText"><p><span>To help you find your favorite shows and great local content, we\'ve selected a PBS station in your area.</span><span class="paragraph">Please confirm that <span class="regionalDefaultStation"></span><span class="regionalDefaultStationMobile"></span> is your preferred local station, or choose another station below.</span></p></div><div class="modalStationImage"></div></div><div class="autoLocalizationContainer localizationStationListNoStations none"><div class="autoLocalizationText"><p>There are no stations available for your selected zip code.</p></div></div> <section id="autoSelectStation" class="" style="-ms-overflow-y:auto;"><!-- DO NOT DELETE --> <div class="stationsList" id="autoStationsList"></div> </section> <div class="modalBottomContainer localizationStationList"><button id="moreStations" class="showStatesModal modal-button widthMedium">More Stations</button><button id="confirmStation" class="modalConfirmStation modal-button baseBlue widthMedium">Confirm Station</button></div><div id="noStations" class="modalBottomContainer localizationStationListNoStations none"><button id="backButton" class="zipBackButton modal-button baseBlue widthMedium">Back</button></div> <div class="pickAnyway"></div></div><!-- end of content --><button id="pbs-close-popup" class="closeBtn" data-pass-focus-on-tab-to="pbs-stations-list" aria-label="Dismiss"> × </button></div>'; 
    
    var output = confirm_wrapper_template;
    
    $('#cboxContent').html(output);
    $('#pbs-stations-list .regionalDefaultStation, #pbs-stations-list .regionalDefaultStationMobile').text(first_option_short_common_name);
    $('#pbs-stations-list .modalStationImage').html('<img src="//image.pbs.org/station-images/StationColorProfiles/color/' + first_option_callsign + '.png.resize.106x106.png" alt="' + first_option_short_common_name + '">');
    $('#pbs-stations-list .pickAnyway').html('<button class="modal-button">My local station is ' + args.station_common_name + '</button>');
    $('#autoStationsList').html(station_button_list);
    $('#moreStations').click(function(e) {
      e.preventDefault(); 
      $.colorbox.remove();
      renderStationZipSelector();
    });
    $('#autoStationsList button.stationItem').click(function(e) {
      e.preventDefault();
      $.colorbox.remove();
      setStationCookie($(this).attr('id'), $(this).attr('data-common_name'), $(this).attr('data-donate_url') );
      checkForStation();
    });
    $('#pbs-stations-list .pickAnyway button').click(function(e) {
      e.preventDefault();
      $.colorbox.remove();
      setStationCookie(args.station_call_letters);
      $(args.match_dma_showdiv).show();
    });


  }

  function renderStationZipSelector() {
    var output = '<div id="pbs-find-stations" class="pbs-modal pbs-find-stations" role="dialog" tabindex="0" aria-labeledby="selectStationTitle" data-pass-focus-on-shift-tab-to="pbs-close-find-station"><!-- modal window --><!-- title --><div class="modalTitle"><h2 id="selectStationTitle">Find Your Local Station:</h2></div><!-- content --><div class="modalContent" role="document"><!-- enter zip code screen --><section id="enterZipCode"><div class="form"><div class="fieldset clearfix zipform"><input id="zipInput" pattern="[0-9]*" minlength="5" maxlength="5" name="zip" placeholder="ZIP Code" required="required" type="tel"><button id="searchByZip" class="modal-button baseBlue" data-atr="getByZip">Search by ZIP Code</button><p class="errorMsg none">Please enter a valid zip code</p></div><div class="fieldset clearfix regionform"><select name="" id="regionSelect"><option value="">Select State</option><option value="AL">Alabama</option><option value="AK">Alaska</option><option value="AS">American Samoa</option><option value="AZ">Arizona</option><option value="AR">Arkansas</option><option value="CA">California</option><option value="CO">Colorado</option><option value="CT">Connecticut</option><option value="DE">Delaware</option><option value="DC">District of Columbia</option><option value="FL">Florida</option><option value="GA">Georgia</option><option value="GU">Guam</option><option value="HI">Hawaii</option><option value="ID">Idaho</option><option value="IL">Illinois</option><option value="IN">Indiana</option><option value="IA">Iowa</option><option value="KS">Kansas</option><option value="KY">Kentucky</option><option value="LA">Louisiana</option><option value="ME">Maine</option><option value="MD">Maryland</option><option value="MA">Massachusetts</option><option value="MI">Michigan</option><option value="MN">Minnesota</option><option value="MS">Mississippi</option><option value="MO">Missouri</option><option value="MT">Montana</option><option value="NE">Nebraska</option><option value="NV">Nevada</option><option value="NH">New Hampshire</option><option value="NJ">New Jersey</option><option value="NM">New Mexico</option><option value="NY">New York</option><option value="NC">North Carolina</option><option value="ND">North Dakota</option><option value="OH">Ohio</option><option value="OK">Oklahoma</option><option value="OR">Oregon</option><option value="PA">Pennsylvania</option><option value="PR">Puerto Rico</option><option value="RI">Rhode Island</option><option value="SC">South Carolina</option><option value="SD">South Dakota</option><option value="TN">Tennessee</option><option value="TX">Texas</option><option value="UT">Utah</option><option value="VT">Vermont</option><option value="VI">Virgin Islands</option><option value="VA">Virginia</option><option value="WA">Washington</option><option value="WV">West Virginia</option><option value="WI">Wisconsin</option><option value="WY">Wyoming</option></select><button id="serchByRegion" class="modal-button baseBlue" data-atr="getByRegion">Search by State</button><p class="errorMsg none">Please select a region</p></div></div></section><button id="pbs-close-find-station" class="closeBtn" data-pass-focus-on-tab-to="pbs-find-stations" aria-label="Dismiss"> × </button></div><!-- end of content --></div>';
    cboxOptions['height'] = 300;
    $.colorbox(cboxOptions);
    $('#cboxContent').html(output);
    $('#searchByZip').click(function(e) {
      e.preventDefault();
      var zipcode = $('#zipInput').val();
      if (/^[0-9]{5}(?:-[0-9]{4})?$/.test(zipcode)){
        $.colorbox.remove();
        getStationsByZip(zipcode);
      } else {
        console.log('invalid zip');
      }
    });
    $('#serchByRegion').click(function(e) {
      e.preventDefault();
      var state = $('#regionSelect').val();
      if (state){
        $.colorbox.remove();
        getStationsByState(state);
      } else {
        console.log('invalid state');
      }
    });
  } 

  function setStationCookie(call_letters, common_name, donate_url) {
    console.log('will set cookie for ' + call_letters);
    var a = new Date();
    a = new Date(a.getTime() +1000*60*60*24*365);
    var station_ary = {'call_letters' : call_letters, 'common_name' : common_name, 'donate_url' : donate_url};
    document.cookie = 'pbs_station_json=' + JSON.stringify(station_ary) +'; expires='+a.toUTCString() + "; path=/";
  }

  function unSetStation() {
    $(args.mismatch_dma_showdiv).hide();
    $(args.match_dma_showdiv).hide();
    console.log('unsetting station cookie');
    document.cookie = 'pbs_station_json=false; expires=-1; path=/';
    checkForStation();    
  }


  function cbresize() {
    $.colorbox.resize(cboxOptions);
  }



  $(window).resize(function(){
    $.colorbox.resize({
      width: window.innerWidth > parseInt(cboxOptions.maxWidth) ? cboxOptions.maxWidth : cboxOptions.width,
      height: window.innerHeight > parseInt(cboxOptions.maxHeight) ? cboxOptions.maxHeight : cboxOptions.height
    });
  });
 
  $(function() {
    $(args.match_dma_showdiv).hide();
    $(args.mismatch_dma_showdiv).hide();
    $('.unSetStation').click(function(e) {
      e.preventDefault();
      unSetStation();
    });
    checkForStation();
  });
  

});
