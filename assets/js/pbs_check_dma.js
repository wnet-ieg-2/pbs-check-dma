jQuery(document).ready(function($) {

  function DMARestrictedPlayer(passedDiv, browser_lat, browser_long) {
    if (typeof(passedDiv) !== 'undefined' && passedDiv) {playerdiv = $(passedDiv);}
    else {playerdiv = $('.dmarestrictedplayer');}
    var videoID = playerdiv.data('media');
    thumb = $('img', playerdiv).attr('src');
    var dma_endpoint = "/pbs_check_dma/";
    var postdata = {media_id:videoID,thumbnail:thumb};
    if (browser_lat && browser_long) {
      postdata["latitude"] = browser_lat;
      postdata["longitude"] = browser_long;
    }
    if (typeof(declined_location) !== 'undefined' && declined_location) {
      postdata["declined_location"] = true;
    }
    if (typeof(playerdiv.data('postid')) !== 'undefined') {
      postdata["postid"] = playerdiv.data('postid');
      postid = postdata["postid"];
    }

    $.ajax({
      url: dma_endpoint,
      data: postdata,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        if (typeof(response.request_browser_location) === 'undefined') { 
          /* "request_browser_location" is a return value from the endpoint 
           * if we haven't satisfied the location requirement yet. 
           * undefined means we dont need to know anymore  */
          playerdiv.html(response.output);
          $('.retryDMALocation').on("click", resetDMA );
          playCustomHLSIfPresent();
        } else {
          if (!navigator.geolocation || declined_location) {
            /* still possibly to have a playable video:
             *  maybe IP geolcation worked, maybe it didnt */
            playerdiv.html(response.output);
            $('.retryDMALocation').on("click", resetDMA );
            playCustomHLSIfPresent();
          } else {
            navigator.geolocation.getCurrentPosition(function(position) {
              browser_lat = position.coords.latitude;
              browser_long = position.coords.longitude;
              DMARestrictedPlayer(playerdiv, browser_lat, browser_long);
            }, geoerrorhandler);
          }
        }
      }
    });
  }

  function playCustomHLSIfPresent() {
    if (typeof($('#custom_hls_player')) === 'undefined') {
      // this script doesnt apply so be done.
      return;
    }
    var player = $('#custom_hls_player');
    if (typeof(player.data('postid') !== 'undefined') && player.data('postid')) {
      postid = player.data('postid');
    }
    if (typeof(player.data('hls') !== 'undefined') && player.data('hls')) {
      hls = player.data('hls');
    }
    var playerargs = {'file': hls, width: '100%', image: thumb };
    if (typeof(userstarted) !== 'undefined'){
      playerargs['autostart'] = userstarted;
    } 
    var payload = {'postid':postid,'timestamp':Date.now()};
    $.ajax(
        {
          url: "/livestream_status/",
          data: payload,
          type: 'POST',
          dataType: 'json'
        }
      )
      .done(function(response) {
        if (typeof(response.blackout_status) !== 'undefined') {
          blackout_status = response.blackout_status;
          console.log("stream blacked out: " + blackout_status);
          if (blackout_status == false) {
            if (typeof(jwplayer("custom_hls_player").getState()) === 'undefined') {
              jwplayer("custom_hls_player").setup(playerargs).on('error', jwperrorhandler).on('play', function() {userstarted = true; console.log("user started");});
            }
          } else {
            if (typeof(jwplayer("custom_hls_player")) !== 'undefined') {
              jwplayer("custom_hls_player").stop();
              jwplayer("custom_hls_player").remove();
              $(".dmarestrictedplayer").html("<div id='custom_hls_player'><div class='video-wrap dma-fail'><img src='" + thumb + "'><div class='sorry'><div class='sorry-txt'><h3>The currently-broadcast program is not licensed for live streaming.</h3><br /><br /><h3>The live stream will resume at " + response.end + "</h3></div></div></div></div>");
            }
          }
        }
      })
      .always(function() {
        blackoutStatusTimeout = window.setTimeout(playCustomHLSIfPresent, 30*1000);
      });
  }

  function jwperrorhandler() {
      jwplayer("custom_hls_player").remove();
      $(".dmarestrictedplayer").html("<div id='custom_hls_player'><div class='video-wrap dma-fail'><img src='" + thumb + "'><div class='sorry'><div class='sorry-txt'><h3>One moment please, there is a problem with the livestream</h3></div></div></div></div>");
  }

  function geoerrorhandler(error) {
    if (error.code === 1) {
      declined_location = true; 
    } 
    DMARestrictedPlayer(playerdiv);
  }

  function resetDMA(evt) {
    evt.preventDefault;
    document.cookie = "dmalocation= ;  expires = Thu, 01 Jan 1970 00:00:00 GMT; path=/ ";
    setTimeout("location.reload(true);", 500);
  }

  var blackout_status = false;
  var blackoutStatusTimeout = false;
  var declined_location = false;
  var playerdiv = '';
  var browser_lat = '';
  var browser_long = '';
  var thumb = '';
  var hls = '';
  var postid = '';
  var userstarted = false;

  if ($(".dmarestrictedplayer[data-media]")[0]){
    $( ".dmarestrictedplayer" ).each(function( index ) {
        DMARestrictedPlayer(this, '','');
    });
  }


});

