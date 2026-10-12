/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SummaryField from './summary-field';

describe( 'SummaryField', () => {
	it( 'shows the text, the live character count and the limit', () => {
		render( <SummaryField value="Hello" onChange={ jest.fn() } /> );

		expect(
			screen.getByRole( 'textbox', { name: 'Summary' } )
		).toHaveValue( 'Hello' );
		expect( screen.getByText( '5/1,500 characters' ) ).toBeInTheDocument();
	} );

	it( 'shows the fixed tip next to the label', () => {
		render( <SummaryField value="" onChange={ jest.fn() } /> );

		expect(
			screen.getByRole( 'img', {
				name: 'Place critical promotional copy within the first 80–100 characters.',
			} )
		).toBeInTheDocument();
	} );

	it( 'reports new text, cut at the character limit', () => {
		const onChange = jest.fn();
		render( <SummaryField value="" onChange={ onChange } /> );

		fireEvent.change( screen.getByRole( 'textbox', { name: 'Summary' } ), {
			target: { value: 'a'.repeat( 1600 ) },
		} );

		expect( onChange ).toHaveBeenCalledWith( 'a'.repeat( 1500 ) );
	} );

	it( 'can be disabled', () => {
		render( <SummaryField value="" onChange={ jest.fn() } disabled /> );

		expect(
			screen.getByRole( 'textbox', { name: 'Summary' } )
		).toBeDisabled();
	} );
} );
