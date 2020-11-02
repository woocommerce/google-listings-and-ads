/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Hello from './index';

describe( 'Hello component', () => {
	it( 'should return greetings with name', () => {
		const { getByText } = render( <Hello name="John" /> );

		expect( getByText( 'Hello, John!' ) ).toBeTruthy();
	} );
} );
