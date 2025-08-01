/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { ASSET_FORM_KEY } from '~/constants';
import AssetField from './asset-field';
import Section from '~/components/section';
import YoutubeVideoSelector from './youtube-video-selector';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Renders the videos section of the asset group editor, allowing users to add YouTube videos to their asset group.
 *
 * @param {Object} props - Component props.
 * @param {Object} props.initialValues - The initial values for the asset group, including YouTube video IDs.
 * @param {boolean} props.isSelectedFinalUrl - Indicates if the final URL is selected.
 *
 * @return {JSX.Element} The rendered AssetGroupVideosSection component.
 */
const AssetGroupVideosSection = ( { initialValues, isSelectedFinalUrl } ) => {
	const { getInputProps } = useAdaptiveFormContext();
	const { onChange } = getInputProps( ASSET_FORM_KEY.YOUTUBE_VIDEO );

	return (
		<Section
			title={ __( 'Videos', 'google-listings-and-ads' ) }
			className="gla-asset-group-section"
			description={
				<div className="gla-asset-group-section__primary-description">
					<p>
						{ __(
							'Recommended but optional',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="asset-group"
							linkId="asset-group-videos-learn-more"
							href="https://support.google.com/google-ads/answer/14528532"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
		>
			<div className="gla-asset-group-section__content">
				<AssetField
					className="gla-asset-field-videos"
					heading={ __( 'Videos', 'google-listings-and-ads' ) }
					subheading={ __(
						'For best results, we recommend adding 1 video.',
						'google-listings-and-ads'
					) }
					disabled={ ! isSelectedFinalUrl }
					initialExpanded={ isSelectedFinalUrl }
					markOptional
				>
					<YoutubeVideoSelector
						onChange={ onChange }
						initialVideos={
							initialValues[ ASSET_FORM_KEY.YOUTUBE_VIDEO ]
						}
					/>
				</AssetField>
			</div>
		</Section>
	);
};

export default AssetGroupVideosSection;
