/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NotificationsPanel from './index';
import useNotifications from '../../hooks/useNotifications';

jest.mock( '../../hooks/useNotifications' );

const MockNotificationA = () => <div data-testid="notification-a" />;
const MockNotificationB = () => <div data-testid="notification-b" />;

describe( 'NotificationsPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders nothing when there are no notifications', () => {
		useNotifications.mockReturnValue( { notifications: [] } );
		const { container } = render( <NotificationsPanel /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders the notification component for each notification', () => {
		useNotifications.mockReturnValue( {
			notifications: [
				{ id: 'a', triggered_at: 1000, component: MockNotificationA },
				{ id: 'b', triggered_at: 2000, component: MockNotificationB },
			],
		} );
		render( <NotificationsPanel /> );
		expect( screen.getByTestId( 'notification-a' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'notification-b' ) ).toBeInTheDocument();
	} );

	it( 'skips rendering for notifications with no component', () => {
		useNotifications.mockReturnValue( {
			notifications: [
				{ id: 'a', triggered_at: 1000, component: MockNotificationA },
				{ id: 'no-component', triggered_at: 2000, component: null },
			],
		} );
		render( <NotificationsPanel /> );
		expect( screen.getByTestId( 'notification-a' ) ).toBeInTheDocument();
		expect(
			screen.queryByTestId( 'no-component' )
		).not.toBeInTheDocument();
	} );

	it( 'displays a badge with the notification count', () => {
		useNotifications.mockReturnValue( {
			notifications: [
				{ id: 'a', triggered_at: 1000, component: MockNotificationA },
				{ id: 'b', triggered_at: 2000, component: MockNotificationB },
			],
		} );
		render( <NotificationsPanel /> );
		expect( screen.getByText( '2' ) ).toBeInTheDocument();
	} );
} );
