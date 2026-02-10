
/*================================================
[  Table of contents  ]
================================================

:: Sticky
:: Tooltip
:: SwiperAnimation
:: Shuffle
:: Back to top

======================================
[ End table content ]
======================================*/

(function ($) {
  "use strict";
  var SEER = {};

/*************************
  Predefined Variables
*************************/
  var $window = $(window),
    $document = $(document),
    $body = $('body');
  //Check if function exists
  $.fn.exists = function () {
    return this.length > 0;
  };

/*************************
            Preloader
    *************************/
    SEER.preloader = function() {
        $('#preloader').delay(0).fadeOut('slow');
    };
	


  

/****************************************************
     SEER Window load and functions
****************************************************/

  //Window load functions
    $window.on("load", function() {
        SEER.preloader()
	
		//SEER.isSticky()
    });


})(jQuery);

