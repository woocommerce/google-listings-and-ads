/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSettings from '.~/components/free-listings/configure-product-listings/useSettings';
import useMCSetup from '.~/hooks/useMCSetup';
import ShippingRateSection from './shipping-rate-section';
//import FlatShippingRatesInputCards from './flat-shipping-rates-input-cards';

jest.mock( './flat-shipping-rates-input-cards', () => () => <></> );

jest.mock( '.~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest
		.fn()
		.mockName( 'useAdaptiveFormContext' )
		.mockImplementation( () => {
			return {
				getInputProps: () => {
					return {
						checked: true,
						className: '',
						help: null,
						onBlur: () => {},
						onChange: () => {},
						selected: 'flat',
						value: 'flat',
					};
				},
				values: {
					countries: [ 'ES' ],
					language: 'English',
					locale: 'en_US',
					location: 'selected',
					offer_free_shipping: false,
					shipping_country_rates: [],
					shipping_country_times: [],
					shipping_rate: 'flat',
					shipping_time: 'flat',
					tax_rate: null,
				},
			};
		} ),
} ) );

jest.mock(
	'.~/components/free-listings/configure-product-listings/useSettings'
);
jest.mock( '.~/hooks/useMCSetup' );

describe( 'ShippingRateSection', () => {
	it( 'shouldnt render automatic rates if there are not shipping rates and it is onboarding', () => {
		useMCSetup.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: {
					status: 'incomplete',
				},
			};
		} );

		useSettings.mockImplementation( () => {
			return {
				settings: {
					shipping_rates_count: 0,
				},
			};
		} );

		const { getByText, queryByRole } = render( <ShippingRateSection /> );

		expect(
			getByText(
				'My shipping settings are simple. I can manually estimate flat shipping rates.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.'
			)
		).toBeInTheDocument();

		expect(
			queryByRole(
				'Automatically sync my store’s shipping settings to Google.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'should render automatic rates if there are shipping rates and it is onboarding', () => {
		useMCSetup.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: {
					status: 'incomplete',
				},
			};
		} );

		useSettings.mockImplementation( () => {
			return {
				settings: {
					shipping_rates_count: 1,
				},
			};
		} );

		const { getByText } = render( <ShippingRateSection /> );

		expect(
			getByText(
				'My shipping settings are simple. I can manually estimate flat shipping rates.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'Automatically sync my store’s shipping settings to Google.'
			)
		).toBeInTheDocument();
	} );

	it( 'should render automatic rates if there are not shipping rates and it is not onboarding', () => {
		useMCSetup.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: {
					status: 'completed',
				},
			};
		} );

		useSettings.mockImplementation( () => {
			return {
				settings: {
					shipping_rates_count: 0,
				},
			};
		} );

		const { getByText } = render( <ShippingRateSection /> );

		expect(
			getByText(
				'My shipping settings are simple. I can manually estimate flat shipping rates.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'Automatically sync my store’s shipping settings to Google.'
			)
		).toBeInTheDocument();
	} );

	it( 'should render automatic rates if there are shipping rates and it is not onboarding', () => {
		useMCSetup.mockImplementation( () => {
			return {
				hasFinishedResolution: true,
				data: {
					status: 'completed',
				},
			};
		} );

		useSettings.mockImplementation( () => {
			return {
				settings: {
					shipping_rates_count: 1,
				},
			};
		} );

		const { getByText } = render( <ShippingRateSection /> );

		expect(
			getByText(
				'My shipping settings are simple. I can manually estimate flat shipping rates.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.'
			)
		).toBeInTheDocument();

		expect(
			getByText(
				'Automatically sync my store’s shipping settings to Google.'
			)
		).toBeInTheDocument();
	} );
} );
