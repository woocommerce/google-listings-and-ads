/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AssetField from './asset-field';
import Section from '~/components/section';
import YoutubeVideoSelector from './youtube-video-selector';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Renders the videos section of the asset group editor, allowing users to add YouTube videos to their asset group.
 *
 * @param {Object} props - Component props.
 * @param {boolean} props.isSelectedFinalUrl - Indicates if a final URL is selected.
 *
 * @return {JSX.Element} The rendered AssetGroupVideosSection component.
 */
const AssetGroupVideosSection = ( { isSelectedFinalUrl } ) => {
	return (
		<Section
			title={ __( 'Videos', 'google-listings-and-ads' ) }
			className="gla-asset-group-section"
			description={
				<div className="gla-asset-group-section__primary-description">
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
					<YoutubeVideoSelector />
				</AssetField>
			</div>
		</Section>
	);
};

export default AssetGroupVideosSection;
