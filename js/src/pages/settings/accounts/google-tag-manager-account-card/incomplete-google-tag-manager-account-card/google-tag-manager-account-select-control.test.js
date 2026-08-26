jest.mock( '~/hooks/useGoogleTagManagerAccount', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useGoogleTagManagerAccount' )
		.mockImplementation( () => ( {
			account: {
				accounts: [
					{ accountId: '1', name: 'Account 1' },
					{ accountId: '2', name: 'Account 2' },
				],
			},
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
import GoogleTagManagerAccountSelectControl from './google-tag-manager-account-select-control';

describe( 'GoogleTagManagerAccountSelectControl', () => {
	test( 'First option selected by default', () => {
		const { queryAllByRole } = render(
			<GoogleTagManagerAccountSelectControl />
		);
		const options = queryAllByRole( 'option' );
		expect( options ).toHaveLength( 2 );
		expect( options[ 0 ] ).toHaveAttribute( 'value', '1' );
	} );

	test( 'Calls onChange function on init with the default value', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		render(
			<GoogleTagManagerAccountSelectControl onChange={ onChange } />
		);
		expect( onChange ).toHaveBeenCalledWith( '1' );
	} );

	test( 'Call onChange method when the value changes', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByRole } = render(
			<GoogleTagManagerAccountSelectControl
				value="1"
				onChange={ onChange }
			/>
		);
		fireEvent.change( queryByRole( 'combobox' ), {
			target: { value: '2' },
		} );
		expect( onChange ).toHaveBeenCalledWith( '2', expect.any( Object ) );
	} );
} );
