jest.mock( '.~/hooks/useExistingGoogleMCAccounts', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useExistingGoogleMCAccounts' )
		.mockImplementation( () => {
			return {
				data: [
					{ id: 1, name: 'Account 1', domain: 'https://example.com' },
					{
						id: 2,
						name: 'Account 2',
						domain: 'https://example2.com',
					},
				],
			};
		} ),
} ) );

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control/index';

describe( 'Merchant Center Select Control', () => {
	test( 'First option selected by default', () => {
		const { queryAllByRole } = render( <MerchantCenterSelectControl /> );
		const options = queryAllByRole( 'option' );
		expect( options ).toHaveLength( 2 );
		expect( options[ 0 ] ).toHaveAttribute( 'value', '1' );
	} );

	test( 'Calls onChange function on init with the default value', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		render( <MerchantCenterSelectControl onChange={ onChange } /> );
		expect( onChange ).toHaveBeenCalledWith( 1 );
	} );

	test( 'When a value is defined, it doesnt call onChange init method', () => {
		const onChange = jest.fn().mockName( 'onChange' );
		render(
			<MerchantCenterSelectControl value="2" onChange={ onChange } />
		);
		expect( onChange ).not.toHaveBeenCalled();
	} );
} );
