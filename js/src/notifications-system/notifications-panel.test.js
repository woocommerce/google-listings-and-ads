/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NotificationsPanel from './notifications-panel';
import useNotifications from './woo-marketing-notifications-slot/useNotifications';
import useNotificationsSystemMap from './useNotificationsSystemMap';

jest.mock( '~/hooks/useNotifications', () => jest.fn() );

jest.mock( './useNotificationsSystemMap', () => jest.fn() );

jest.mock( './notification', () => ( { id } ) => (
	<div data-testid={ `notification-${ id }` } />
) );

const TEST_MAP = {
	'paused-campaign': {
		title: 'Paused Campaign',
		description: 'Your campaign is paused.',
		actions: [],
	},
	'tracking-off': {
		title: 'Tracking Off',
		description: 'Turn on tracking.',
		actions: [],
	},
	'product-issues': {
		title: 'Product Issues',
		description: 'Some products have issues.',
		actions: [],
	},
};

describe( 'NotificationsPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useNotificationsSystemMap.mockReturnValue( TEST_MAP );
	} );

	it( 'renders nothing when there are no notifications', () => {
		useNotifications.mockReturnValue( { notifications: [] } );
		const { container } = render( <NotificationsPanel /> );
		expect( container.firstChild ).toBeNull();
	} );

	it.each( Object.keys( TEST_MAP ) )(
		'renders a Notification for known notification ID "%s"',
		( id ) => {
			useNotifications.mockReturnValue( {
				notifications: [ { id, triggered_at: 1000 } ],
			} );
			render( <NotificationsPanel /> );
			expect(
				screen.getByTestId( `notification-${ id }` )
			).toBeInTheDocument();
		}
	);

	it( 'renders multiple Notification components when multiple notifications are returned', () => {
		useNotifications.mockReturnValue( {
			notifications: [
				{ id: 'paused-campaign', triggered_at: 1000 },
				{ id: 'tracking-off', triggered_at: 2000 },
				{ id: 'product-issues', triggered_at: 3000 },
			],
		} );
		render( <NotificationsPanel /> );
		expect(
			screen.getByTestId( 'notification-paused-campaign' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'notification-tracking-off' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'notification-product-issues' )
		).toBeInTheDocument();
	} );

	it( 'skips unknown notification IDs without error', () => {
		useNotifications.mockReturnValue( {
			notifications: [
				{ id: 'unknown-notification-id', triggered_at: 1000 },
				{ id: 'another-unknown-id', triggered_at: 2000 },
			],
		} );
		const { container } = render( <NotificationsPanel /> );
		expect( container.firstChild ).toBeInTheDocument();
		expect(
			container.querySelector( '[data-testid]' )
		).not.toBeInTheDocument();
	} );
} );
