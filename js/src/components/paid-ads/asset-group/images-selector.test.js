/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ImagesSelector from './images-selector';
import useCroppedImageSelector from '.~/hooks/useCroppedImageSelector';
import AppTooltip from '.~/components/app-tooltip';

jest.mock( '.~/hooks/useCroppedImageSelector', () =>
	jest.fn().mockName( 'useCroppedImageSelector' )
);

jest.mock( '.~/components/app-tooltip', () =>
	jest.fn( ( props ) => <div { ...props } /> ).mockName( 'AppTooltip' )
);

describe( 'ImagesSelector', () => {
	const imageConfig = {
		minWidth: 150,
		minHeight: 100,
		suggestedWidth: 1500,
		suggestedHeight: 1000,
	};
	const urlA = 'https://image/A';
	const urlB = 'https://image/B';
	const urlC = 'https://image/C';
	const imageA = { id: urlA, url: urlA, alt: '' };
	const imageB = { id: urlB, url: urlB, alt: '' };
	const imageC = { id: urlC, url: urlC, alt: '' };

	const getImgUrls = () =>
		screen.getAllByRole( 'img' ).map( ( img ) => img.src );

	const getAddButton = () => screen.getByRole( 'button', { name: /add/i } );

	const getReplaceButtons = () =>
		screen.getAllByRole( 'button', { name: /replace/i } );

	const getRemoveButtons = () =>
		screen.getAllByRole( 'button', { name: /remove/i } );

	let onChange;
	let onSelect;
	let onDelete;
	let openSelector;

	beforeEach( () => {
		onChange = jest.fn().mockName( 'onChange' );
		openSelector = jest.fn().mockName( 'openSelector' );

		useCroppedImageSelector.mockImplementation( ( options ) => {
			onSelect = options.onSelect;
			onDelete = options.onDelete;
			return { openSelector };
		} );
	} );

	it( 'Should render the children', () => {
		render(
			<ImagesSelector imageConfig={ imageConfig }>
				Children
			</ImagesSelector>
		);

		expect( screen.getByText( 'Children' ) ).toBeInTheDocument();
	} );

	it( 'When clicking on add image button, the `openSelector` should be called without an image ID.', async () => {
		render( <ImagesSelector imageConfig={ imageConfig } /> );
		await userEvent.click( getAddButton() );

		expect( openSelector ).toBeCalledWith( undefined );
	} );

	it( 'When clicking on a replace image button, the `openSelector` should be called with the corresponding image ID.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA ] }
			/>
		);
		const buttons = getReplaceButtons();
		await userEvent.click( buttons.at( 0 ) );

		expect( openSelector ).toBeCalledWith( urlA );
	} );

	it( 'When clicking on a remove image button, the corresponding image should be removed.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA, urlB, urlC ] }
				onChange={ onChange }
			/>
		);
		const buttons = getRemoveButtons();
		await userEvent.click( buttons.at( 1 ) );

		expect( getImgUrls() ).toEqual( [ urlA, urlC ] );
		expect( onChange ).toBeCalledWith( [ urlA, urlC ] );
	} );

	it( 'When the number of images reaches the maximum limit, it should disable the add image button and vice versa.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				maxNumberOfImages={ 2 }
				initialImageUrls={ [ urlA ] }
			/>
		);
		const button = getAddButton();

		expect( button ).toBeEnabled();

		await act( async () => onSelect( imageB ) );

		expect( button ).toBeDisabled();

		await act( async () => onDelete( imageA ) );

		expect( button ).toBeEnabled();
	} );

	it( 'When the length of `initialImageUrls` or `images` is greater than `maxNumberOfImages`, it should truncate the excess', () => {
		const initialImageUrls = [ urlA, urlB, urlC ];
		const { rerender } = render(
			<ImagesSelector
				imageConfig={ imageConfig }
				maxNumberOfImages={ 2 }
				initialImageUrls={ initialImageUrls }
				onChange={ onChange }
			/>
		);

		expect( getImgUrls() ).toEqual( [ urlA, urlB ] );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange ).toHaveBeenLastCalledWith( [ urlA, urlB ] );

		rerender(
			<ImagesSelector
				imageConfig={ imageConfig }
				maxNumberOfImages={ 1 }
				initialImageUrls={ initialImageUrls }
				onChange={ onChange }
			/>
		);

		expect( getImgUrls() ).toEqual( [ urlA ] );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
		expect( onChange ).toHaveBeenLastCalledWith( [ urlA ] );
	} );

	it( 'When reaching the maximum number of images and the relevant tip is specified, it should use the tooltip', async () => {
		const props = { imageConfig, initialImageUrls: [ urlA ] };
		const tip = 'tip-content';
		const { rerender } = render(
			<ImagesSelector { ...props } maxNumberOfImages={ 2 } />
		);

		expect( AppTooltip ).toHaveBeenCalledTimes( 0 );

		rerender( <ImagesSelector { ...props } maxNumberOfImages={ 1 } /> );

		expect( AppTooltip ).toHaveBeenCalledTimes( 0 );

		rerender(
			<ImagesSelector
				{ ...props }
				maxNumberOfImages={ 1 }
				reachedMaxNumberTip={ tip }
			/>
		);

		expect( AppTooltip ).toHaveBeenCalledWith(
			expect.objectContaining( { text: tip } ),
			{}
		);
	} );

	it( 'When an image is called back for addition, it should be pushed to the image list.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA ] }
				onChange={ onChange }
			/>
		);
		await userEvent.click( getAddButton() );
		await act( async () => onSelect( imageB ) );

		expect( getImgUrls() ).toEqual( [ urlA, urlB ] );
		expect( onChange ).toBeCalledWith( [ urlA, urlB ] );
	} );

	it( 'When an image is called back for deletion, the image with the same ID should be removed from the image list.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA, urlB, urlC ] }
				onChange={ onChange }
			/>
		);
		await act( async () => onDelete( imageB ) );

		expect( getImgUrls() ).toEqual( [ urlA, urlC ] );
		expect( onChange ).toBeCalledWith( [ urlA, urlC ] );
	} );

	it( 'When an image called back for addition already has one with the same ID in the image list, it should not be added.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA ] }
			/>
		);
		await userEvent.click( getAddButton() );
		await act( async () => onSelect( imageA ) );

		expect( getImgUrls() ).toEqual( [ urlA ] );
	} );

	it( 'When an image is called back for replacement, it should replace the previously clicked image in the image list.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA, urlB ] }
				onChange={ onChange }
			/>
		);
		const buttons = getReplaceButtons();
		await userEvent.click( buttons.at( 0 ) );
		await act( async () => onSelect( imageC ) );

		expect( getImgUrls() ).toEqual( [ urlC, urlB ] );
		expect( onChange ).toBeCalledWith( [ urlC, urlB ] );
	} );

	it( 'When an image is called back for replacement but the previously clicked image has been removed from the image list, it should be pushed to the image list.', async () => {
		render(
			<ImagesSelector
				imageConfig={ imageConfig }
				initialImageUrls={ [ urlA, urlB ] }
				onChange={ onChange }
			/>
		);
		const buttons = getReplaceButtons();
		await userEvent.click( buttons.at( 0 ) );
		await act( async () => onDelete( imageA ) );
		await act( async () => onSelect( imageC ) );

		expect( getImgUrls() ).toEqual( [ urlB, urlC ] );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
		expect( onChange ).toHaveBeenCalledWith( [ urlB ] );
		expect( onChange ).toHaveBeenLastCalledWith( [ urlB, urlC ] );
	} );

	describe( 'When an image called back for replacement already has one with the same ID in the image list', () => {
		it( 'should swap their positions.', async () => {
			render(
				<ImagesSelector
					imageConfig={ imageConfig }
					initialImageUrls={ [ urlA, urlB, urlC ] }
					onChange={ onChange }
				/>
			);
			let buttons;

			// Swap from front to back.
			buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 0 ) );
			await act( async () => onSelect( imageC ) );

			expect( getImgUrls() ).toEqual( [ urlC, urlB, urlA ] );
			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenLastCalledWith( [ urlC, urlB, urlA ] );

			// Swap from back to front.
			buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 2 ) );
			await act( async () => onSelect( imageB ) );

			expect( getImgUrls() ).toEqual( [ urlC, urlA, urlB ] );
			expect( onChange ).toHaveBeenCalledTimes( 2 );
			expect( onChange ).toHaveBeenLastCalledWith( [ urlC, urlA, urlB ] );
		} );

		it( 'and a deletion happened on an image other than the swapping ones, it should swap their positions.', async () => {
			render(
				<ImagesSelector
					imageConfig={ imageConfig }
					initialImageUrls={ [ urlA, urlB, urlC ] }
					onChange={ onChange }
				/>
			);
			const buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 0 ) );
			await act( async () => onDelete( imageB ) );
			await act( async () => onSelect( imageC ) );

			expect( getImgUrls() ).toEqual( [ urlC, urlA ] );
			expect( onChange ).toHaveBeenCalledTimes( 2 );
			expect( onChange ).toHaveBeenCalledWith( [ urlA, urlC ] );
			expect( onChange ).toHaveBeenLastCalledWith( [ urlC, urlA ] );
		} );

		it( 'and the same ID one is also the previously clicked image, it should not be added.', async () => {
			render(
				<ImagesSelector
					imageConfig={ imageConfig }
					initialImageUrls={ [ urlA, urlB ] }
				/>
			);
			const buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 0 ) );
			await act( async () => onSelect( imageA ) );

			expect( getImgUrls() ).toEqual( [ urlA, urlB ] );
		} );

		it( 'but the previously clicked image has been removed from the image list, it should not be added.', async () => {
			render(
				<ImagesSelector
					imageConfig={ imageConfig }
					initialImageUrls={ [ urlA, urlB ] }
				/>
			);
			const buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 0 ) );
			await act( async () => onDelete( imageA ) );
			await act( async () => onSelect( imageB ) );

			expect( getImgUrls() ).toEqual( [ urlB ] );
		} );

		it( 'When an image is called back for replacement but the previously clicked image has been removed from the image list, it should be pushed to the image list.', async () => {
			render(
				<ImagesSelector
					imageConfig={ imageConfig }
					initialImageUrls={ [ urlA, urlB ] }
					onChange={ onChange }
				/>
			);
			const buttons = getReplaceButtons();
			await userEvent.click( buttons.at( 0 ) );
			await act( async () => onDelete( imageA ) );
			await act( async () => onSelect( imageC ) );

			expect( getImgUrls() ).toEqual( [ urlB, urlC ] );
			expect( onChange ).toHaveBeenCalledTimes( 2 );
			expect( onChange ).toHaveBeenCalledWith( [ urlB ] );
			expect( onChange ).toHaveBeenLastCalledWith( [ urlB, urlC ] );
		} );
	} );
} );
