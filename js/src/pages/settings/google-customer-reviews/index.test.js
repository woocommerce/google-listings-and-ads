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
import GoogleCustomerReviewsSettings from './index';
import useSettings from '~/hooks/useSettings';
import usePreference from '~/hooks/usePreference';
import { handleApiError } from '~/utils/handleError';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest.fn(),
} ) );

jest.mock( '@wordpress/components', () => ( {
	ToggleControl: ( { label, checked, onChange, disabled, help } ) => (
		<div>
			<label htmlFor={ `toggle-control-mock-${ label }` }>
				<input
					id={ `toggle-control-mock-${ label }` }
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
	RadioControl: ( { label, selected, options = [], onChange, disabled } ) => (
		<fieldset>
			<legend>{ label }</legend>
			{ options.map( ( option ) => (
				<label
					key={ option.value }
					htmlFor={ `radio-${ option.value }` }
				>
					<input
						id={ `radio-${ option.value }` }
						type="radio"
						name="widget-position-mock"
						checked={ option.value === selected }
						disabled={ disabled }
						onChange={ () => onChange( option.value ) }
					/>
					{ option.label }
				</label>
			) ) }
		</fieldset>
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

jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'GoogleCustomerReviewsSettings', () => {
	let saveSettings;
	let setPreference;

	beforeEach( () => {
		saveSettings = jest.fn().mockResolvedValue( {} );
		setPreference = jest.fn();

		useDispatch.mockReturnValue( { set: setPreference } );
		usePreference.mockReturnValue( false );
		handleApiError.mockClear();
		recordGlaEvent.mockClear();
	} );

	it( 'renders a loading state until settings resolve', () => {
		useSettings.mockReturnValue( { settings: undefined, saveSettings } );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.getByTestId( 'spinner-card' ) ).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Collect reviews after purchase' )
		).not.toBeInTheDocument();
	} );

	it( 'renders the toggle unchecked when the setting is disabled', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect(
			screen.getByLabelText( 'Collect reviews after purchase' )
		).not.toBeChecked();
	} );

	it( 'renders the toggle checked when the setting is enabled', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect(
			screen.getByLabelText( 'Collect reviews after purchase' )
		).toBeChecked();
	} );

	it( 'saves the setting with the toggled value on change', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click(
			screen.getByLabelText( 'Collect reviews after purchase' )
		);

		await waitFor( () =>
			expect( saveSettings ).toHaveBeenCalledWith(
				expect.objectContaining( {
					gcr_collect_reviews_after_purchase: true,
				} )
			)
		);
	} );

	it( 'calls handleApiError and re-enables the toggle when saving fails', async () => {
		const error = new Error( 'Network error' );
		saveSettings.mockRejectedValueOnce( error );
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		const toggle = screen.getByLabelText(
			'Collect reviews after purchase'
		);
		fireEvent.click( toggle );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				'There was an error updating the setting.'
			)
		);

		expect( toggle ).not.toBeDisabled();
	} );

	it( 'fires gla_reviews_collection_toggle with enabled: true when turned on and saved', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click(
			screen.getByLabelText( 'Collect reviews after purchase' )
		);

		await waitFor( () =>
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_reviews_collection_toggle',
				{ enabled: true }
			)
		);
	} );

	it( 'fires gla_reviews_collection_toggle with enabled: false when turned off and saved', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click(
			screen.getByLabelText( 'Collect reviews after purchase' )
		);

		await waitFor( () =>
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_reviews_collection_toggle',
				{ enabled: false }
			)
		);
	} );

	it( 'fires gla_reviews_collection_toggle even when saving fails', async () => {
		const error = new Error( 'Network error' );
		saveSettings.mockRejectedValueOnce( error );
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click(
			screen.getByLabelText( 'Collect reviews after purchase' )
		);

		await waitFor( () => expect( handleApiError ).toHaveBeenCalled() );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_reviews_collection_toggle',
			{ enabled: true }
		);
	} );

	it( 'renders the GCR-enrollment notice with its link when not dismissed', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.getByText( /Learn how/i ) ).toHaveAttribute(
			'href',
			GCR_ENROLLMENT_HELP_URL
		);
	} );

	it( 'dismisses the GCR-enrollment notice', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		const gcrNotice = screen
			.getByText( /Learn how/i )
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
			settings: { gcr_collect_reviews_after_purchase: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.queryByText( /Learn how/i ) ).not.toBeInTheDocument();
	} );

	it( 'renders the badge widget toggle unchecked when the setting is disabled', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect(
			screen.getByLabelText( 'Google store widget' )
		).not.toBeChecked();
	} );

	it( 'renders the badge widget toggle checked when the setting is enabled', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.getByLabelText( 'Google store widget' ) ).toBeChecked();
	} );

	it( 'hides the widget position control when the badge widget toggle is off', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect(
			screen.queryByLabelText( 'Right bottom' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Left bottom' )
		).not.toBeInTheDocument();
	} );

	it( 'shows the widget position control defaulting to bottom-right when the setting is unset', () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.getByLabelText( 'Right bottom' ) ).toBeChecked();
		expect( screen.getByLabelText( 'Left bottom' ) ).not.toBeChecked();
	} );

	it( 'shows the widget position control with the saved position selected', () => {
		useSettings.mockReturnValue( {
			settings: {
				gcr_badge_widget_enabled: true,
				gcr_badge_widget_position: 'bottom-left',
			},
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		expect( screen.getByLabelText( 'Left bottom' ) ).toBeChecked();
		expect( screen.getByLabelText( 'Right bottom' ) ).not.toBeChecked();
	} );

	it( 'saves the badge widget toggle with the toggled value on change', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click( screen.getByLabelText( 'Google store widget' ) );

		await waitFor( () =>
			expect( saveSettings ).toHaveBeenCalledWith(
				expect.objectContaining( {
					gcr_badge_widget_enabled: true,
				} )
			)
		);
	} );

	it( 'saves the widget position when changed', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click( screen.getByLabelText( 'Left bottom' ) );

		await waitFor( () =>
			expect( saveSettings ).toHaveBeenCalledWith(
				expect.objectContaining( {
					gcr_badge_widget_position: 'bottom-left',
				} )
			)
		);
	} );

	it( 'calls handleApiError and re-enables the toggle when saving the badge widget setting fails', async () => {
		const error = new Error( 'Network error' );
		saveSettings.mockRejectedValueOnce( error );
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		const toggle = screen.getByLabelText( 'Google store widget' );
		fireEvent.click( toggle );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				'There was an error updating the setting.'
			)
		);

		expect( toggle ).not.toBeDisabled();
	} );

	it( 'fires gla_reviews_badge_widget_toggle with enabled: true when turned on and saved', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click( screen.getByLabelText( 'Google store widget' ) );

		await waitFor( () =>
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_reviews_badge_widget_toggle',
				{ enabled: true }
			)
		);
	} );

	it( 'fires gla_reviews_badge_widget_toggle with enabled: false when turned off and saved', async () => {
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click( screen.getByLabelText( 'Google store widget' ) );

		await waitFor( () =>
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_reviews_badge_widget_toggle',
				{ enabled: false }
			)
		);
	} );

	it( 'fires gla_reviews_badge_widget_toggle even when saving fails', async () => {
		const error = new Error( 'Network error' );
		saveSettings.mockRejectedValueOnce( error );
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: false },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );
		fireEvent.click( screen.getByLabelText( 'Google store widget' ) );

		await waitFor( () => expect( handleApiError ).toHaveBeenCalled() );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_reviews_badge_widget_toggle',
			{ enabled: true }
		);
	} );

	it( 'calls handleApiError and re-enables the position control when saving the widget position fails', async () => {
		const error = new Error( 'Network error' );
		saveSettings.mockRejectedValueOnce( error );
		useSettings.mockReturnValue( {
			settings: { gcr_badge_widget_enabled: true },
			saveSettings,
		} );

		render( <GoogleCustomerReviewsSettings /> );

		const leftBottomOption = screen.getByLabelText( 'Left bottom' );
		fireEvent.click( leftBottomOption );

		await waitFor( () =>
			expect( handleApiError ).toHaveBeenCalledWith(
				error,
				'There was an error updating the setting.'
			)
		);

		expect( leftBottomOption ).not.toBeDisabled();
	} );
} );
