/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import NotificationsPanel from '../notifications-panel';
import useNotifications from '~/hooks/useNotifications';

jest.mock( '~/hooks/useNotifications', () => jest.fn() );

jest.mock( '@wordpress/data', () => ( {
	useDispatch: jest.fn(),
} ) );

jest.mock(
	'../notifications/skipped-campaign-creation',
	() =>
		( { onDismiss } ) => (
			<div data-testid="skipped-campaign-creation">
				<button onClick={ onDismiss }>dismiss</button>
			</div>
		)
);
jest.mock( '../notifications/not-onboarded-90-days', () => () => (
	<div data-testid="not-onboarded-90-days" />
) );
jest.mock( '../notifications/sold-10-items', () => () => (
	<div data-testid="sold-10-items" />
) );
jest.mock( '../notifications/payments-shipping-no-sales', () => () => (
	<div data-testid="payments-shipping-no-sales" />
) );
jest.mock( '../notifications/abandoned-onboarding', () => () => (
	<div data-testid="abandoned-onboarding" />
) );
jest.mock( '../notifications/product-issues', () => () => (
	<div data-testid="product-issues" />
) );
jest.mock( '../notifications/paused-campaign', () => () => (
	<div data-testid="paused-campaign" />
) );
jest.mock( '../notifications/active-campaign-zero-sales', () => () => (
	<div data-testid="active-campaign-zero-sales" />
) );
jest.mock( '../notifications/enhanced-conversions-off', () => () => (
	<div data-testid="enhanced-conversions-off" />
) );
jest.mock( '../notifications/recommendations-available', () => () => (
	<div data-testid="recommendations-available" />
) );
jest.mock( '../notifications/coupons-not-synced', () => () => (
	<div data-testid="coupons-not-synced" />
) );
jest.mock( '../notifications/sales-not-growing', () => () => (
	<div data-testid="sales-not-growing" />
) );
jest.mock( '../notifications/tracking-off', () => () => (
	<div data-testid="tracking-off" />
) );

const KNOWN_IDS = [
	'skipped-campaign-creation',
	'not-onboarded-90-days',
	'sold-10-items',
	'payments-shipping-no-sales',
	'abandoned-onboarding',
	'product-issues',
	'paused-campaign',
	'active-campaign-zero-sales',
	'enhanced-conversions-off',
	'recommendations-available',
	'coupons-not-synced',
	'sales-not-growing',
	'tracking-off',
];

const mockDismissNotification = jest.fn();
const mockInvalidateResolutionForStoreSelector = jest.fn();

describe( 'NotificationsPanel', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useDispatch.mockReturnValue( {
			dismissNotification: mockDismissNotification,
			invalidateResolutionForStoreSelector:
				mockInvalidateResolutionForStoreSelector,
		} );
	} );

	it( 'renders nothing when there are no notifications', () => {
		useNotifications.mockReturnValue( [] );
		const { container } = render( <NotificationsPanel /> );
		expect( container.firstChild ).toBeNull();
	} );

	it.each( KNOWN_IDS )(
		'renders the correct component for known notification ID "%s"',
		( id ) => {
			useNotifications.mockReturnValue( [ { id, triggered_at: 1000 } ] );
			render( <NotificationsPanel /> );
			expect( screen.getByTestId( id ) ).toBeInTheDocument();
		}
	);

	it( 'renders multiple notification components when multiple notifications are returned', () => {
		useNotifications.mockReturnValue( [
			{ id: 'skipped-campaign-creation', triggered_at: 1000 },
			{ id: 'paused-campaign', triggered_at: 2000 },
			{ id: 'tracking-off', triggered_at: 3000 },
		] );
		render( <NotificationsPanel /> );
		expect(
			screen.getByTestId( 'skipped-campaign-creation' )
		).toBeInTheDocument();
		expect( screen.getByTestId( 'paused-campaign' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'tracking-off' ) ).toBeInTheDocument();
	} );

	it( 'skips unknown notification IDs without error', () => {
		useNotifications.mockReturnValue( [
			{ id: 'unknown-notification-id', triggered_at: 1000 },
			{ id: 'another-unknown-id', triggered_at: 2000 },
		] );
		const { container } = render( <NotificationsPanel /> );
		expect( container.firstChild ).toBeInTheDocument();
		expect(
			container.querySelector( '[data-testid]' )
		).not.toBeInTheDocument();
	} );

	it( 'calls dismissNotification with the notification id when onDismiss is triggered', () => {
		useNotifications.mockReturnValue( [
			{ id: 'skipped-campaign-creation', triggered_at: 1000 },
		] );
		render( <NotificationsPanel /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'dismiss' } ) );
		expect( mockDismissNotification ).toHaveBeenCalledWith(
			'skipped-campaign-creation'
		);
	} );

	describe( 'badge DOM update', () => {
		let badge;

		beforeEach( () => {
			badge = document.createElement( 'span' );
			badge.className = 'update-plugins';
			const menuItem = document.createElement( 'li' );
			menuItem.id = 'toplevel_page_woocommerce-marketing';
			menuItem.appendChild( badge );
			document.body.appendChild( menuItem );
		} );

		afterEach( () => {
			document
				.getElementById( 'toplevel_page_woocommerce-marketing' )
				?.remove();
		} );

		it( 'sets badge text and shows it when notifications are present', () => {
			useNotifications.mockReturnValue( [
				{ id: 'paused-campaign', triggered_at: 1000 },
				{ id: 'tracking-off', triggered_at: 2000 },
			] );
			render( <NotificationsPanel /> );
			expect( badge.textContent ).toBe( '2' );
			expect( badge.style.display ).not.toBe( 'none' );
		} );

		it( 'clears badge text and hides it when notifications are empty', () => {
			useNotifications.mockReturnValue( [] );
			render( <NotificationsPanel /> );
			expect( badge.textContent ).toBe( '' );
			expect( badge.style.display ).toBe( 'none' );
		} );
	} );

	it( 'invalidates getNotifications resolution when the tab becomes visible', () => {
		useNotifications.mockReturnValue( [] );
		render( <NotificationsPanel /> );

		act( () => {
			Object.defineProperty( document, 'hidden', {
				configurable: true,
				get: () => false,
			} );
			document.dispatchEvent( new Event( 'visibilitychange' ) );
		} );

		expect( mockInvalidateResolutionForStoreSelector ).toHaveBeenCalledWith(
			'getNotifications'
		);
	} );
} );
