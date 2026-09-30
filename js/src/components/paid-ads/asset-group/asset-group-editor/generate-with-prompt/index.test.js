/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GenerateWithPrompt from './index';
import GenerateWithPromptModal from './generate-with-prompt-modal';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( './generate-with-prompt-modal', () =>
	jest
		.fn( ( { onRequestClose } ) => (
			<div role="dialog">
				<button onClick={ onRequestClose }>Close modal</button>
			</div>
		) )
		.mockName( 'GenerateWithPromptModal' )
);

jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );

describe( 'GenerateWithPrompt', () => {
	const finalUrl = 'https://example.com';
	const assetKey = 'marketing_image';
	const ariaLabel = 'Generate a landscape image with prompt';

	const renderComponent = () =>
		render(
			<GenerateWithPrompt
				finalUrl={ finalUrl }
				assetKey={ assetKey }
				buttonLabel="Generate with prompt"
				buttonAriaLabel={ ariaLabel }
			/>
		);

	const getButton = () => screen.getByRole( 'button', { name: ariaLabel } );

	it( 'Should render the button with the given accessible label', () => {
		renderComponent();

		expect( getButton() ).toHaveTextContent( 'Generate with prompt' );
		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );

	it( 'Should record an event when the button is clicked', async () => {
		renderComponent();

		await userEvent.click( getButton() );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_gen_ai_generate_with_prompt_click',
			{ asset_key: assetKey }
		);
	} );

	it( 'Should open the modal with the final URL and asset key when the button is clicked', async () => {
		renderComponent();

		await userEvent.click( getButton() );

		expect( screen.getByRole( 'dialog' ) ).toBeInTheDocument();
		expect( GenerateWithPromptModal ).toHaveBeenCalledWith(
			expect.objectContaining( { finalUrl, assetKey } ),
			{}
		);
	} );

	it( 'Should close the modal when it requests to close', async () => {
		renderComponent();

		await userEvent.click( getButton() );
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Close modal' } )
		);

		expect( screen.queryByRole( 'dialog' ) ).not.toBeInTheDocument();
	} );
} );
