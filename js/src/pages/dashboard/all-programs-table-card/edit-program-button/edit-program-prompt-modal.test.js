/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import EditProgramPromptModal from './edit-program-prompt-modal';

describe( 'EditProgramPromptModal', () => {
	it( 'should use US spelling for "optimizations"', () => {
		render(
			<EditProgramPromptModal
				programId={ 123 }
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByText(
				'Editing will result in the loss of any optimizations learned over time.'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( /optimisations/ ) ).not.toBeInTheDocument();
	} );
} );
