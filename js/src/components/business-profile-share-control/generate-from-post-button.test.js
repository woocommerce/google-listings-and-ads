/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GenerateFromPostButton from './generate-from-post-button';

describe( 'GenerateFromPostButton', () => {
	it( 'fills an empty field straight away, with no confirmation', async () => {
		const user = userEvent.setup();
		const onGenerate = jest.fn();
		render(
			<GenerateFromPostButton summary="" onGenerate={ onGenerate } />
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect( onGenerate ).toHaveBeenCalledTimes( 1 );
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'asks before replacing existing text, and replaces it on "Replace"', async () => {
		const user = userEvent.setup();
		const onGenerate = jest.fn();
		render(
			<GenerateFromPostButton
				summary="My own text"
				onGenerate={ onGenerate }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Replace your text?' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Your edits will be replaced with a new version from your post.'
			)
		).toBeInTheDocument();
		expect( onGenerate ).not.toHaveBeenCalled();

		await user.click( screen.getByRole( 'button', { name: 'Replace' } ) );

		expect( onGenerate ).toHaveBeenCalledTimes( 1 );
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'keeps the existing text on "Cancel"', async () => {
		const user = userEvent.setup();
		const onGenerate = jest.fn();
		render(
			<GenerateFromPostButton
				summary="My own text"
				onGenerate={ onGenerate }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Generate from post' } )
		);
		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onGenerate ).not.toHaveBeenCalled();
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );
} );
