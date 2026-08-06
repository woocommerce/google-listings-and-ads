/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { useDispatch } from '@wordpress/data';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';
import {
	GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
	GCR_ENROLLMENT_HELP_URL,
} from './constants';
import ReviewsSettings from './reviews-settings';
import useSettings from '~/hooks/useSettings';
import usePreference from '~/hooks/usePreference';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	ToggleControl: ( { label, checked, onChange, disabled, help } ) => (
		<div>
			<label htmlFor="toggle-control-mock">
				<input
					id="toggle-control-mock"
					type="checkbox"
					aria-label={ label }
					checked={ checked }
					disabled={ disabled }
					onChange={ () => onChange( ! checked ) }
				/>
				{ label }
			</label>
			{ help && <p>{ help }</p> }
		</div>
	),
	Notice: ( { children, onRemove, isDismissible } ) => (
		<div className="components-notice" role="status">
			{ children }
			{ isDismissible && (
				<button
					className="components-notice__dismiss"
					onClick={ onRemove }
				>
					Dismiss
				</button>
			) }
		</div>
	),
	Flex: ( { children, ...rest } ) => <div { ...rest }>{ children }</div>,
	Card: ( { children, ...rest } ) => <div { ...rest }>{ children }</div>,
	CardBody: ( { children, ...rest } ) => <div { ...rest }>{ children }</div>,
	CardFooter: ( { children, ...rest } ) => (
		<div { ...rest }>{ children }</div>
	),
} ) );

jest.mock( '~/components/app-button', () => ( { href, children, onClick } ) => (
	<a href={ href } onClick={ onClick }>
		{ children }
	</a>
) );

jest.mock( '~/components/spinner-card', () => () => (
	<div data-testid="spinner-card" />
) );

jest.mock( '~/hooks/useSettings', () => jest.fn().mockName( 'useSettings' ) );

jest.mock( '~/hooks/usePreference', () =>
	jest.fn().mockName( 'usePreference' )
);

describe( 'ReviewsSettings', () => {
	let saveSettings;
	let setPreference;

	beforeEach( () => {
		saveSettings = jest.fn().mockResolvedValue( {} );
		setPreference = jest.fn();

		useDispatch.mockReturnValue( { set: setPreference } );
		usePreference.mockReturnValue( false );
	} );

	it( 'renders a loading state until settings resolve', () => {
		useSettings.mockReturnValue( { settings: undefined, saveSettings } );

		render( <ReviewsSettings /> );

		expect( screen.getByTestId( 'spinner-card' ) ).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Collect reviews after purchase' )
		).not.toBeInTheDocument();
	} );

	it( 'renders the toggle unchecked when the setting is disabled', () => {
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <ReviewsSettings /> );

		expect(
			screen.getByLabelText( 'Collect reviews after purchase' )
		).not.toBeChecked();
	} );

	it( 'renders the toggle checked when the setting is enabled', () => {
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: true },
			saveSettings,
		} );

		render( <ReviewsSettings /> );

		expect(
			screen.getByLabelText( 'Collect reviews after purchase' )
		).toBeChecked();
	} );

	it( 'saves the setting with the toggled value on change', async () => {
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <ReviewsSettings /> );
		fireEvent.click(
			screen.getByLabelText( 'Collect reviews after purchase' )
		);

		await waitFor( () =>
			expect( saveSettings ).toHaveBeenCalledWith(
				expect.objectContaining( {
					collect_reviews_after_purchase: true,
				} )
			)
		);
	} );

	it( 'renders the GCR-enrollment notice with its link when not dismissed', () => {
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <ReviewsSettings /> );

		expect( screen.getByText( /Find out how/i ) ).toHaveAttribute(
			'href',
			GCR_ENROLLMENT_HELP_URL
		);
	} );

	it( 'dismisses the GCR-enrollment notice', () => {
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <ReviewsSettings /> );

		const gcrNotice = screen
			.getByText( /Find out how/i )
			.closest( '.components-notice' );
		fireEvent.click(
			gcrNotice.querySelector( '.components-notice__dismiss' )
		);

		expect( setPreference ).toHaveBeenCalledWith(
			PREFERENCES_STORE_NAMESPACE,
			GCR_ENROLLMENT_NOTICE_DISMISSED_KEY,
			true
		);
	} );

	it( 'does not render the GCR-enrollment notice once dismissed', () => {
		usePreference.mockReturnValue( true );
		useSettings.mockReturnValue( {
			settings: { collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <ReviewsSettings /> );

		expect( screen.queryByText( /Find out how/i ) ).not.toBeInTheDocument();
	} );
} );
