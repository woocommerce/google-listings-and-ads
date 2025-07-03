/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, fireEvent, waitFor, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import YouTubeVideoInputControl from './youtube-video-input-control';

// Helper for filling input and clicking button
const fillAndSubmit = async ( url ) => {
	const input = screen.getByRole( 'textbox' );
	fireEvent.change( input, { target: { value: url } } );
	const button = screen.getByRole( 'button' );
	fireEvent.click( button );
};

describe( 'YouTubeVideoInputControl', () => {
	afterEach( () => {
		jest.resetAllMocks();
	} );

	it( 'renders input and button', () => {
		const { getByRole } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);

		expect( getByRole( 'textbox' ) ).toBeInTheDocument();
		expect( getByRole( 'button' ) ).toBeInTheDocument();
	} );

	it( 'disables button when input is empty', () => {
		const { getByRole } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);

		expect( getByRole( 'button' ) ).toBeDisabled();
	} );

	it( 'shows error for invalid YouTube URL', async () => {
		const { findByText } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);
		await fillAndSubmit( 'https://invalid-url.com' );
		const errorMessage = await findByText( 'Invalid YouTube URL' );

		expect( errorMessage ).toBeInTheDocument();
	} );

	it( 'calls onVideoAdded with correct data on valid input', async () => {
		const mockOnVideoAdded = jest.fn();
		render(
			<YouTubeVideoInputControl onVideoAdded={ mockOnVideoAdded } />
		);
		await fillAndSubmit( 'https://www.youtube.com/watch?v=abcdefghijk' );

		await waitFor( () => {
			expect( mockOnVideoAdded ).toHaveBeenCalledWith( 'abcdefghijk' );
		} );
	} );

	it( 'accepts youtu.be short URLs', async () => {
		const mockOnVideoAdded = jest.fn();
		render(
			<YouTubeVideoInputControl onVideoAdded={ mockOnVideoAdded } />
		);
		await fillAndSubmit( 'https://youtu.be/abcdefghijk' );

		await waitFor( () => {
			expect( mockOnVideoAdded ).toHaveBeenCalledWith( 'abcdefghijk' );
		} );
	} );

	it( 'clears error when input is cleared', async () => {
		const { findByText, getByRole, queryByText } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);
		await fillAndSubmit( 'invalid-url' );

		const errorMessage = await findByText( 'Invalid YouTube URL' );
		expect( errorMessage ).toBeInTheDocument();

		const input = getByRole( 'textbox' );
		fireEvent.change( input, { target: { value: '' } } );

		await waitFor( () => {
			expect(
				queryByText( 'Invalid YouTube URL' )
			).not.toBeInTheDocument();
		} );
	} );
} );
