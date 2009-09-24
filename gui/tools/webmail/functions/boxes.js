(function($) {
    $.fn.omegaToggle = function(target) {

        var jQueryMatchedObj = this;
        var x = $target;
        var cookieVar = $(x);
        
        $(x).hide();

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
        		$(x).slideDown(100);
        		$(this).removeClass("selected");
        		$.cookie(cookieVar, 'expanded');
    		} else {
        		$(x).slideUp(300);
        		$(this).addClass("selected");
        		$.cookie(cookieVar, 'collapsed');    			
    		};
        }

		var cookieVar = $.cookie(cookieVar);

	    if (cookieVar == 'collapsed') {
	 		$(x).show();
			$(this).removeClass("selected");
		};

        return this.click(_initialize);
	};
})(jQuery);