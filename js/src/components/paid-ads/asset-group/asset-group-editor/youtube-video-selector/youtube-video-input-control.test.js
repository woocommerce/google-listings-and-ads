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

	it( 'shows error if fetch fails (non-ok response)', async () => {
		global.fetch = jest.fn().mockResolvedValue( { ok: false } );
		const { findByText } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);
		await fillAndSubmit( 'https://www.youtube.com/watch?v=abcdefghijk' );
		const errorMessage = await findByText(
			'Failed to fetch video details'
		);

		expect( errorMessage ).toBeInTheDocument();
	} );

	it( 'shows error if fetch throws', async () => {
		global.fetch = jest
			.fn()
			.mockRejectedValue( new Error( 'Network error' ) );
		const { findByText } = render(
			<YouTubeVideoInputControl onVideoAdded={ jest.fn() } />
		);
		await fillAndSubmit( 'https://www.youtube.com/watch?v=abcdefghijk' );
		const errorMessage = await findByText(
			/Failed to fetch video details\. Please check the URL and try again\. Error: Network error/
		);

		expect( errorMessage ).toBeInTheDocument();
	} );

	it( 'calls onVideoAdded with correct data on valid input', async () => {
		const mockOnVideoAdded = jest.fn();
		const mockData = {
			title: 'Test Video',
			thumbnail_url:
				'https://img.youtube.com/vi/abcdefghijk/hqdefault.jpg',
		};
		global.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: async () => mockData,
		} );

		render(
			<YouTubeVideoInputControl onVideoAdded={ mockOnVideoAdded } />
		);
		await fillAndSubmit( 'https://www.youtube.com/watch?v=abcdefghijk' );
		await waitFor( () => {
			expect( mockOnVideoAdded ).toHaveBeenCalledWith( {
				title: mockData.title,
				thumbnail: mockData.thumbnail_url,
				url: 'https://www.youtube.com/watch?v=abcdefghijk',
			} );
		} );
	} );

	it( 'accepts youtu.be short URLs', async () => {
		const mockOnVideoAdded = jest.fn();
		const mockData = {
			title: 'Short URL Video',
			thumbnail_url:
				'https://img.youtube.com/vi/abcdefghijk/hqdefault.jpg',
		};
		global.fetch = jest.fn().mockResolvedValue( {
			ok: true,
			json: async () => mockData,
		} );
		render(
			<YouTubeVideoInputControl onVideoAdded={ mockOnVideoAdded } />
		);
		await fillAndSubmit( 'https://youtu.be/abcdefghijk' );
		await waitFor( () => {
			expect( mockOnVideoAdded ).toHaveBeenCalledWith( {
				title: mockData.title,
				thumbnail: mockData.thumbnail_url,
				url: 'https://youtu.be/abcdefghijk',
			} );
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
