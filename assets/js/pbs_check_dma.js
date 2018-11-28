jQuery(document).ready(function($) {

  function DMARestrictedPlayer(passedDiv) {
    if (typeof(passedDiv) !== 'undefined' && passedDiv) {playerdiv = $(passedDiv);}
    else {$player = $('.dmarestrictedplayer');}
    var active = 0;
    var videoID = playerdiv.data('media');
    var thumb = playerdiv.data('thumbnail');
    var dma_endpoint = "/pbs_check_dma/";

    $.ajax({
      url: dma_endpoint,
      data: {media_id:videoID,thumbnail:thumb},
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        playerdiv.html(response);
      }
    });
  }

  if ($(".dmarestrictedplayer[data-media]")[0]){
    $( ".dmarestrictedplayer" ).each(function( index ) {
        DMARestrictedPlayer(this);
    });
  }


});

