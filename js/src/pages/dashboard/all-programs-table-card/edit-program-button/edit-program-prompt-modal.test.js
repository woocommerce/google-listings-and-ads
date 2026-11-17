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
	it( 'should show the three paragraphs with US spelling', () => {
		render(
			<EditProgramPromptModal
				programId={ 123 }
				onRequestClose={ jest.fn() }
			/>
		);

		const paragraphs = screen.getByRole( 'dialog' ).querySelectorAll( 'p' );

		expect( [ ...paragraphs ].map( ( p ) => p.textContent ) ).toEqual( [
			'Results typically improve with time.',
			'Editing will result in the loss of any optimizations learned over time.',
			'We recommend allowing your programs to run for at least 14 days after set up, without pausing or editing, for optimal performance.',
		] );
	} );
} );
