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
					onClick={ () =>
						props.onReplaceImage(
							props.sourceImageUrl,
							'https://example.com/new.png'
						)
					}
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

	let onAddSelectedImages;
	let onReplaceImage;

	beforeEach( () => {
		jest.clearAllMocks();
		onAddSelectedImages = jest.fn().mockName( 'onAddSelectedImages' );
		onReplaceImage = jest.fn().mockName( 'onReplaceImage' );

		useAdaptiveFormContext.mockReturnValue( {
			values: { final_url: finalUrl, [ assetKey ]: [] },
		} );
		useGenAIMediaAssets.mockReturnValue( { assets: [ srcA, srcB ] } );
	} );

	const renderPicker = () =>
		render(
			<GenAIImagePicker
				assetKey={ assetKey }
				getDisplayImageUrl={ getDisplayImageUrl }
				onAddSelectedImages={ onAddSelectedImages }
				onReplaceImage={ onReplaceImage }
			/>
		);

	it( 'renders nothing when there are no generated assets', () => {
		useGenAIMediaAssets.mockReturnValue( { assets: [] } );

		const { container } = renderPicker();

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders a keyboard-focusable Edit control per image', () => {
		renderPicker();

		const editButtons = screen.getAllByRole( 'button', {
			name: 'Edit this image',
		} );

		expect( editButtons ).toHaveLength( 2 );
		editButtons.forEach( ( button ) =>
			expect( button.tagName ).toBe( 'BUTTON' )
		);
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

	it( 'replacing via the modal forwards to onReplaceImage and closes the modal', async () => {
		const user = userEvent.setup();
		renderPicker();

		await user.click(
			screen.getAllByRole( 'button', { name: 'Edit this image' } )[ 0 ]
		);
		await user.click( screen.getByText( 'mock-generate' ) );

		expect( onReplaceImage ).toHaveBeenCalledWith(
			srcA,
			'https://example.com/new.png'
		);
		expect(
			screen.queryByTestId( 'edit-image-modal' )
		).not.toBeInTheDocument();
	} );
} );
