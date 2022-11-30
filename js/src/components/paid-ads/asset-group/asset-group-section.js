/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import './asset-group-section.scss';

/**
 * Renders the form content for managing an asset group of a campaign with Section UI.
 */
export default function AssetGroupSection() {
	return (
		<Section
			className=".gla-asset-group-section"
			title={ createInterpolateElement(
				__(
					'Add assets <optional>(Optional)</optional>',
					'google-listings-and-ads'
				),
				{
					optional: (
						<span className="gla-asset-group-section__optional-label" />
					),
				}
			) }
			description={
				<>
					<p className="gla-asset-group-section__primary-description">
						{ __(
							'Upload additional creative assets and Google will mix and match these assets to build ads that lead to a page in your site. Adding more assets typically boosts your campaign’s performance.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="manage-campaign-asset-group"
							linkId="about-campaign-assets"
							href="https://support.google.com/google-ads/answer/7331111"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</>
			}
		>
			<Section.Card>
				<Section.Card.Body>Final URL</Section.Card.Body>
			</Section.Card>
		</Section>
	);
}
