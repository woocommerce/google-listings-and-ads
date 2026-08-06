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
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSettings from '~/hooks/useSettings';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/hooks/useShippingRates' );
jest.mock( '~/hooks/useShippingTimes' );
jest.mock( '~/hooks/useSettings' );
jest.mock( '~/hooks/useStoreCurrency' );
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes' );
jest.mock( '~/hooks/useSaveShippingRates' );
jest.mock( '~/hooks/useSaveShippingTimes' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

const submittedValues = {
	country: 'US',
	countries: [ 'US' ],
	shipping_country_rates: [],
	shipping_country_times: [],
};

// MarketForm renders its fields via AdaptiveForm (and attaches a ref to it);
// mock it as a forwardRef spy so we can both inspect the `initialValues` it's
// given and simulate a submit by clicking the rendered button, without
// needing a real form context.
const mockAdaptiveForm = jest.fn();
jest.mock( '~/components/adaptive-form', () => {
	const { forwardRef: mockForwardRef } =
		jest.requireActual( '@wordpress/element' );

	return {
		__esModule: true,
		default: mockForwardRef( ( props, ref ) => {
			mockAdaptiveForm( props, ref );
			return (
				<button
					ref={ ref }
					onClick={ () => props.onSubmit( submittedValues ) }
				>
					Submit
				</button>
			);
		} ),
	};
} );

const PRIMARY_MARKET = { id: 'primary', label: 'Primary Market' };

describe( 'MarketForm', () => {
	beforeEach( () => {
		mockAdaptiveForm.mockClear();
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat', shipping_time: 'flat' },
		} );
		useStoreCurrency.mockReturnValue( { code: 'USD' } );
		useSaveShippingRates.mockReturnValue( {
			saveShippingRates: jest.fn(),
		} );
		useSaveShippingTimes.mockReturnValue( {
			saveShippingTimes: jest.fn(),
		} );
		useAppDispatch.mockReturnValue( {
			createMarket: jest.fn(),
			updateMarket: jest.fn(),
			syncSettings: jest.fn(),
			invalidateResolution: jest.fn(),
		} );
	} );

	test( 'seeds the primary market rate/time from the main target country, not the alphabetically-first row', () => {
		// CA sorts before US, but US is the store's main target country and
		// carries a different rate — the bug picked CA's row instead.
		useShippingRates.mockReturnValue( {
			data: [
				{ country: 'CA', rate: 5, options: {} },
				{ country: 'US', rate: 3, options: {} },
			],
			hasFinishedResolution: true,
		} );
		useShippingTimes.mockReturnValue( {
			data: [
				{ countryCode: 'CA', time: 7, maxTime: 14 },
				{ countryCode: 'US', time: 1, maxTime: 3 },
			],
			hasFinishedResolution: true,
		} );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			targetAudience: { main_target_country: 'US' },
			loaded: true,
		} );

		render(
			<MarketForm
				initialMarket={ {
					...PRIMARY_MARKET,
					countries: [ 'CA', 'US' ],
				} }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_rate ).toBe( 3 );
		expect( initialValues.flat_shipping_min_time ).toBe( 1 );
		expect( initialValues.flat_shipping_max_time ).toBe( 3 );
	} );

	test( 'falls back to the first shipping rate/time row when no main target country is known', () => {
		useShippingRates.mockReturnValue( {
			data: [ { country: 'CA', rate: 5, options: {} } ],
			hasFinishedResolution: true,
		} );
		useShippingTimes.mockReturnValue( {
			data: [ { countryCode: 'CA', time: 7, maxTime: 14 } ],
			hasFinishedResolution: true,
		} );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			targetAudience: {},
			loaded: true,
		} );

		render(
			<MarketForm
				initialMarket={ { ...PRIMARY_MARKET, countries: [ 'CA' ] } }
				onSubmit={ () => {} }
			/>
		);

		const { initialValues } = mockAdaptiveForm.mock.calls[ 0 ][ 0 ];
		expect( initialValues.flat_shipping_rate ).toBe( 5 );
		expect( initialValues.flat_shipping_min_time ).toBe( 7 );
	} );

	test( 'renders AppSpinner instead of seeding the form while target audience has not resolved', () => {
		// If the form seeded its rate/time fields before target audience
		// resolves, main_target_country would be undefined and it would
		// silently fall back to the (possibly wrong) first row.
		useShippingRates.mockReturnValue( {
			data: [ { country: 'CA', rate: 5, options: {} } ],
			hasFinishedResolution: true,
		} );
		useShippingTimes.mockReturnValue( {
			data: [ { countryCode: 'CA', time: 7, maxTime: 14 } ],
			hasFinishedResolution: true,
		} );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			targetAudience: {},
			loaded: false,
		} );

		render(
			<MarketForm
				initialMarket={ { ...PRIMARY_MARKET, countries: [ 'CA' ] } }
				onSubmit={ () => {} }
			/>
		);

		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
		expect( mockAdaptiveForm ).not.toHaveBeenCalled();
	} );
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
		useShippingRates.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );
		useShippingTimes.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );
		useSaveShippingRates.mockReturnValue( {
			saveShippingRates: jest.fn(),
		} );
		useSaveShippingTimes.mockReturnValue( {
			saveShippingTimes: jest.fn(),
		} );
		useSettings.mockReturnValue( {
			settings: {
				shipping_rate: SHIPPING_RATE_METHOD.MANUAL,
				shipping_time: 'manual',
			},
		} );
		useStoreCurrency.mockReturnValue( { code: 'USD' } );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			targetAudience: { main_target_country: 'US' },
			loaded: true,
		} );
	} );

	test( 'invalidates both getTargetAudience and getMarkets after a successful save, since saving shipping rates/times can change which countries are split into their own derived markets', async () => {
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
