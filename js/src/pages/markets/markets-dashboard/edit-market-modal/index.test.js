/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import EditMarketModal from './';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );
jest.mock( '~/hooks/useSettings' );
jest.mock( '~/hooks/useShippingRates' );
jest.mock( '~/hooks/useShippingTimes' );
jest.mock( '~/hooks/useTargetAudienceFinalCountryCodes' );
jest.mock( './edit-primary-audience', () => {
	const { useEffect } = require( '@wordpress/element' );
	const { useAdaptiveFormContext } = require( '~/components/adaptive-form' );

	return function EditPrimaryAudienceStub() {
		const { setValue } = useAdaptiveFormContext();

		useEffect( () => {
			setValue( 'countries', [ 'US', 'CA' ] );
		}, [] );

		return null;
	};
} );

const market = { id: 'primary', label: 'Primary Market' };
const targetAudience = { countries: [ 'US' ] };

describe( 'EditMarketModal', () => {
	beforeEach( () => {
		useAppDispatch.mockReturnValue( {
			updateMarket: jest.fn().mockResolvedValue(),
		} );
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
		useShippingRates.mockReturnValue( {
			data: [
				{
					id: 'rate-us',
					country: 'US',
					currency: 'USD',
					rate: 10,
					options: { free_shipping_threshold: 50 },
				},
			],
			hasFinishedResolution: true,
		} );
		useShippingTimes.mockReturnValue( {
			data: [
				{
					countryCode: 'US',
					time: 0,
					maxTime: 3,
				},
			],
			hasFinishedResolution: true,
		} );
		useTargetAudienceFinalCountryCodes.mockReturnValue( {
			targetAudience: {
				location: 'selected',
				countries: [ 'US' ],
			},
			getFinalCountries: ( ta ) => ta?.countries ?? [],
			loading: false,
		} );
	} );

	test( 'renders the title for the primary market', () => {
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit primary market' } )
		).toBeInTheDocument();
	} );

	test( 'renders the estimated shipping rates block', () => {
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
		);

		expect(
			screen.getByText( 'Estimated shipping rates' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Free shipping over a specific order value',
			} )
		).toBeInTheDocument();
		const costInput = screen.getByRole( 'textbox', {
			name: 'Cost',
		} );
		expect( costInput ).toBeInTheDocument();
		expect( costInput ).toHaveDisplayValue( /50/ );
	} );

	test( 'renders the estimated shipping times block', () => {
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
		);

		expect(
			screen.getByText( 'Estimated shipping times' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Delivery times apply per country, regardless of language or currency.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'to' ) ).toBeInTheDocument();
		expect( screen.getByDisplayValue( '3' ) ).toBeInTheDocument();
	} );

	test( 'dispatches updateMarket with countries and shipping payloads when Save is clicked', async () => {
		const user = userEvent.setup();
		const updateMarket = jest.fn().mockResolvedValue();
		useAppDispatch.mockReturnValue( {
			updateMarket,
		} );
		const onRequestClose = jest.fn();

		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ onRequestClose }
			/>
		);

		const saveButton = screen.getByRole( 'button', { name: 'Save' } );

		await waitFor( () => {
			expect( saveButton ).not.toBeDisabled();
		} );

		await user.click( saveButton );

		await waitFor( () => {
			expect( updateMarket ).toHaveBeenCalledWith( 'primary', {
				countries: [ 'US', 'CA' ],
				shippingRates: [
					{
						id: 'rate-us',
						country: 'US',
						currency: 'USD',
						rate: 10,
						options: { free_shipping_threshold: 50 },
					},
					{
						id: undefined,
						country: 'CA',
						currency: 'USD',
						rate: 10,
						options: { free_shipping_threshold: 50 },
					},
				],
				shippingTimes: [
					{
						countryCode: 'US',
						time: 0,
						maxTime: 3,
					},
					{
						countryCode: 'CA',
						time: 0,
						maxTime: 3,
					},
				],
			} );
		} );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'invokes onRequestClose when the footer Close button is clicked', async () => {
		const user = userEvent.setup();
		const onRequestClose = jest.fn();
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ onRequestClose }
			/>
		);

		// `getByRole('button', { name: 'Cancel' })` matches both the
		// `<Modal>`'s X button (aria-label) and the footer button. Use the
		// `is-tertiary` variant class to target only our footer button.
		const footerCancelButton = document.querySelector(
			'.app-modal__footer .is-tertiary'
		);
		await user.click( footerCancelButton );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );
} );
