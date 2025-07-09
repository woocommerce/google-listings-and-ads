/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Placeholder component for the AssetGroupVideosSection.
 */
const AssetGroupVideosSection = () => {
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
				<Placeholder
					icon="video-alt3"
					label={ __( 'Videos', 'google-listings-and-ads' ) }
				>
					<p>
						{ __(
							'YouTube Videos Selector',
							'google-listings-and-ads'
						) }
					</p>
				</Placeholder>
			</div>
		</Section>
	);
};

export default AssetGroupVideosSection;
