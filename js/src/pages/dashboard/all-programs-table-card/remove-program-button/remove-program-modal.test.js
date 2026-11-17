/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import RemoveProgramModal from './remove-program-modal';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );

describe( 'RemoveProgramModal', () => {
	it( 'should use US spelling for "optimizations"', () => {
		render(
			<RemoveProgramModal
				programId={ 123 }
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByText(
				'Results typically improve with time. Removing a campaign will result in the loss of any optimizations learned from those campaigns.'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( /optimisations/ ) ).not.toBeInTheDocument();
	} );
} );
