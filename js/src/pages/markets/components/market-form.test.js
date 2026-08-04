/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

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

// MarketForm renders its fields via AdaptiveForm (and attaches a ref to it);
// mock it as a forwardRef spy so we can inspect the `initialValues` it's given
// without needing a real form context or triggering a "function components
// cannot be given refs" warning.
const mockAdaptiveForm = jest.fn( () => null );
jest.mock( '~/components/adaptive-form', () => {
	const { forwardRef } = jest.requireActual( '@wordpress/element' );
	return forwardRef( ( props, ref ) => mockAdaptiveForm( props, ref ) );
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
