/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import FinalUrlCard from './final-url-card';
import './asset-group-section.scss';

/**
 * Renders the form content for managing an asset group of a campaign with Section UI.
 */
export default function AssetGroupSection() {
	const [ assetGroup, setAssetGroup ] = useState( null );

	return (
		<Section
			className="gla-asset-group-section"
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
			<VerticalGapLayout size="medium">
				<FinalUrlCard onAssetsChange={ setAssetGroup } />
				<div>Assets card will be added here</div>
			</VerticalGapLayout>
			<h3>Temporary demo for showing the current assets data:</h3>
			<pre>{ JSON.stringify( assetGroup, null, 2 ) }</pre>
		</Section>
	);
}
