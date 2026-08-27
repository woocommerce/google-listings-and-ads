jest.mock( '../hooks/useGoogleTagManagerContainers', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useGoogleTagManagerContainers' )
		.mockImplementation( () => ( {
			containers: [
				{
					id: '111',
					publicId: 'GTM-AAA111',
					name: 'Container 1',
				},
				{
					id: '222',
					publicId: 'GTM-BBB222',
					name: 'Container 2',
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
import GoogleTagManagerContainerSelectControl from './google-tag-manager-container-select-control';

describe( 'GoogleTagManagerContainerSelectControl', () => {
	test( 'First option selected by default, labeled with the public (GTM-XXXXXXX) ID', () => {
		const { queryAllByRole } = render(
			<GoogleTagManagerContainerSelectControl />
		);
		const options = queryAllByRole( 'option' );
		expect( options ).toHaveLength( 2 );
		expect( options[ 0 ] ).toHaveAttribute( 'value', '111' );
		expect( options[ 0 ] ).toHaveTextContent( 'Container 1 (GTM-AAA111)' );
	} );

	test( 'Calls onChange function on init with the default (internal) container ID', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		render(
			<GoogleTagManagerContainerSelectControl onChange={ onChange } />
		);
		expect( onChange ).toHaveBeenCalledWith( '111' );
	} );

	test( 'Call onChange method when the value changes', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		const { queryByRole } = render(
			<GoogleTagManagerContainerSelectControl
				value="111"
				onChange={ onChange }
			/>
		);
		fireEvent.change( queryByRole( 'combobox' ), {
			target: { value: '222' },
		} );
		expect( onChange ).toHaveBeenCalledWith( '222', expect.any( Object ) );
	} );
} );
