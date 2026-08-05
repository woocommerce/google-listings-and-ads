/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Notification from './notification';
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
	CONTEXT_MARKETING_OVERVIEW: 'marketing-overview',
	REFERRER_TYPE_NOTIFICATION: 'notification',
} ) );

// Rendered as a `<button>`, not an `<a>` — real anchor navigation isn't
// implemented in jsdom, and these tests don't assert on the `href` attribute.
jest.mock(
	'~/components/app-button',
	() =>
		( { href, children, onClick, loading, disabled } ) => (
			<button
				data-href={ href }
				onClick={ onClick }
				aria-disabled={ disabled }
				data-loading={ loading ? 'true' : 'false' }
			>
				{ children }
			</button>
		)
);

jest.mock( './notification-skeleton', () => () => (
	<div data-testid="notification-skeleton" />
) );

const dismissNotification = jest.fn();
const saveSettings = jest.fn();

const baseProps = {
	id: 'collect-reviews',
	title: 'Collect Google reviews after purchase',
	description: 'Google Reviews provide free social proof.',
	triggeredAt: 1_700_000_000,
	onDismiss: jest.fn(),
};

describe( 'Notification', () => {
	const originalLocation = window.location;
	let locationAssignSpy;

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: originalLocation,
		} );
	} );

	beforeEach( () => {
		jest.clearAllMocks();

		useAppDispatch.mockReturnValue( { dismissNotification } );
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		locationAssignSpy = jest.fn();
		Object.defineProperty( window, 'location', {
			configurable: true,
			value: { ...originalLocation, assign: locationAssignSpy },
		} );
	} );

	it( 'navigates immediately and only tracks the click for an action with no settingKey', () => {
		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'view-product-issues',
						href: '/settings',
						children: 'View Product Issues',
					},
				] }
			/>
		);

		const link = screen.getByText( 'View Product Issues' );
		fireEvent.click( link );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_notifications_system_notification_cta_clicked',
			expect.objectContaining( { id: 'collect-reviews' } )
		);
		expect( saveSettings ).not.toHaveBeenCalled();
		expect( locationAssignSpy ).not.toHaveBeenCalled();
	} );

	it( 'saves the setting and then navigates when the action has a settingKey', async () => {
		saveSettings.mockResolvedValue( {} );

		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'enable-reviews-collection',
						href: '/settings',
						settingKey: 'collect_reviews_after_purchase',
						children: 'Enable reviews collection',
					},
				] }
			/>
		);

		fireEvent.click( screen.getByText( 'Enable reviews collection' ) );

		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				collect_reviews_after_purchase: true,
			} )
		);

		await waitFor( () =>
			expect( locationAssignSpy ).toHaveBeenCalledWith(
				expect.stringContaining( '/settings' )
			)
		);
	} );

	it( 'does not navigate and shows an error when saving the setting fails', async () => {
		const error = { message: 'Something went wrong' };
		saveSettings.mockRejectedValue( error );

		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'add-widget',
						href: '/settings',
						settingKey: 'badge_widget_enabled',
						children: 'Add widget',
					},
				] }
			/>
		);

		fireEvent.click( screen.getByText( 'Add widget' ) );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				expect.any( String )
			)
		);

		expect( locationAssignSpy ).not.toHaveBeenCalled();
	} );

	it( 'disables other actions while one settingKey action is saving', async () => {
		let resolveSave;
		saveSettings.mockReturnValue(
			new Promise( ( resolve ) => {
				resolveSave = resolve;
			} )
		);

		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'enable-reviews-collection',
						href: '/settings',
						settingKey: 'collect_reviews_after_purchase',
						children: 'Enable reviews collection',
					},
					{
						id: 'learn-more',
						href: '/learn-more',
						children: 'Learn more',
					},
				] }
			/>
		);

		fireEvent.click( screen.getByText( 'Enable reviews collection' ) );

		expect( screen.getByText( 'Learn more' ) ).toHaveAttribute(
			'aria-disabled',
			'true'
		);

		resolveSave( {} );
		await waitFor( () => expect( locationAssignSpy ).toHaveBeenCalled() );
	} );
} );
