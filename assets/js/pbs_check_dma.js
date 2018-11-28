jQuery(document).ready(function($) {

  function DMARestrictedPlayer(passedDiv, browser_lat, browser_long) {
    if (typeof(passedDiv) !== 'undefined' && passedDiv) {playerdiv = $(passedDiv);}
    else {playerdiv = $('.dmarestrictedplayer');}
    var videoID = playerdiv.data('media');
    var thumb = $('img', playerdiv).attr('src');
    var dma_endpoint = "/pbs_check_dma/";
    var postdata = {media_id:videoID,thumbnail:thumb};
    if (browser_lat && browser_long) {
      postdata["latitude"] = browser_lat;
      postdata["longitude"] = browser_long;
    }
    if (typeof(declined_location) !== 'undefined' && declined_location) {
      postdata["declined_location"] = true;
    }
    $.ajax({
      url: dma_endpoint,
      data: postdata,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        if (typeof(response.request_browser_location) === 'undefined') {
          playerdiv.html(response.output);
        } else {
          if (!navigator.geolocation || declined_location) {
            playerdiv.html(response.output);
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

  if ($(".dmarestrictedplayer[data-media]")[0]){
    $( ".dmarestrictedplayer" ).each(function( index ) {
        DMARestrictedPlayer(this, '','');
    });
  }


});

