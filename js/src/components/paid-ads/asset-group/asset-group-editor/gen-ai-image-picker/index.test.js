/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GenAIImagePicker from './index';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useGenAIMediaAssets from '~/hooks/useGenAIMediaAssets';
import EditImageModal from './edit-image-modal';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockName( 'useAdaptiveFormContext' ),
} ) );

jest.mock( '~/hooks/useGenAIMediaAssets', () =>
	jest.fn().mockName( 'useGenAIMediaAssets' )
);

jest.mock( './edit-image-modal', () =>
	jest
		.fn( ( props ) => (
			<div data-testid="edit-image-modal">
				<button
					onClick={ () => {
						props.onReplaceImage(
							props.sourceImageUrl,
							'https://example.com/new.png'
						);
						props.onRequestClose();
					} }
				>
					mock-generate
				</button>
			</div>
		) )
		.mockName( 'EditImageModal' )
);

describe( 'GenAIImagePicker', () => {
	const assetKey = 'marketing_image';
	const finalUrl = 'https://example.com';
	const srcA = 'https://example.com/a.png';
	const srcB = 'https://example.com/b.png';
	const getDisplayImageUrl = jest.fn( ( url ) => url );

	const newSrc = 'https://example.com/new.png';

	let onAddSelectedImages;

	beforeEach( () => {
		jest.clearAllMocks();
		onAddSelectedImages = jest.fn().mockName( 'onAddSelectedImages' );

		useAdaptiveFormContext.mockReturnValue( {
			values: { final_url: finalUrl, [ assetKey ]: [] },
		} );
		useGenAIMediaAssets.mockReturnValue( { assets: [ srcA, srcB ] } );
	} );

	const getPicker = () => (
		<GenAIImagePicker
			assetKey={ assetKey }
			getDisplayImageUrl={ getDisplayImageUrl }
			onAddSelectedImages={ onAddSelectedImages }
		/>
	);

	const renderPicker = () => render( getPicker() );

	it( 'renders nothing when there are no generated assets', () => {
		useGenAIMediaAssets.mockReturnValue( { assets: [] } );

		const { container } = renderPicker();

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders an Edit button for each image not yet added', () => {
		renderPicker();

		expect(
			screen.getAllByRole( 'button', { name: 'Edit this image' } )
		).toHaveLength( 2 );
	} );

	it( 'clicking Edit opens the modal for that image without toggling selection', async () => {
		const user = userEvent.setup();
		renderPicker();

		await user.click(
			screen.getAllByRole( 'button', { name: 'Edit this image' } )[ 0 ]
		);

		expect( EditImageModal ).toHaveBeenCalledWith(
			expect.objectContaining( {
				sourceImageUrl: srcA,
				displayImageUrl: getDisplayImageUrl( srcA ),
			} ),
			expect.anything()
		);
		screen
			.getAllByRole( 'checkbox' )
			.forEach( ( checkbox ) => expect( checkbox ).not.toBeChecked() );
	} );

	it( 'closes the modal once the image has been replaced', async () => {
		const user = userEvent.setup();
		renderPicker();

		await user.click(
			screen.getAllByRole( 'button', { name: 'Edit this image' } )[ 0 ]
		);
		await user.click( screen.getByText( 'mock-generate' ) );

		expect(
			screen.queryByTestId( 'edit-image-modal' )
		).not.toBeInTheDocument();
	} );

	it( 'keeps an edited image selected under its new URL, so the edited image is the one added', async () => {
		const user = userEvent.setup();
		const { rerender } = renderPicker();

		await user.click( screen.getAllByRole( 'checkbox' )[ 0 ] );
		await user.click(
			screen.getAllByRole( 'button', { name: 'Edit this image' } )[ 0 ]
		);
		await user.click( screen.getByText( 'mock-generate' ) );

		// The modal swaps the URL in the store, so the picker re-renders with the new image.
		useGenAIMediaAssets.mockReturnValue( { assets: [ newSrc, srcB ] } );
		rerender( getPicker() );

		expect( screen.getAllByRole( 'checkbox' )[ 0 ] ).toBeChecked();

		await user.click(
			screen.getByRole( 'button', { name: 'Add selected images' } )
		);

		expect( onAddSelectedImages ).toHaveBeenCalledWith( [ newSrc ] );
	} );
} );
