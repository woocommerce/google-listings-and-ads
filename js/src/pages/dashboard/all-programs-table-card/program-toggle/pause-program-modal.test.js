/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';

describe( 'PauseProgramModal', () => {
	it( 'should use US spelling for "optimizations"', () => {
		render( <PauseProgramModal onRequestClose={ jest.fn() } /> );

		expect(
			screen.getByText(
				'Pausing a campaign will result in the loss of any optimizations learned from those campaigns.'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( /optimisations/ ) ).not.toBeInTheDocument();
	} );
} );
