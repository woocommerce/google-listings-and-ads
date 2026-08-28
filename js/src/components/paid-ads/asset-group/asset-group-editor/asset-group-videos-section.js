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
							href="https://support.google.com/google-ads/answer/14528532"
							linkId="asset-group-videos-learn-more"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</div>
			}
			title={ __( 'Videos', 'google-listings-and-ads' ) }
		>
			<div className="gla-asset-group-section__content">
				<AssetField
					className="gla-asset-field-videos"
					disabled={ ! isSelectedFinalUrl }
					heading={ __( 'Videos', 'google-listings-and-ads' ) }
					help={
						<>
							<p>
								{ __(
									'You can add up to a maximum of 5 YouTube video assets.',
									'google-listings-and-ads'
								) }
							</p>
							<div className="gla-asset-field__help-popover__content-group">
								<p>
									{ __(
										'Only valid YouTube URLs are accepted, such as:',
										'google-listings-and-ads'
									) }
								</p>
								<ul>
									<li>
										https://www.youtube.com/watch?v=VIDEO_ID
									</li>
									<li>https://youtu.be/VIDEO_ID</li>
								</ul>
							</div>
							<p>
								{ __(
									'Clicking a video thumbnail will open a preview in a new browser tab.',
									'google-listings-and-ads'
								) }
							</p>
							<p>
								{ __(
									'To remove a video, hover over the thumbnail and click the "X" icon.',
									'google-listings-and-ads'
								) }
							</p>
						</>
					}
					initialExpanded={ isSelectedFinalUrl }
					subheading={ __(
						'For best results, we recommend adding 1 video.',
						'google-listings-and-ads'
					) }
					markOptional
				>
					<YoutubeVideoSelector
						initialVideos={
							initialValues[ ASSET_FORM_KEY.YOUTUBE_VIDEO ]
						}
						onChange={ onChange }
					/>
				</AssetField>
			</div>
		</Section>
	);
};

export default AssetGroupVideosSection;
