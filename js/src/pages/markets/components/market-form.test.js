/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MarketForm from './market-form';
import useSettings from '~/hooks/useSettings';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import { SHIPPING_RATE_METHOD } from '~/constants';
import { PRIMARY_MARKET_ID } from '../constants';

jest.mock( '~/hooks/useSettings' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

let mockSubmittedValues = {
	country: 'US',
	countries: [ 'US' ],
};

// MarketForm renders its fields via AdaptiveForm (and attaches a ref to it);
// mock it as a forwardRef spy so we can both inspect the props it's given
// (initialValues, onChange) and simulate a submit by clicking the rendered
// button, without needing a real form context. `setValue` is exposed via
// `useImperativeHandle` so `handleChange` can be exercised directly.
const mockAdaptiveForm = jest.fn();
const mockSetValue = jest.fn();
jest.mock( '~/components/adaptive-form', () => {
	const {
		forwardRef: mockForwardRef,
		useImperativeHandle: mockUseImperativeHandle,
	} = jest.requireActual( '@wordpress/element' );

	return {
		__esModule: true,
		default: mockForwardRef( ( props, ref ) => {
			mockAdaptiveForm( props, ref );
			mockUseImperativeHandle( ref, () => ( {
				setValue: mockSetValue,
			} ) );
			return (
				<button onClick={ () => props.onSubmit( mockSubmittedValues ) }>
					Submit
				</button>
			);
		} ),
	};
} );

describe( 'MarketForm handleSubmit', () => {
	const createMarket = jest.fn();
	const updateMarket = jest.fn();
	const syncSettings = jest.fn();
	const invalidateResolution = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		createMarket.mockResolvedValue();
		updateMarket.mockResolvedValue();
		syncSettings.mockResolvedValue();

		useAppDispatch.mockReturnValue( {
			createMarket,
			updateMarket,
			syncSettings,
			invalidateResolution,
		} );
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				shipping_time: 'manual',
			},
		} );
		mockSubmittedValues = {
			country: 'US',
			countries: [ 'US' ],
		};
	} );

	test( 'invalidates both getTargetAudience and getMarkets after a successful save, since saving shipping can change which countries are split into their own derived markets', async () => {
		const user = userEvent.setup();
		const onSubmit = jest.fn();

		render( <MarketForm onSubmit={ onSubmit } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( createMarket ).toHaveBeenCalledWith(
			expect.objectContaining( { country: 'US' } )
		);
		expect( syncSettings ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getTargetAudience',
			[]
		);
		expect( invalidateResolution ).toHaveBeenCalledWith( 'getMarkets', [] );
		expect( onSubmit ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'nests a shipping object with rate, threshold, and times for flat markets', async () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );
		mockSubmittedValues = {
			country: 'US',
			countries: [ 'US' ],
			flat_shipping_rate: 5,
			offer_free_shipping: true,
			free_shipping_threshold: 25,
			flat_shipping_min_time: 1,
			flat_shipping_max_time: 3,
		};
		const user = userEvent.setup();

		render( <MarketForm onSubmit={ () => {} } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( createMarket ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shipping: {
					flat_rate: 5,
					free_shipping_threshold: 25,
					flat_time: 1,
					flat_max_time: 3,
				},
			} )
		);
	} );

	test( 'sets free_shipping_threshold to null when offer_free_shipping is turned off, for flat markets', async () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );
		mockSubmittedValues = {
			country: 'US',
			countries: [ 'US' ],
			flat_shipping_rate: 5,
			offer_free_shipping: false,
			free_shipping_threshold: 25,
			flat_shipping_min_time: 1,
			flat_shipping_max_time: 3,
		};
		const user = userEvent.setup();

		render( <MarketForm onSubmit={ () => {} } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( createMarket ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shipping: expect.objectContaining( {
					free_shipping_threshold: null,
				} ),
			} )
		);
	} );

	test( 'nests only shipping times, with no rate or threshold, for automatic markets', async () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
				shipping_time: 'flat',
			},
		} );
		mockSubmittedValues = {
			country: 'US',
			countries: [ 'US' ],
			flat_shipping_min_time: 2,
			flat_shipping_max_time: 4,
		};
		const user = userEvent.setup();

		render( <MarketForm onSubmit={ () => {} } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( createMarket ).toHaveBeenCalledWith(
			expect.objectContaining( {
				shipping: { flat_time: 2, flat_max_time: 4 },
			} )
		);
	} );

	test( 'omits the shipping key entirely for manual markets', async () => {
		const user = userEvent.setup();

		render( <MarketForm onSubmit={ () => {} } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		const [ payload ] = createMarket.mock.calls[ 0 ];
		expect( payload ).not.toHaveProperty( 'shipping' );
	} );

	test( 'updates an existing non-primary market without merging countries into the payload', async () => {
		mockSubmittedValues = {
			country: 'FR',
			countries: [ 'FR' ],
		};
		const user = userEvent.setup();

		render(
			<MarketForm
				initialMarket={ { id: 'fr', country: 'FR' } }
				onSubmit={ () => {} }
			/>
		);

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( createMarket ).not.toHaveBeenCalled();
		expect( updateMarket ).toHaveBeenCalledWith(
			'fr',
			expect.objectContaining( { country: 'FR' } )
		);
		const [ , payload ] = updateMarket.mock.calls[ 0 ];
		expect( payload ).not.toHaveProperty( 'countries' );
	} );

	test( 'includes countries back into the payload when updating the primary market', async () => {
		mockSubmittedValues = {
			country: null,
			countries: [ 'US', 'CA' ],
		};
		const user = userEvent.setup();

		render(
			<MarketForm
				initialMarket={ {
					id: PRIMARY_MARKET_ID,
					countries: [ 'US', 'CA' ],
				} }
				onSubmit={ () => {} }
			/>
		);

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( updateMarket ).toHaveBeenCalledWith(
			PRIMARY_MARKET_ID,
			expect.objectContaining( { countries: [ 'US', 'CA' ] } )
		);
	} );

	test( 'does not invalidate getMarkets or call onSubmit when syncSettings fails', async () => {
		syncSettings.mockRejectedValue( new Error( 'sync failed' ) );
		const user = userEvent.setup();
		const onSubmit = jest.fn();

		render( <MarketForm onSubmit={ onSubmit } /> );

		await user.click( screen.getByRole( 'button', { name: 'Submit' } ) );

		expect( handleApiError ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).not.toHaveBeenCalled();
		expect( onSubmit ).not.toHaveBeenCalled();
	} );
} );

describe( 'MarketForm initial values', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useAppDispatch.mockReturnValue( {
			createMarket: jest.fn().mockResolvedValue(),
			updateMarket: jest.fn().mockResolvedValue(),
			syncSettings: jest.fn().mockResolvedValue(),
			invalidateResolution: jest.fn(),
		} );
	} );

	test( 'seeds rate, threshold, and time fields from initialMarket.shipping for flat markets', () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );

		render(
			<MarketForm
				initialMarket={ {
					id: 'fr',
					country: 'FR',
					shipping: {
						flat_rate: 8,
						free_shipping_threshold: 50,
						flat_time: 5,
						flat_max_time: 7,
					},
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_rate ).toBe( 8 );
		expect( initialValues.offer_free_shipping ).toBe( true );
		expect( initialValues.free_shipping_threshold ).toBe( 50 );
		expect( initialValues.flat_shipping_min_time ).toBe( 5 );
		expect( initialValues.flat_shipping_max_time ).toBe( 7 );
	} );

	test( 'seeds rate, threshold, and time fields from initialMarket.shipping for the primary market too', () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );

		render(
			<MarketForm
				initialMarket={ {
					id: PRIMARY_MARKET_ID,
					countries: [ 'US', 'CA' ],
					shipping: {
						flat_rate: 8,
						free_shipping_threshold: 50,
						flat_time: 5,
						flat_max_time: 7,
					},
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_rate ).toBe( 8 );
		expect( initialValues.offer_free_shipping ).toBe( true );
		expect( initialValues.free_shipping_threshold ).toBe( 50 );
		expect( initialValues.flat_shipping_min_time ).toBe( 5 );
		expect( initialValues.flat_shipping_max_time ).toBe( 7 );
	} );

	test( 'defaults offer_free_shipping to false and leaves the threshold undefined when none is set', () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );

		render(
			<MarketForm
				initialMarket={ {
					id: 'fr',
					country: 'FR',
					shipping: {
						flat_rate: 8,
						free_shipping_threshold: null,
						flat_time: 5,
						flat_max_time: 7,
					},
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.offer_free_shipping ).toBe( false );
		expect( initialValues.free_shipping_threshold ).toBeUndefined();
	} );

	test( 'seeds only shipping-time fields, omitting rate fields, for automatic markets', () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
				shipping_time: 'flat',
			},
		} );

		render(
			<MarketForm
				initialMarket={ {
					id: 'fr',
					country: 'FR',
					shipping: {
						flat_rate: 8,
						free_shipping_threshold: 50,
						flat_time: 5,
						flat_max_time: 7,
					},
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_min_time ).toBe( 5 );
		expect( initialValues.flat_shipping_max_time ).toBe( 7 );
		expect( initialValues ).not.toHaveProperty( 'flat_shipping_rate' );
		expect( initialValues ).not.toHaveProperty( 'offer_free_shipping' );
		expect( initialValues ).not.toHaveProperty( 'free_shipping_threshold' );
	} );

	test( 'falls back to the default min/max shipping time when the market has no stored time row', () => {
		// e.g. the store's global shipping time method is manual, so no
		// country has a time row, yet shipping_rate is flat/automatic and
		// still renders the time inputs. flat_time/flat_max_time come back
		// null from the API and must not overwrite the defaults with null.
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'manual',
			},
		} );

		render(
			<MarketForm
				initialMarket={ {
					id: 'fr',
					country: 'FR',
					shipping: {
						flat_rate: 8,
						free_shipping_threshold: null,
						flat_time: null,
						flat_max_time: null,
					},
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_min_time ).toBe( 1 );
		expect( initialValues.flat_shipping_max_time ).toBe( 5 );
	} );

	test( 'falls back to defaults when initialMarket has no shipping object yet, e.g. a brand new market', () => {
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );

		render(
			<MarketForm
				initialMarket={ { countries: [ 'US' ] } }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_rate ).toBeNull();
		expect( initialValues.offer_free_shipping ).toBe( false );
		expect( initialValues.flat_shipping_min_time ).toBe( 1 );
		expect( initialValues.flat_shipping_max_time ).toBe( 5 );
	} );
} );

describe( 'MarketForm handleChange', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useAppDispatch.mockReturnValue( {
			createMarket: jest.fn().mockResolvedValue(),
			updateMarket: jest.fn().mockResolvedValue(),
			syncSettings: jest.fn().mockResolvedValue(),
			invalidateResolution: jest.fn(),
		} );
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.FLAT,
				shipping_time: 'flat',
			},
		} );
	} );

	test( 'clears the free-shipping threshold and toggle when the flat rate changes to 0', () => {
		render( <MarketForm initialMarket={ {} } onSubmit={ () => {} } /> );

		const { onChange } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		onChange( { name: 'flat_shipping_rate', value: 0 } );

		expect( mockSetValue ).toHaveBeenCalledWith(
			'free_shipping_threshold',
			undefined
		);
		expect( mockSetValue ).toHaveBeenCalledWith(
			'offer_free_shipping',
			false
		);
	} );

	test( 'leaves other fields untouched when the flat rate changes to a non-zero value', () => {
		render( <MarketForm initialMarket={ {} } onSubmit={ () => {} } /> );

		const { onChange } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		onChange( { name: 'flat_shipping_rate', value: 5 } );

		expect( mockSetValue ).not.toHaveBeenCalled();
	} );

	test( 'clears the threshold when offer_free_shipping is turned off', () => {
		render( <MarketForm initialMarket={ {} } onSubmit={ () => {} } /> );

		const { onChange } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		onChange( { name: 'offer_free_shipping', value: false } );

		expect( mockSetValue ).toHaveBeenCalledWith(
			'free_shipping_threshold',
			undefined
		);
	} );

	test( 'does nothing when offer_free_shipping is turned on', () => {
		render( <MarketForm initialMarket={ {} } onSubmit={ () => {} } /> );

		const { onChange } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		onChange( { name: 'offer_free_shipping', value: true } );

		expect( mockSetValue ).not.toHaveBeenCalled();
	} );

	test( 'does nothing for changes to fields it does not react to', () => {
		render( <MarketForm initialMarket={ {} } onSubmit={ () => {} } /> );

		const { onChange } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		onChange( { name: 'flat_shipping_min_time', value: 3 } );

		expect( mockSetValue ).not.toHaveBeenCalled();
	} );
} );
