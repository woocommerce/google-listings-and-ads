/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GenAIPromptControl, { MAX_PROMPT_LENGTH } from './index';

describe( 'GenAIPromptControl', () => {
	it( 'renders the placeholder and the raw character count', () => {
		render(
			<GenAIPromptControl
				value=""
				onChange={ jest.fn() }
				placeholder="Example: Make the background blue"
			/>
		);

		expect(
			screen.getByPlaceholderText( 'Example: Make the background blue' )
		).toBeInTheDocument();
		expect(
			screen.getByText( `0/${ MAX_PROMPT_LENGTH } characters` )
		).toBeInTheDocument();
	} );

	it( 'calls onChange with the new value', () => {
		const onChange = jest.fn();
		render( <GenAIPromptControl value="" onChange={ onChange } /> );

		fireEvent.change( screen.getByRole( 'textbox' ), {
			target: { value: 'Add a red hat' },
		} );

		expect( onChange ).toHaveBeenCalledWith( 'Add a red hat' );
	} );

	it( 'reflects the over-limit state once the value exceeds the max length', () => {
		const overLimitValue = 'a'.repeat( MAX_PROMPT_LENGTH + 1 );
		const { container } = render(
			<GenAIPromptControl
				value={ overLimitValue }
				onChange={ jest.fn() }
			/>
		);

		expect(
			screen.getByText(
				`${ overLimitValue.length }/${ MAX_PROMPT_LENGTH } characters`
			)
		).toBeInTheDocument();
		expect(
			container.querySelector( '.gla-gen-ai-prompt-control--error' )
		).toBeInTheDocument();
	} );

	it( 'disables the field when disabled', () => {
		render(
			<GenAIPromptControl value="" onChange={ jest.fn() } disabled />
		);

		expect( screen.getByRole( 'textbox' ) ).toBeDisabled();
	} );
} );
