/**
 * External dependencies
 */
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	GLA_NOTIFICATION_DISMISSED,
	GLA_NOTIFICATIONS_COUNT_CHANGED,
} from '~/constants';

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

	function placeBadge() {
		if ( marketingMenu.classList.contains( 'wp-has-current-submenu' ) ) {
			const subMenu = marketingMenu.querySelector(
				'.wp-submenu [href="admin.php?page=wc-admin&path=%2Fmarketing"]'
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
	}

	// Place immediately for the server-rendered initial state (a hard page
	// load never fires a class mutation), then keep watching for SPA route
	// changes that toggle the submenu classes.
	placeBadge();

	const observer = new MutationObserver( placeBadge );

	observer.observe( marketingMenu, {
		attributes: true,
		attributeFilter: [ 'class' ],
	} );

	/**
	 * Adjusts the badge count by a delta rather than setting an absolute count,
	 * since the server-rendered count can include other extensions' notifications.
	 *
	 * @param {number} delta The number to add to the current count.
	 */
	function updateCount( delta ) {
		const countEl = badge.querySelector( '.update-count' );

		if ( ! countEl ) {
			return;
		}

		const currentCount = parseInt( countEl.textContent, 10 );

		if ( Number.isNaN( currentCount ) ) {
			return;
		}

		const newCount = Math.max( 0, currentCount + delta );

		countEl.textContent = newCount;
		badge.className = badge.className.replace(
			/\bcount-\d+\b/,
			'count-' + newCount
		);
		badge.style.display = newCount === 0 ? 'none' : '';
	}

	addAction( GLA_NOTIFICATION_DISMISSED, 'gla/notification-manager', () =>
		updateCount( -1 )
	);

	addAction(
		GLA_NOTIFICATIONS_COUNT_CHANGED,
		'gla/notification-manager',
		updateCount
	);
} )();
