jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockName( 'useAdaptiveFormContext' ),
} ) );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GenAIProgressCard from './card';
import { useAdaptiveFormContext } from '~/components/adaptive-form';

describe( 'GenAIProgressCard', () => {
	let abortGenerateAssets;

	function mockAdapter( isGeneratingAssets ) {
		abortGenerateAssets = jest.fn();
		useAdaptiveFormContext.mockReturnValue( {
			adapter: { abortGenerateAssets, isGeneratingAssets },
		} );
	}

	test( 'Renders the title, text and progress bar inside a card', () => {
		mockAdapter( true );
		const { container } = render( <GenAIProgressCard /> );

		const card = container.querySelector( '.gen-ai-progress-card' );
		expect( card ).toHaveClass( 'components-card' );
		expect(
			screen.getByRole( 'heading', { name: 'Google AI suggestions' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Google AI is analyzing your campaign’s URL to automatically generate your ad assets'
			)
		).toBeInTheDocument();
		expect( screen.getByRole( 'progressbar' ) ).toBeInTheDocument();
	} );

	test( 'Renders the Google AI icon', () => {
		mockAdapter( true );
		render( <GenAIProgressCard /> );

		expect( screen.getByRole( 'img', { name: 'Google AI' } ) ).toHaveClass(
			'gen-ai-progress-card__icon'
		);
	} );

	test( 'Shows the Skip button while generating assets', () => {
		mockAdapter( true );
		render( <GenAIProgressCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Skip' } )
		).toBeInTheDocument();
	} );

	test( 'Hides the Skip button when not generating assets', () => {
		mockAdapter( false );
		render( <GenAIProgressCard /> );

		expect(
			screen.queryByRole( 'button', { name: 'Skip' } )
		).not.toBeInTheDocument();
	} );

	test( 'Calls abortGenerateAssets when the Skip button is clicked', async () => {
		mockAdapter( true );
		render( <GenAIProgressCard /> );

		await userEvent.click( screen.getByRole( 'button', { name: 'Skip' } ) );

		expect( abortGenerateAssets ).toHaveBeenCalledTimes( 1 );
	} );
} );
