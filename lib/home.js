(function ($) {
 "use strict";
    
		//---------------------------------------------
		//Nivo slider
		//---------------------------------------------
			 $('#ensign-nivoslider').nivoSlider({
				effect: 'random',
				slices: 15,
				boxCols: 8,
				boxRows: 4,
				animSpeed: 500,
				pauseTime: 500000,
				startSlide: 0,
				directionNav: true,
				controlNavThumbs: false,
				pauseOnHover: true,
				manualAdvance: false
			 });
			 
			 $('#ensign-nivoslider-v2').nivoSlider({
				effect: 'random',
				slices: 15,
				boxCols: 8,
				boxRows: 4,
				animSpeed: 500,
				pauseTime: 500000,
				startSlide: 0,
				directionNav: false,
                controlNav:true,
				controlNavThumbs: false,
				pauseOnHover: false,
				manualAdvance: true      
			 });
})(jQuery); 