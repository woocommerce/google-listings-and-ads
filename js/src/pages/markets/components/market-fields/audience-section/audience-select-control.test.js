/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useStoreCurrency from '~/hooks/useStoreCurrency';
import useMCCountries from '~/hooks/useMCCountries';
import SupportedCountrySelect from '~/components/supported-country-select';
import useMarkets from '../../../hooks/useMarkets';
import AudienceSelectControl from './audience-select-control';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );

jest.mock( '~/hooks/useStoreCurrency' );
jest.mock( '~/hooks/useMCCountries' );
jest.mock( '../../../hooks/useMarkets' );

jest.mock( '~/components/supported-country-select', () =>
	jest.fn( () => <div data-testid="supported-country-select" /> )
);

describe( 'AudienceSelectControl', () => {
	beforeEach( () => {
		useStoreCurrency.mockReturnValue( { code: 'USD' } );
		useAdaptiveFormContext.mockReturnValue( {
			getInputProps: () => ( { value: [], onChange: jest.fn() } ),
			values: {},
			setValues: jest.fn(),
			adapter: { renderRequestedValidation: () => null },
		} );
		useMCCountries.mockReturnValue( {
			data: { US: {}, CA: {}, GB: {} },
			hasFinishedResolution: true,
		} );
		useMarkets.mockReturnValue( { data: [], hasFinishedResolution: true } );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'omits a country that a secondary market owns', () => {
		useMarkets.mockReturnValue( {
			data: [
				{ id: 'primary', country: null },
				{ id: 'gb', country: 'GB' },
			],
			hasFinishedResolution: true,
		} );

		render( <AudienceSelectControl /> );

		const { countryCodes } = SupportedCountrySelect.mock.calls[ 0 ][ 0 ];

		expect( countryCodes ).toEqual( [ 'US', 'CA' ] );
	} );

	test( 'offers every supported country when no secondary market owns one', () => {
		render( <AudienceSelectControl /> );

		const { countryCodes } = SupportedCountrySelect.mock.calls[ 0 ][ 0 ];

		expect( countryCodes ).toEqual( [ 'US', 'CA', 'GB' ] );
	} );

	test( 'leaves the country list undefined until the supported countries resolve', () => {
		// Passing an empty array here would render a select with no countries at all.
		useMCCountries.mockReturnValue( {
			data: undefined,
			hasFinishedResolution: false,
		} );

		render( <AudienceSelectControl /> );

		const { countryCodes } = SupportedCountrySelect.mock.calls[ 0 ][ 0 ];

		expect( countryCodes ).toBeUndefined();
	} );
} );
