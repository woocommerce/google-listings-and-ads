/* global MutationObserver */
( function () {
	const badge = document.querySelector(
		'#toplevel_page_woocommerce-marketing .update-plugins'
	);

	if ( ! badge ) {
		return;
	}

	const marketingMenu = document.getElementById(
		'toplevel_page_woocommerce-marketing'
	);

	const observer = new MutationObserver( function () {
		if ( marketingMenu.classList.contains( 'wp-has-current-submenu' ) ) {
			const subMenu = document.querySelector(
				'[href="admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard"]'
			);

			if ( subMenu && ! subMenu.contains( badge ) ) {
				// Ensure there is white space between the badge and menu title for visual consistency.
				subMenu.textContent.trimEnd();
				subMenu.textContent += ' ';

				// Move the badge to the correct location.
				subMenu.appendChild( badge );
			}
		} else {
			const topMenu = document.querySelector(
				'.toplevel_page_woocommerce-marketing > a > .wp-menu-name'
			);

			if ( topMenu && ! topMenu.contains( badge ) ) {
				// Ensure there is white space between the badge and menu title for visual consistency.
				topMenu.textContent.trimEnd();
				topMenu.textContent += ' ';

				// Move the badge to the correct location.
				topMenu.appendChild( badge );
			}
		}
	} );

	observer.observe( marketingMenu, {
		attributes: true,
		attributeFilter: [ 'class' ],
	} );
} )();
