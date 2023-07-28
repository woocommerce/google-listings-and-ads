/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ValidationErrors from './validation-errors';

describe( 'ValidationErrors', () => {
	it.each( [ undefined, null, '', [] ] )(
		'Should be empty DOM element when `messages` is `%s`',
		( messages ) => {
			const { container } = render(
				<ValidationErrors messages={ messages } />
			);
			expect( container ).toBeEmptyDOMElement();
		}
	);

	it( 'Should support passing a string to render a message', () => {
		render( <ValidationErrors messages={ 'amount is required' } /> );

		const list = screen.getByRole( 'list' );
		const item = screen.getByRole( 'listitem' );

		expect( list ).toBeInTheDocument();
		expect( item ).toBeInTheDocument();
		expect( item ).toHaveTextContent( 'amount is required' );
	} );

	it( 'Should support passing an array to render a message', () => {
		render( <ValidationErrors messages={ [ 'amount is required' ] } /> );

		const list = screen.getByRole( 'list' );
		const item = screen.getByRole( 'listitem' );

		expect( list ).toBeInTheDocument();
		expect( item ).toBeInTheDocument();
		expect( item ).toHaveTextContent( 'amount is required' );
	} );

	it( 'Should support passing an array to render multiple messages', () => {
		const messages = [
			'amount should be greater than 0',
			'amount should be an integer',
		];

		render( <ValidationErrors messages={ messages } /> );

		const list = screen.getByRole( 'list' );
		const items = screen.getAllByRole( 'listitem' );

		expect( list ).toBeInTheDocument();
		expect( items.length ).toBe( messages.length );

		messages.forEach( ( message, i ) => {
			expect( items[ i ] ).toBeInTheDocument();
			expect( items[ i ] ).toHaveTextContent( message );
		} );
	} );
} );
