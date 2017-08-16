jQuery(document).ready(function($) {

  var args = {};

  if (typeof pbs_check_dma_args !== "undefined") {
    args = pbs_check_dma_args;
  }

  var cboxOptions = {
      iframe: true,
      closeButton: false,
      overlayClose: false,
      maxWidth: "1164",
      width: "95%",
      maxHeight: "95%",
      height: "700",
      scrolling: false,
      href: '/wp-content/common/ipad/explore-dma-register.php' 
    }

  function renderDMAbox() {
    $.colorbox(cboxOptions);
    $('button#dismissEmail').click(function(e) {
      console.log('fired');
      e.preventDefault();
      $.colorbox.remove();
    });
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
    renderDMAbox();
  });


});

function closeColorbox() {
  jQuery.colorbox.close();
}

