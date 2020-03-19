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
          playCustomHLSIfPresent(playerdiv);
        } else {
          if (!navigator.geolocation || declined_location) {
            /* still possibly to have a playable video:
             *  maybe IP geolcation worked, maybe it didnt */
            playerdiv.html(response.output);
            playCustomHLSIfPresent(playerdiv);
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

  function playCustomHLSIfPresent(parentdiv) {
    if (typeof($('#custom_hls_player', parentdiv)) !== 'undefined') {
      hlsplayer = $('#custom_hls_player', playerdiv);
      jwplayer("custom_hls_player").setup({'file': hlsplayer.data('hls'), width: '100%', image: thumb, ga: {label: 'mediaid'}});
    }
  }

  function geoerrorhandler(error) {
    if (error.code === 1) {
      declined_location = true; 
    } 
    DMARestrictedPlayer(playerdiv);
  }

  var declined_location = false;
  var playerdiv = '';
  var browser_lat = '';
  var browser_long = '';
  var thumb = '';

  if ($(".dmarestrictedplayer[data-media]")[0]){
    $( ".dmarestrictedplayer" ).each(function( index ) {
        DMARestrictedPlayer(this, '','');
    });
  }


});

