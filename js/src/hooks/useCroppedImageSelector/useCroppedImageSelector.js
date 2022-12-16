/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './useCroppedImageSelector.scss';

/**
 * @typedef {import('.~/hooks/types.js').ImageMedia} ImageMedia
 */

/**
 * @typedef {Object} CroppedImageSelector
 * @property {(preselectedId: number) => void} openSelector Function to open the selector modal.
 */

/**
 * Calculates the maximum cropping size according to the fixed aspect ratio.
 *
 * @param {number} width The image width.
 * @param {number} height The image height.
 * @param {number} widthScale The scale of width.
 * @param {number} heightScale The scale of height.
 * @return {[number, number]} The tuple of cropped width and height.
 */
export function cropByFixedRatio( width, height, widthScale, heightScale ) {
	const ratio = widthScale / heightScale;

	if ( width / height > ratio ) {
		width = Math.round( height * ratio );
	} else {
		height = Math.round( width / ratio );
	}

	return [ width, height ];
}

/**
 * Returns the options to be used with the jQuery plugin `imgAreaSelect` to specify the
 * initial selection.
 *
 * @param {number} width The image width.
 * @param {number} height The image height.
 * @param {number} minWidth The minimum width and also the width to be calculated as the aspect ratio.
 * @param {number} minHeight The minimum height and also the height to be calculated as the aspect ratio.
 * @return {Object} The options to be used with the jQuery plugin `imgAreaSelect`.
 */
export function getSelectionOptions( width, height, minWidth, minHeight ) {
	const [ croppedWidth, croppedHeight ] = cropByFixedRatio( ...arguments );
	const x1 = ( width - croppedWidth ) / 2;
	const y1 = ( height - croppedHeight ) / 2;

	return {
		// Shows the resize handles.
		handles: true,
		// Returns the `imgAreaSelect` instance instead of jQuery object.
		// Ref:
		// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/views/cropper.js#L83
		// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/views/cropper.js#L60
		instance: true,
		// Don't start a new selection when clicking outside the selection area.
		persistent: true,
		imageWidth: width,
		imageHeight: height,
		minWidth,
		minHeight,
		x1,
		y1,
		x2: x1 + croppedWidth,
		y2: y1 + croppedHeight,
		aspectRatio: `${ minWidth }:${ minHeight }`,
	};
}

/**
 * Calculates the percent error between the actual aspect ratio and the expected aspect ratio.
 *
 * @param {number} width The actual width.
 * @param {number} height The actual height.
 * @param {number} expectedWidth The expected width.
 * @param {number} expectedHeight The expected height.
 * @return {number} The percent error of aspect ratio.
 */
export function calcRatioPercentError(
	width,
	height,
	expectedWidth,
	expectedHeight
) {
	const imageRatio = width / height;
	const expectedRatio = expectedWidth / expectedHeight;
	return Math.abs( ( imageRatio - expectedRatio ) / expectedRatio ) * 100;
}

/**
 * Hook for opening a modal to select images with fixed aspect ratio via
 * WordPress Media library. It will request cropping when the size of the
 * selected image doesn't match the given aspect ratio.
 *
 * @param {Object} options
 * @param {number} options.minWidth The minimum width and also the width to be calculated as the aspect ratio.
 * @param {number} options.minHeight The minimum height and also the height to be calculated as the aspect ratio.
 * @param {number} options.suggestedWidth The suggested width showing on the modal.
 * @param {number} options.suggestedHeight The suggested height showing on the modal.
 * @param {(image: ImageMedia) => void} options.onSelect Callback when an image is selected.
 * @param {(image: ImageMedia) => void} options.onDelete Callback when an image is deleted.
 * @param {number} [options.allowedRatioPercentError=1] The percent error of the aspect ratio.
 * @return {CroppedImageSelector} The handle of this hook.
 */
export default function useCroppedImageSelector( {
	minWidth,
	minHeight,
	suggestedWidth,
	suggestedHeight,
	onSelect,
	onDelete,
	allowedRatioPercentError = 1,
} ) {
	const callbackRef = useRef( {} );
	callbackRef.current.onSelect = onSelect;
	callbackRef.current.onDelete = onDelete;

	const openSelector = useCallback(
		( preselectedId ) => {
			const { media } = wp;
			const sizeErrorMessage = sprintf(
				// translators: 1: Minimum width, 2: Minimum height.
				__(
					'Image size needs to be at least %1$d x %2$d',
					'google-listings-and-ads'
				),
				minWidth,
				minHeight
			);

			// Will be called by the controller of the parent class of CustomizeImageCropper. Ref:
			// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/controllers/customize-image-cropper.js#L14
			// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/controllers/cropper.js#L65
			// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/views/cropper.js#L48-L54
			const imgSelectOptions = ( attachment, controller ) => {
				const width = attachment.get( 'width' );
				const height = attachment.get( 'height' );
				const args = [ width, height, minWidth, minHeight ];
				const ratioPercentError = calcRatioPercentError( ...args );

				if ( ratioPercentError < allowedRatioPercentError ) {
					controller.set( 'canSkipCrop', true );
				}
				return getSelectionOptions( ...args );
			};

			// Ref:
			// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/_enqueues/wp/media/models.js#L36
			// - https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/views/frame/select.js
			const frame = media( {
				button: {
					text: __( 'Select', 'google-listings-and-ads' ),
					close: false,
				},
				states: [
					// Ref: https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/controllers/library.js
					new media.controller.Library( {
						title: __(
							'Select or upload image',
							'google-listings-and-ads'
						),
						library: media.query( { type: 'image' } ),
						date: false,
						suggestedWidth,
						suggestedHeight,
					} ),
					new media.controller.CustomizeImageCropper( {
						imgSelectOptions,
						// Ignores the adjustment of the flexible sides.
						// Ref: https://github.com/WordPress/wordpress-develop/blob/5.9.0/src/js/media/controllers/customize-image-cropper.js#L39-L40
						control: { params: {} },
					} ),
				],
			} );

			function handlePreselect( library ) {
				if ( ! preselectedId ) {
					return;
				}
				const attachment = library.get( preselectedId );
				if ( attachment ) {
					frame.state().get( 'selection' ).reset( [ attachment ] );
				}
			}

			function handleImageDeleted( attachment ) {
				callbackRef.current.onDelete( attachment );
			}

			function handleSelectionToggle() {
				// Workaround to update the disabled state of button after uploading a new image
				// since `toolbar` will be triggered the refresh event at the end, so this function
				// must be called after that.
				if ( this === frame ) {
					setImmediate( handleSelectionToggle );
					return;
				}

				const selection = frame.state().get( 'selection' );
				const toolbar = frame.toolbar.get();

				let invalidSize;

				if ( selection.length ) {
					const { width, height } = selection.first().toJSON();
					invalidSize = width < minWidth || height < minHeight;
				}

				const primaryBlock = toolbar.primary.el;

				if ( invalidSize ) {
					primaryBlock.dataset.errorMessage = sizeErrorMessage;
					primaryBlock.classList.add( 'gla-decorated-error-message' );
					toolbar.get( 'select' ).model.set( 'disabled', true );
				} else {
					delete primaryBlock.dataset.errorMessage;
				}
			}

			function handleSelectButtonClick() {
				frame.setState( 'cropper' );
			}

			function handleCrop( image ) {
				// 'skippedcrop' will pass an attachment model.
				if ( image instanceof media.model.Attachment ) {
					image = image.toJSON();
				}
				callbackRef.current.onSelect( image );
			}

			function handleClose() {
				// Remove all callbacks bound with the same context.
				// Ref: https://backbonejs.org/#Events-off
				const libraryModel = frame.state( 'library' );
				libraryModel.get( 'library' ).off( null, null, frame );
				libraryModel.get( 'selection' ).off( null, null, frame );
				frame.off( null, null, frame );
				frame.remove();
			}

			frame
				.on( 'select', handleSelectButtonClick, frame )
				.on( 'cropped skippedcrop', handleCrop, frame )
				.on( 'close', handleClose, frame );

			frame
				.state( 'library' )
				.get( 'selection' )
				.on(
					'selection:single selection:unsingle',
					handleSelectionToggle,
					frame
				);

			frame
				.state( 'library' )
				.get( 'library' )
				.once( 'attachments:received', handlePreselect, frame )
				.on( 'reset', handleSelectionToggle, frame ) // Triggered after uploading a new image
				.on( 'destroy', handleImageDeleted, frame ); // Triggered after deleting an image

			frame.open();
		},
		[
			minWidth,
			minHeight,
			suggestedWidth,
			suggestedHeight,
			allowedRatioPercentError,
		]
	);

	return { openSelector };
}
