/**
 * External dependencies
 */
import { subscribe, select, resolveSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const GOOGLE_DASHBOARD_HREF =
	'admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard';

/**
 * Mirrors PHP NotificationManager::is_marketing_page() for badge placement.
 *
 * @return {boolean} Whether the current page is a Marketing child page.
 */
function isMarketingChildPage() {
	const params = new URLSearchParams( window.location.search );

	if ( params.get( 'page' ) !== 'wc-admin' ) {
		return false;
	}

	const path = params.get( 'path' ) || '';

	return path.startsWith( '/google' ) || path === '/marketing';
}

/**
 * @param {HTMLElement} target
 * @param {HTMLElement} badge
 */
function appendBadgeToTarget( target, badge ) {
	if ( target.contains( badge ) ) {
		return;
	}

	target.textContent = target.textContent.trimEnd() + ' ';
	target.appendChild( badge );
}

/**
 * @param {HTMLElement} badge
 * @param {number} count
 */
function updateBadgeCount( badge, count ) {
	const normalizedCount = Math.max( 0, Number( count ) || 0 );

	if ( normalizedCount <= 0 ) {
		badge.style.display = 'none';
		return;
	}

	badge.style.display = '';
	badge.className = badge.className.replace( /\bcount-\d+\b/g, '' ).trim();
	badge.classList.add( 'count-' + normalizedCount );

	const countSpan = badge.querySelector( '.update-count' );

	if ( countSpan ) {
		countSpan.textContent = String( normalizedCount );
	}
}

function initNotificationManager() {
	const badge = document.querySelector(
		'#toplevel_page_woocommerce-marketing .update-plugins'
	);

	if ( ! badge ) {
		return;
	}

	const marketingMenu = document.getElementById(
		'toplevel_page_woocommerce-marketing'
	);

	if ( ! marketingMenu ) {
		return;
	}

	const googleSubMenu = document.querySelector(
		`[href="${ GOOGLE_DASHBOARD_HREF }"]`
	);

	const placeBadge = () => {
		if ( isMarketingChildPage() && googleSubMenu ) {
			appendBadgeToTarget( googleSubMenu, badge );
			return;
		}

		const topMenu = document.querySelector(
			'.toplevel_page_woocommerce-marketing > a > .wp-menu-name'
		);

		if ( topMenu ) {
			appendBadgeToTarget( topMenu, badge );
		}
	};

	const observer = new MutationObserver( placeBadge );

	observer.observe( marketingMenu, {
		attributes: true,
		attributeFilter: [ 'class' ],
		subtree: true,
	} );

	placeBadge();

	const subscribeToMenuCount = () => {
		if ( ! select( STORE_KEY ) ) {
			return false;
		}

		let lastCount = null;

		subscribe( () => {
			const count = select( STORE_KEY ).getMenuNotificationCount();

			if ( count === null || count === lastCount ) {
				return;
			}

			lastCount = count;
			updateBadgeCount( badge, count );
		}, STORE_KEY );

		return true;
	};

	if ( ! subscribeToMenuCount() ) {
		const unsubscribe = subscribe( () => {
			if ( subscribeToMenuCount() ) {
				unsubscribe();
			}
		} );
	}

	if ( select( STORE_KEY ) ) {
		resolveSelect( STORE_KEY ).getNotifications();
	}
}

initNotificationManager();
