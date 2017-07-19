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
      height: "90%",
      width: "90%",
      initialWidth: "90%",
      initialHeight: "90%",
      maxWidth: '90%',
      maxHeight: '90%',
      scrolling: true
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
    console.log('looking up zipcode for real');
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
          console.log('got a response');
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

  function getStationsByZip(zipcode) {
    var stations_url = '//services.pbs.org/callsigns/zip/';
    $.ajax({
      url: stations_url + zipcode + '.json?format=jsonp',
      dataType: 'jsonp',
      jsonpCallback: 'callme',
      cache: true,
      success: function(response) {
        if (typeof response.$items[0] !== "undefined") {
          checkForStation(response.$items);
        }
      }             
    });
  }

  function checkForStation(arr) {
    var station_call_letters = args.station_call_letters;
    for (var i = 0, len=arr.length; i < len; i++) {
      var callsign = arr[i].$links[0].callsign;
      console.log(callsign);
      if ((arr[i].confidence == 100) && (callsign == station_call_letters)) {
        console.log('found');
        // set station cookie and exit
      }
    }
    renderStationFinder(arr);
  }


  function renderStationFinder(arr) {
    console.log('rendering the list');
    $.colorbox(cboxOptions);

var output = '<div id="pbs-stations-list" class="pbs-modal pbs-stations-list" role="dialog" aria-labeledby="enterZipCodeTitle" tabindex="0" data-pass-focus-on-shift-tab-to="pbs-close-popup"> <!-- modal window --> <!-- title --><div class="modalTitle"><h2 id="enterZipCodeTitle">Confirm Your Local Station</h2></div> <!-- content --><div class="modalContent" role="document"><div class="autoLocalizationContainer localizationStationList"><div class="autoLocalizationText"><p><span>To help you find your favorite shows and great local content, we\'ve selected a PBS station in your area.</span><span class="paragraph">Please confirm that <span class="regionalDefaultStation">THIRTEEN</span><span class="regionalDefaultStationMobile">THIRTEEN</span> is your preferred local station, or choose another station below.</span></p></div><div class="modalStationImage"><img src="//image.pbs.org/station-images/StationColorProfiles/color/WNET.png.resize.106x106.png" alt="THIRTEEN"></div></div><div class="autoLocalizationContainer localizationStationListNoStations none"><div class="autoLocalizationText"><p>There are no stations available for your selected zip code.</p></div></div> <section id="autoSelectStation" class="" style="-ms-overflow-y:auto;"><!-- DO NOT DELETE --> <div class="stationsList" id="autoStationsList"> <button id="WNET" class="stationItem active" data-donate_url="http://support.thirteen.org/pbsdonate" data-common_name="THIRTEEN/WNET New York" data-zipcode="10019" aria-pressed="true"><h3><strong class="commonName">THIRTEEN/WNET New York</strong><strong class="shortCommonName">THIRTEEN</strong></h3><span>New York, NY</span></button> <button id="WEDH" class="stationItem" data-donate_url="https://www.callswithoutwalls.com/pledgeCart3/?campaign=749BE753-2AFD-4A51-9809-72EE1DA352B6&amp;source=ASW0000WBPBS" data-common_name="CONNECTICUT PUBLIC TELEVISION" data-zipcode="6105" aria-pressed="false"><h3><strong class="commonName">CONNECTICUT PUBLIC TELEVISION</strong><strong class="shortCommonName">CPTV</strong></h3><span>Hartford, CT</span></button> </div> </section> <div class="modalBottomContainer localizationStationList"><button id="moreStations" class="showStatesModal modal-button widthMedium">More Stations</button><button id="confirmStation" class="modalConfirmStation modal-button baseBlue widthMedium">Confirm Station</button></div><div id="noStations" class="modalBottomContainer localizationStationListNoStations none"><button id="backButton" class="zipBackButton modal-button baseBlue widthMedium">Back</button></div> </div><!-- end of content --><button id="pbs-close-popup" class="closeBtn" data-pass-focus-on-tab-to="pbs-stations-list" aria-label="Dismiss"> × </button></div>';


    $('#cboxContent').html(output);
  }




  function renderProgramList(response) {
    var output = '<div id="pledge_overlay"><h2>Select a thank-you gift:</h2><div class="pledge_programs_list"><ul><li><a class="premium" data-pcode="" data-price=0><span class="title">No gift, I want all of my pledge to go towards supporting this station</span></a></li></ul></div>';
    var active_panel = false;
    var featured_programs = null;
    if (typeof response.featured_programs !== "undefined") {
      featured_programs = response.featured_programs;
      output += '<div id="featured_premiums_list" class="pledge_programs_list">';
      $.each(featured_programs, function(index, program) {
        output += '<h3 id="' + program.slug + '">' + program.label + '</h3><div>';
        output += formatPremiumList(program);
        output += '</div>';
      });
      output += '</div>';
    }
    var additional_text = '';
    if (typeof response.additional_text != "undefined") {
      additional_text = '<div class="additional_text"><p>' + response.additional_text + '</p></div>';
    }
    if (typeof response.programs != "undefined") {
      if (featured_programs) {
        output += '<h2 class="program_list_header">Or, click on a program below to see more choices:</h2>';
      } else {
        output += '<h2 class="program_list_header">Click on a program below to see the available thank-you gifts:</h2>';
      }
      output += '<div id="pledge_premiums_list" class="pledge_programs_list">';
      $.each(response.programs, function(index, program) {
        output += '<h3 id="' + program.slug + '"><i class="fa fa-caret-square-o-down"></i>' + program.label + '</h3><div>';
        output += formatPremiumList(program);
        output += '</div>';
      });
      output += '</div>';
    }
    output += '</div>';
    $('#cboxContent').html(output);
	  $('#pledge_premiums_list').accordion({
      active: active_panel,
      collapsible: true,
      heightStyle: "content",
      animate: false,
      icons: false,
      activate: function( event, ui ) {
        setTimeout(cbresize, 100);
        $(".fa", ui.newHeader).removeClass("fa-caret-square-o-down").addClass("fa-caret-square-o-up");
        $(".fa", ui.oldHeader).removeClass("fa-caret-square-o-up").addClass("fa-caret-square-o-down");
      }      
    });
    $('#pledge_overlay').append(additional_text);
    $('#pledge_overlay .additional_text').click(function() {
      $.colorbox.close();
    });
    $('#pledge_overlay li a.premium').click(function(event) {
      event.preventDefault();
      $('#wnet_pledge_premiums button.launch').html("Change Selected Gift <i class='fa fa-minus-circle'></i>");
      var prem_message = "Selected Gift: <b>"+ $("span.title", this).text() + "</b>";
      var pricenum = Number($(this).attr("data-price"));
      if (pricenum > 0){
        prem_message = prem_message + ' $' + pricenum.toFixed(2);
        if (args.form_type == 'sustainer'){
          prem_message = prem_message + "/month, total annual contribution = " + (pricenum*12).toLocaleString('en-US', {style: 'currency', currency: 'USD' });
        }
      }
      $('#wnet_pledge_premiums_messages').html(prem_message);
      $('#wnet_pledge_premiums #pcode').text($(this).attr("data-pcode"));
      $('#wnet_pledge_premiums #req_amt').text($(this).attr("data-price"));
      $.colorbox.remove();
    })
  };

  
  function cbresize() {
    $.colorbox.resize(cboxOptions);
  }


  function formatPremiumList(program) {
    var output = '<ul>';
    $.each(program.premiums, function(idx, premium) {
      if (args.form_type == 'sustainer') {
        if (! premium.sust_amount) {
          return;
        }
        var monthly_amount = premium.sust_amount/12;
        var monthly_price = monthly_amount.toFixed(2);
        output += '<li><a class="premium" data-pcode="' + premium.pcode + '" data-price="' + monthly_amount + '"><span class="title">' + premium.title + '</span> <span class="price">($' + monthly_price + '/month)</span></li>';
      } else {
        output += '<li><a class="premium" data-pcode="' + premium.pcode + '" data-price="' + premium.req_amount + '"><span class="title">' + premium.title + '</span> <span class="price">($' + premium.req_amount + ')</span></li>';
      }
    });
    output += '</ul>';
    return output;
  }



  $(window).resize(function(){
    $.colorbox.resize({
      width: window.innerWidth > parseInt(cboxOptions.maxWidth) ? cboxOptions.maxWidth : cboxOptions.width,
      height: window.innerHeight > parseInt(cboxOptions.maxHeight) ? cboxOptions.maxHeight : cboxOptions.height
    });
  });
 
  $(function() {
    getClientIP();
  });
  

});
