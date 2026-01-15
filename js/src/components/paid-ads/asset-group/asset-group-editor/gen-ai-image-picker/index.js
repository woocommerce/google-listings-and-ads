/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Flex,
	FlexItem,
	CheckboxControl,
	FlexBlock,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AIIcon from '~/images/ai-icon.svg?inline';
import './index.scss';

export default function GenAIImagePicker( {
	assetKey,
	finalUrl,
	images,
	onAddSelectedImages = noop,
} ) {
	const [ selectedImages, setSelectedImages ] = useState( [] );
	const handleOnAddSelectedImages = () => {
		onAddSelectedImages( selectedImages );
		setSelectedImages( [] );
	};

	const toggleImageSelection = ( src ) => {
		setSelectedImages( ( previousImages ) =>
			previousImages.includes( src )
				? previousImages.filter( ( image ) => image !== src )
				: [ ...previousImages, src ]
		);
	};

	return (
		<Flex className="gla-gen-ai-image-picker" direction="column" gap={ 4 }>
			<FlexBlock>
				<h3 className="gla-gen-ai-image-picker__title">
					<AIIcon width={ 24 } height={ 24 } />

					{ __( 'AI-generated images', 'google-listings-and-ads' ) }
				</h3>
				<p className="gla-gen-ai-image-picker__description">
					{ __(
						'Select to add these images to this set for your product.',
						'google-listings-and-ads'
					) }
				</p>
			</FlexBlock>

			<FlexBlock>
				<Flex gap={ 4 } justify="start" wrap>
					{ images.map( ( src ) => (
						<FlexItem
							key={ src }
							className="gla-gen-ai-image-picker__image"
						>
							<AppButton
								className="gla-gen-ai-image-picker__medium-button"
								aria-label={ __(
									'Select image',
									'google-listings-and-ads'
								) }
								onClick={ () => toggleImageSelection( src ) }
							>
								<img
									className="gla-media-selector__medium"
									src={ src }
									alt=""
								/>
							</AppButton>

							<CheckboxControl
								className="gla-gen-ai-image-picker__checkbox"
								checked={ selectedImages.includes( src ) }
								onChange={ () => toggleImageSelection( src ) }
								value={ src }
							/>
						</FlexItem>
					) ) }
				</Flex>
			</FlexBlock>

			<FlexBlock>
				<AppButton
					variant="secondary"
					text={ __(
						'Add selected images',
						'google-listings-and-ads'
					) }
					onClick={ handleOnAddSelectedImages }
					disabled={ selectedImages.length === 0 }
				/>
			</FlexBlock>
		</Flex>
	);
}
