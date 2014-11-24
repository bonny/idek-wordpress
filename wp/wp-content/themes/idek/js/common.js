
// Nagivate with keyboard
(function($) {

	var $document = $(document);

	$document.on("keyup", keyboardNavigate);

	function keyboardNavigate(e) {

		// keyCode:s 
		// 39 - höger pil
		// 37 - väns
		
		var $currentNavItem = $("#menu-huvudmeny .current-menu-item");
		var $toNavItem;
		var className = "";
		
		switch (e.keyCode) {

			// right / next
			case 39:
				$toNavItem = $currentNavItem.next();
				className = "next";
				break;

			// left / prev
			case 37:
				$toNavItem = $currentNavItem.prev();
				className = "prev";
				break;

		}

		if ($toNavItem) {
			$("body").addClass("begin-navigate begin-navigate--" + className);
			$toNavItem.find("a").get(0).click();
		}

	}


})(jQuery);
