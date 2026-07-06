/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { GLA_NOTIFICATION_DISMISSED } from '~/constants';

( function () {
	const marketingMenu = document.getElementById(
		'toplevel_page_woocommerce-marketing'
	);

	if ( ! marketingMenu ) {
		return;
	}

	const badge = marketingMenu.querySelector( '.update-plugins' );

	if ( ! badge ) {
		return;
	}

	const observer = new MutationObserver( function () {
		if ( marketingMenu.classList.contains( 'wp-has-current-submenu' ) ) {
			const subMenu = marketingMenu.querySelector(
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
			const topMenu = marketingMenu.querySelector(
				':scope > a > .wp-menu-name'
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

	function handleNotificationDismissed() {
		const countEl = badge.querySelector( '.update-count' );

		if ( ! countEl ) {
			return;
		}

		const newCount = Math.max( 0, parseInt( countEl.textContent, 10 ) - 1 );

		if ( newCount === 0 ) {
			badge.style.display = 'none';
		} else {
			countEl.textContent = newCount;
			badge.className = badge.className.replace(
				/\bcount-\d+\b/,
				'count-' + newCount
			);
		}
	}

	addAction(
		GLA_NOTIFICATION_DISMISSED,
		'gla/notification-manager',
		handleNotificationDismissed
	);
} )();
