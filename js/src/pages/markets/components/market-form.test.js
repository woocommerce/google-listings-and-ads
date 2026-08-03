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
import { useAppDispatch } from '~/data';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useSaveShippingRates from '~/hooks/useSaveShippingRates';
import useSaveShippingTimes from '~/hooks/useSaveShippingTimes';
import useSettings from '~/hooks/useSettings';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import { handleApiError } from '~/utils/handleError';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );
jest.mock( '~/hooks/useShippingRates' );
jest.mock( '~/hooks/useShippingTimes' );
jest.mock( '~/hooks/useSaveShippingRates' );
jest.mock( '~/hooks/useSaveShippingTimes' );
jest.mock( '~/hooks/useSettings' );
jest.mock( '~/hooks/useStoreCurrency' );
jest.mock( '~/utils/handleError', () => ( {
	handleApiError: jest.fn(),
} ) );

const submittedValues = {
	country: 'US',
	countries: [ 'US' ],
	shipping_country_rates: [],
	shipping_country_times: [],
};

// AdaptiveForm's real submit/validation machinery isn't under test here;
// stub it down to a button that hands handleSubmit fixed form values.
jest.mock( '~/components/adaptive-form', () => {
	const { forwardRef: mockForwardRef } =
		jest.requireActual( '@wordpress/element' );

	return {
		__esModule: true,
		default: mockForwardRef( ( { onSubmit }, ref ) => (
			<button ref={ ref } onClick={ () => onSubmit( submittedValues ) }>
				Submit
			</button>
		) ),
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
