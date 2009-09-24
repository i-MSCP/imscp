(function($) {
    $.fn.omegaToggle = function(target,boxID) {

        var jQueryMatchedObj = this,
        x = $(target),
        cookieID = "Toggle" + boxID,
        cookieVar = $.cookie(cookieID);
        
        if (cookieVar == 'collapsed') {
	 		$(x).hide();
			$(jQueryMatchedObj).addClass("selected");
		};
        
        function _initialize() {
        	_start(this,jQueryMatchedObj);
        return false;
        }

        function _start(objClicked,jQueryMatchedObj) {
            _set_interface();
            return false;
        };

        function _set_interface() {
        	if ($(x).is(":hidden")) {
        		$(x).slideDown(300);
        		$(jQueryMatchedObj).removeClass("selected");
        		$.cookie(cookieID, 'expanded', {expires: 90});
    		} else {
        		$(x).slideUp(500);
        		$(jQueryMatchedObj).addClass("selected");
        		$.cookie(cookieID, 'collapsed', {expires: 90});    			
    		};
        }

        return this.click(_initialize);
	};
})(jQuery);