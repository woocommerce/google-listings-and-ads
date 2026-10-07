jest.mock( '../../hooks/useGoogleBusinessProfileLocations', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useGoogleBusinessProfileLocations' )
		.mockImplementation( () => ( {
			locations: [
				{
					id: '1111',
					title: "Jane's Bakery",
					address: '2423 1st Ave, Seattle, WA, 98121',
				},
				{
					id: '2222',
					title: "Jane's Bakery Riverside",
					address: '',
				},
			],
		} ) ),
} ) );

/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import GoogleBusinessProfileLocationSelectControl from './google-business-profile-location-select-control';

describe( 'GoogleBusinessProfileLocationSelectControl', () => {
	test( 'First option selected by default, labeled with the address, or the business name when there is none', () => {
		const { queryAllByRole } = render(
			<GoogleBusinessProfileLocationSelectControl />
		);
		const options = queryAllByRole( 'option' );
		expect( options ).toHaveLength( 2 );
		expect( options[ 0 ] ).toHaveAttribute( 'value', '1111' );
		expect( options[ 0 ] ).toHaveTextContent(
			'2423 1st Ave, Seattle, WA, 98121'
		);
		expect( options[ 1 ] ).toHaveTextContent( "Jane's Bakery Riverside" );
	} );

	test( 'Calls onChange function on init with the default location ID', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		render(
			<GoogleBusinessProfileLocationSelectControl onChange={ onChange } />
		);
		expect( onChange ).toHaveBeenCalledWith( '1111' );
	} );

	test( 'Call onChange method when the value changes', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByRole } = render(
			<GoogleBusinessProfileLocationSelectControl
				value="1111"
				onChange={ onChange }
			/>
		);
		fireEvent.change( queryByRole( 'combobox' ), {
			target: { value: '2222' },
		} );
		expect( onChange ).toHaveBeenCalledWith( '2222', expect.any( Object ) );
	} );
} );
