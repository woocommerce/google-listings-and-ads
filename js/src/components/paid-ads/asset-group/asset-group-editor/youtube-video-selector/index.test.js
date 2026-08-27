/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AppTooltip from '~/components/app-tooltip';
import YoutubeVideoSelector from './index';

jest.mock( './youtube-video-input-control', () => ( props ) => (
	<button
		onClick={ () => props.onVideoAdded && props.onVideoAdded( 'video1' ) }
	>
		Add Video
	</button>
) );

jest.mock( '~/components/app-tooltip', () =>
	jest.fn( ( props ) => <div { ...props } /> ).mockName( 'AppTooltip' )
);

const initialVideos = [ 'video1', 'video2' ];

describe( 'YoutubeVideoSelector', () => {
	it( 'renders with initial videos', () => {
		render(
			<YoutubeVideoSelector
				initialVideos={ initialVideos }
				onChange={ jest.fn() }
			/>
		);

		const images = screen.getAllByRole( 'img' );
		expect( images[ 0 ] ).toHaveAttribute(
			'src',
			'https://img.youtube.com/vi/video1/mqdefault.jpg'
		);
		expect( images[ 2 ] ).toHaveAttribute(
			'src',
			'https://img.youtube.com/vi/video2/mqdefault.jpg'
		);
	} );

	it( 'calls onChange when a video is added', () => {
		const onChange = jest.fn();
		const { getByRole } = render(
			<YoutubeVideoSelector initialVideos={ [] } onChange={ onChange } />
		);

		fireEvent.click( getByRole( 'button', { name: 'Add Video' } ) );

		expect( onChange ).toHaveBeenCalledWith( [ 'video1' ] );
	} );

	it( 'does not add duplicate videos', () => {
		const onChange = jest.fn();
		const { getByRole } = render(
			<YoutubeVideoSelector
				initialVideos={ [ 'video1' ] }
				onChange={ onChange }
			/>
		);
		fireEvent.click( getByRole( 'button', { name: 'Add Video' } ) );

		expect( onChange ).toHaveBeenCalledTimes( 0 );
	} );

	it( 'removes a video and calls onChange', () => {
		const onChange = jest.fn();
		const { getAllByRole } = render(
			<YoutubeVideoSelector
				initialVideos={ initialVideos }
				onChange={ onChange }
			/>
		);
		fireEvent.click(
			getAllByRole( 'button', { name: 'Remove media' } )[ 0 ]
		);
		expect( onChange ).toHaveBeenCalledWith( [ initialVideos[ 1 ] ] );
	} );

	it( 'disables add button when maxNumberOfVideos is reached', () => {
		const { getByRole } = render(
			<YoutubeVideoSelector
				initialVideos={ initialVideos }
				maxNumberOfVideos={ 2 }
				onChange={ jest.fn() }
				reachedMaxNumberTip="Max videos reached"
			/>
		);

		// Add button should not be rendered (input control is shown instead)
		fireEvent.click( getByRole( 'button', { name: 'Add Video' } ) );
		const addButton = getByRole( 'button', { name: 'Add YouTube video' } );
		expect( addButton ).toBeDisabled();
	} );

	it( 'shows tooltip when max videos reached and reachedMaxNumberTip is set', () => {
		render(
			<YoutubeVideoSelector
				initialVideos={ initialVideos }
				maxNumberOfVideos={ 2 }
				onChange={ jest.fn() }
				reachedMaxNumberTip="Max videos reached"
			/>
		);
		fireEvent.click( screen.getByText( 'Add Video' ) );

		expect( AppTooltip ).toHaveBeenCalledWith(
			expect.objectContaining( { text: 'Max videos reached' } ),
			{}
		);
	} );

	it( 'opens video url in new tab when media is clicked', () => {
		window.open = jest.fn();
		const { getAllByRole } = render(
			<YoutubeVideoSelector
				initialVideos={ initialVideos }
				onChange={ jest.fn() }
			/>
		);
		fireEvent.click(
			getAllByRole( 'button', { name: 'View video' } )[ 0 ]
		);
		expect( window.open ).toHaveBeenCalledWith(
			'https://youtube.com/v/video1',
			'_blank',
			'noopener,noreferrer'
		);
	} );
} );
