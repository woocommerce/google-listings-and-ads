/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ShippingRateSettings from './shipping-rate-settings';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { handleApiError } from '~/utils/handleError';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/hooks/useSettings' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

// ShippingRateMethodSection relies on `useAdaptiveFormContext`, `useSettings`
// and `useMCSetup` internals that are irrelevant to this component's own
// save/notice logic, so stub it to a minimal control surface: a button that
// triggers a `shipping_rate` change, plus its `disabled` prop rendered as an
// attribute so tests can assert the in-progress state.
jest.mock(
	'~/components/shipping-rate-section/shipping-rate-method-section',
	() =>
		( { disabled } ) => (
			<button disabled={ disabled } data-testid="shipping-rate-option">
				Manual
			</button>
		)
);

// AdaptiveForm is mocked as a forwardRef spy so the `onChange` handler can be
// exercised by clicking the stubbed radio button, without needing a real
// form/context.
const mockAdaptiveForm = jest.fn();
jest.mock( '~/components/adaptive-form', () => {
	const { forwardRef: mockForwardRef } =
		jest.requireActual( '@wordpress/element' );

	return {
		__esModule: true,
		default: mockForwardRef( ( props, ref ) => {
			mockAdaptiveForm( props, ref );
			return (
				<div ref={ ref }>
					<button
						onClick={ () =>
							props.onChange( {
								name: 'shipping_rate',
								value: 'manual',
							} )
						}
					>
						Select manual
					</button>
					<button
						onClick={ () =>
							props.onChange( {
								name: 'shipping_rate',
								value: 'automatic',
							} )
						}
					>
						Select automatic
					</button>
					<button
						onClick={ () =>
							props.onChange( {
								name: 'some_other_field',
								value: 'anything',
							} )
						}
					>
						Change other field
					</button>
					{ props.children }
				</div>
			);
		} ),
	};
} );

describe( 'ShippingRateSettings', () => {
	let createNotice;

	beforeEach( () => {
		jest.clearAllMocks();
		createNotice = jest.fn();
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
	} );

	test( 'shows a success notice only after both save and sync succeed, and re-enables the options', async () => {
		const saveSettings = jest.fn().mockResolvedValue();
		const syncSettings = jest.fn().mockResolvedValue();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Select manual' ) );

		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				shipping_time: 'manual',
			} )
		);
		expect( syncSettings ).toHaveBeenCalledTimes( 1 );
		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			expect.stringContaining( 'has been saved and synced' )
		);
		expect( handleApiError ).not.toHaveBeenCalled();
		expect(
			screen.getByTestId( 'shipping-rate-option' )
		).not.toBeDisabled();
	} );

	test( 'disables the options while the save is in progress', async () => {
		let resolveSave;
		const saveSettings = jest.fn(
			() => new Promise( ( resolve ) => ( resolveSave = resolve ) )
		);
		const syncSettings = jest.fn().mockResolvedValue();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Select manual' ) );

		expect( screen.getByTestId( 'shipping-rate-option' ) ).toBeDisabled();

		resolveSave();

		await waitFor( () =>
			expect(
				screen.getByTestId( 'shipping-rate-option' )
			).not.toBeDisabled()
		);
	} );

	test( 'shows an error notice and skips sync when save fails, without a success notice', async () => {
		const saveSettings = jest
			.fn()
			.mockRejectedValue( new Error( 'save failed' ) );
		const syncSettings = jest.fn();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Select manual' ) );

		expect( handleApiError ).toHaveBeenCalledWith(
			expect.any( Error ),
			'There was an error saving the shipping rate method.'
		);
		expect( syncSettings ).not.toHaveBeenCalled();
		expect( createNotice ).not.toHaveBeenCalled();
		expect(
			screen.getByTestId( 'shipping-rate-option' )
		).not.toBeDisabled();
	} );

	test( 'shows an error notice when sync fails after a successful save, without a success notice', async () => {
		const saveSettings = jest.fn().mockResolvedValue();
		const syncSettings = jest
			.fn()
			.mockRejectedValue( new Error( 'sync failed' ) );
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Select manual' ) );

		expect( handleApiError ).toHaveBeenCalledWith(
			expect.any( Error ),
			'There was an error synchronizing the shipping rate method to Google Merchant Center.'
		);
		expect( createNotice ).not.toHaveBeenCalled();
		expect(
			screen.getByTestId( 'shipping-rate-option' )
		).not.toBeDisabled();
	} );

	test( 'couples a non-manual shipping rate with the flat shipping time', async () => {
		const saveSettings = jest.fn().mockResolvedValue();
		const syncSettings = jest.fn().mockResolvedValue();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.MANUAL },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Select automatic' ) );

		expect( saveSettings ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
				shipping_time: 'flat',
			} )
		);
	} );

	test( 'ignores changes to fields other than shipping_rate', async () => {
		const saveSettings = jest.fn().mockResolvedValue();
		const syncSettings = jest.fn().mockResolvedValue();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			saveSettings,
			syncSettings,
		} );

		const user = userEvent.setup();
		render( <ShippingRateSettings /> );

		await user.click( screen.getByText( 'Change other field' ) );

		expect( saveSettings ).not.toHaveBeenCalled();
		expect( syncSettings ).not.toHaveBeenCalled();
		expect( createNotice ).not.toHaveBeenCalled();
	} );

	test( 'shows a loading spinner while settings have not resolved yet', () => {
		useSettings.mockReturnValue( { settings: undefined } );

		render( <ShippingRateSettings /> );

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
		expect( mockAdaptiveForm ).not.toHaveBeenCalled();
	} );

	test( 'renders nothing when settings have resolved without a shipping_rate value', () => {
		useSettings.mockReturnValue( { settings: {} } );

		const { container } = render( <ShippingRateSettings /> );

		expect( container ).toBeEmptyDOMElement();
		expect( mockAdaptiveForm ).not.toHaveBeenCalled();
	} );
} );
