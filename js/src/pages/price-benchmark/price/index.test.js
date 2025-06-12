/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Price from './index';

// Mock the useAdsCurrency hook
jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn( ( amount ) => `$${ amount.toFixed( 2 ) }` ),
	} )
);

describe( 'Price Component', () => {
	it( 'renders the formatted price correctly', () => {
		const { getByText } = render( <Price amount={ 123.45 } /> );
		expect( getByText( '$123.45' ) ).toBeInTheDocument();
	} );

	it( 'renders with a highlight class when highlight is true', () => {
		const { container } = render(
			<Price amount={ 123.45 } highlight={ true } />
		);
		expect( container.firstChild ).toHaveClass(
			'gla-price-benchmark-table__price--highlight'
		);
	} );

	it( 'does not render with a highlight class when highlight is false', () => {
		const { container } = render(
			<Price amount={ 123.45 } highlight={ false } />
		);
		expect( container.firstChild ).not.toHaveClass(
			'gla-price-benchmark-table__price--highlight'
		);
	} );

	it( 'formats the amount as 0 when the amount is NaN', () => {
		const { getByText } = render( <Price amount={ NaN } /> );
		expect( getByText( '$0.00' ) ).toBeInTheDocument();
	} );
} );
