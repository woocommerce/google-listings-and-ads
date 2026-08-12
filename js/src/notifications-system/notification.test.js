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
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );

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
		( { href, children, onClick, disabled } ) => (
			<button
				data-href={ href }
				onClick={ onClick }
				aria-disabled={ disabled }
			>
				{ children }
			</button>
		)
);

jest.mock( './notification-skeleton', () => () => (
	<div data-testid="notification-skeleton" />
) );

const dismissNotification = jest.fn();

const baseProps = {
	id: 'collect-reviews',
	title: 'Collect Google reviews after purchase',
	description: 'Google Reviews provide free social proof.',
	triggeredAt: 1_700_000_000,
	onDismiss: jest.fn(),
};

describe( 'Notification', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useAppDispatch.mockReturnValue( { dismissNotification } );
	} );

	it( 'tracks the click and does not call onClick for an action with no onClick', () => {
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

		fireEvent.click( screen.getByText( 'View Product Issues' ) );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_notifications_system_notification_cta_clicked',
			expect.objectContaining( { id: 'collect-reviews' } )
		);
	} );

	it( "calls the action's onClick with the event and action when provided", async () => {
		const onClick = jest.fn().mockResolvedValue();

		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'enable-reviews-collection',
						href: '/settings',
						children: 'Enable reviews collection',
						onClick,
					},
				] }
			/>
		);

		fireEvent.click( screen.getByText( 'Enable reviews collection' ) );

		await waitFor( () => expect( onClick ).toHaveBeenCalledTimes( 1 ) );

		const [ event, action ] = onClick.mock.calls[ 0 ];
		expect( event ).toBeTruthy();
		expect( action ).toEqual(
			expect.objectContaining( { id: 'enable-reviews-collection' } )
		);
	} );

	it( "renders each action's own disabled state as-is, with no logic of its own", () => {
		render(
			<Notification
				{ ...baseProps }
				actions={ [
					{
						id: 'enable-reviews-collection',
						href: '/settings',
						children: 'Enable reviews collection',
						disabled: true,
					},
					{
						id: 'learn-more',
						href: '/learn-more',
						children: 'Learn more',
					},
				] }
			/>
		);

		expect(
			screen.getByText( 'Enable reviews collection' )
		).toHaveAttribute( 'aria-disabled', 'true' );
		expect( screen.getByText( 'Learn more' ) ).not.toHaveAttribute(
			'aria-disabled',
			'true'
		);
	} );
} );
