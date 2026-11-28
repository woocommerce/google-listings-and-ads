/**
 * Internal dependencies
 */
import {
	GLA_NOTIFICATION_DISMISSED,
	GLA_NOTIFICATIONS_COUNT_CHANGED,
} from '~/constants';

describe( 'notification-manager', () => {
	let doAction;

	/**
	 * Renders the Marketing menu with a badge, then loads the module.
	 *
	 * @param {number} badgeCount The server-rendered badge count.
	 * @param {*} ownCount The server-rendered count of this plugin's notifications.
	 */
	async function loadModule( badgeCount, ownCount ) {
		document.body.innerHTML = `
			<li id="toplevel_page_woocommerce-marketing">
				<a><div class="wp-menu-name">Marketing <span class="update-plugins count-${ badgeCount }"><span class="update-count">${ badgeCount }</span></span></div></a>
			</li>
		`;
		window.glaNotificationManagerData = { notificationsCount: ownCount };

		jest.resetModules();
		( { doAction } = await import( '@wordpress/hooks' ) );
		await import( './index' );
	}

	function getBadge() {
		return document.querySelector( '.update-plugins' );
	}

	function getBadgeCount() {
		return getBadge().querySelector( '.update-count' ).textContent;
	}

	afterEach( () => {
		delete window.glaNotificationManagerData;
	} );

	it( 'keeps the badge when the inbox count matches the server count', async () => {
		await loadModule( 3, 2 );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 2 );

		expect( getBadgeCount() ).toBe( '3' );
	} );

	it( 'changes the badge by the difference from the server count', async () => {
		await loadModule( 3, 2 );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 1 );
		expect( getBadgeCount() ).toBe( '2' );
		expect( getBadge().className ).toContain( 'count-2' );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 3 );
		expect( getBadgeCount() ).toBe( '4' );
		expect( getBadge().className ).toContain( 'count-4' );
	} );

	it( 'decrements the badge when a notification is dismissed, and keeps that in the baseline', async () => {
		await loadModule( 3, 2 );

		doAction( GLA_NOTIFICATION_DISMISSED );
		expect( getBadgeCount() ).toBe( '2' );

		// A sync after the dismissal reports the same inbox count.
		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 1 );
		expect( getBadgeCount() ).toBe( '2' );
	} );

	it( 'hides the badge at 0 and shows it again when the count goes up', async () => {
		await loadModule( 1, 1 );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 0 );
		expect( getBadge().style.display ).toBe( 'none' );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 2 );
		expect( getBadge().style.display ).toBe( '' );
		expect( getBadgeCount() ).toBe( '2' );
	} );

	it.each( [ NaN, '2', undefined, -1, 1.5 ] )(
		'ignores an invalid count: %p',
		async ( count ) => {
			await loadModule( 3, 2 );

			doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, count );

			expect( getBadgeCount() ).toBe( '3' );
		}
	);

	it( 'treats a missing server count as 0', async () => {
		await loadModule( 1, undefined );

		doAction( GLA_NOTIFICATIONS_COUNT_CHANGED, 2 );

		expect( getBadgeCount() ).toBe( '3' );
	} );
} );
