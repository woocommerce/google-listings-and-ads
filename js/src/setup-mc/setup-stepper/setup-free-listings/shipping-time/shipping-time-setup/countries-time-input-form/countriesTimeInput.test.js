/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CountriesTimeInputForm from './';

jest.mock( '.~/hooks/useStoreCurrency', () =>
	jest.fn( () => ( {
		code: 'GBP',
	} ) )
);

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest.fn( () => ( {
		GB: 'United Kingdom',
		US: 'United States',
		ES: 'Spain',
	} ) )
);

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () => () => ( {
	data: { selectedCountryCodes: [ 'ES' ] },
} ) );

const mockupsertShippingTimes = jest.fn();

jest.mock( '.~/data', () => ( {
	useAppDispatch: () => ( {
		upsertShippingTimes: mockupsertShippingTimes,
	} ),
} ) );

describe( 'CountriesTimeInput', () => {
	const defaultProps = {
		savedValue: {
			countries: [ 'ES' ],
			time: '1',
		},
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Check if the saved time value is set in the input', () => {
		render( <CountriesTimeInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( defaultProps.savedValue.time );
	} );

	test( 'Check if the new value is updated without using the saved value & upsertShippingTimes is called', () => {
		render( <CountriesTimeInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( defaultProps.savedValue.time );

		userEvent.clear( input );
		userEvent.type( input, '2' );

		fireEvent.blur( input );

		expect( input ).toHaveValue( '2' );
		expect( mockupsertShippingTimes ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Check when the saved time value is null and it has not been edited & upsertShippingTimes is called', () => {
		render(
			<CountriesTimeInputForm
				savedValue={ { ...defaultProps.savedValue, time: null } }
			/>
		);

		const input = screen.getByRole( 'textbox' );
		fireEvent.blur( input );
		expect( input ).toHaveValue( '0' );
		expect( mockupsertShippingTimes ).toHaveBeenCalledTimes( 1 );
	} );
} );
