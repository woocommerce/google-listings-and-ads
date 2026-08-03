/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, Tip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ImagesSelector from './images-selector';
import AssetField from './asset-field';
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';
import useAdBlockImage from '~/hooks/useAdBlockImage';
import { ASSET_IMAGE_SPECS } from '../../assetSpecs';

/**
 * Renders the images section for an asset group, displaying requirements and allowing users to select and manage images according to
 * specified asset image specs.
 *
 * @param {Object} props - Component props.
 * @param {Object} props.initialValues - Initial values for the image fields, keyed by asset spec key.
 * @param {Function} props.refFirstErrorField - Function to bind refs for the first error field.
 * @param {boolean} props.isSelectedFinalUrl - Indicates if the final URL is selected, enabling/disabling fields.
 * @param {Function} props.getNumOfIssues - Function to get the number of issues for a given asset key.
 * @param {Function} props.renderErrors - Function to render error messages for a given asset key.
 *
 * @return {JSX.Element} The rendered AssetGroupImagesSection component.
 */
const AssetGroupImagesSection = ( {
	initialValues,
	refFirstErrorField,
	isSelectedFinalUrl,
	getNumOfIssues,
	renderErrors,
} ) => {
	const { values, getInputProps, adapter } = useAdaptiveFormContext();
	const showTip = adapter.hasAISuggestedMediaAssets;
	const { getDisplayImageUrl } = useAdBlockImage();

	return (
		<Section
			title={ __( 'Images', 'google-listings-and-ads' ) }
			className="gla-asset-group-section"
			description={
				<div className="gla-asset-group-section__primary-description">
					<p>
						{ __(
							'The minimum requirements:',
							'google-listings-and-ads'
						) }
					</p>
					<ul>
						<li>
							{ __(
								'1x marketing image',
								'google-listings-and-ads'
							) }
						</li>
						<li>
							{ __(
								'1x square marketing image',
								'google-listings-and-ads'
							) }
						</li>
						<li>{ __( '1x logo', 'google-listings-and-ads' ) }</li>
					</ul>
					<p>
						<AppDocumentationLink
							context="asset-group"
							linkId="asset-group-images-learn-more"
							href="https://support.google.com/google-ads/answer/14530211"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			<div className="gla-asset-group-section__content">
				<Flex direction="column" gap={ 4 }>
					{ showTip && (
						<FlexItem>
							<Tip>
								{ __(
									"We've used your final URL to auto-populate images…",
									'google-listings-and-ads'
								) }
							</Tip>
						</FlexItem>
					) }

					<FlexItem>
						{ ASSET_IMAGE_SPECS.map( ( spec ) => {
							const initialImageUrls = initialValues[ spec.key ];
							const imageProps = getInputProps( spec.key );

							return (
								<AssetField
									key={ spec.key }
									ref={ refFirstErrorField.bind( spec.key ) }
									heading={ spec.heading }
									subheading={ spec.subheading }
									help={ spec.help }
									numOfIssues={ getNumOfIssues( spec.key ) }
									markOptional={ spec.min === 0 }
									disabled={ ! isSelectedFinalUrl }
									initialExpanded={ isSelectedFinalUrl }
								>
									<ImagesSelector
										assetKey={ spec.key }
										initialImageUrls={ initialImageUrls }
										maxNumberOfImages={ spec.getMax(
											values
										) }
										reachedMaxNumberTip={ spec.getMaxNumberTip(
											values
										) }
										imageConfig={ spec.imageConfig }
										onChange={ imageProps.onChange }
										getDisplayImageUrl={
											getDisplayImageUrl
										}
										generateButtonText={
											spec.generateButtonText
										}
									>
										{ renderErrors( spec.key ) }
									</ImagesSelector>
								</AssetField>
							);
						} ) }
					</FlexItem>
				</Flex>
			</div>
		</Section>
	);
};

export default AssetGroupImagesSection;
