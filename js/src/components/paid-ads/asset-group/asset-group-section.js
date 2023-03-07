/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '.~/constants';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import FinalUrlCard from './final-url-card';
import AssetGroupCard from './asset-group-card';
import './asset-group-section.scss';

/**
 * Renders the form content for managing an asset group of a campaign with Section UI.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 */
export default function AssetGroupSection() {
	const { adapter } = useAdaptiveFormContext();

	return (
		<Section
			className="gla-asset-group-section"
			title={ createInterpolateElement(
				__(
					'Add dynamic ad assets <optional>(Optional)</optional>',
					'google-listings-and-ads'
				),
				{
					optional: (
						<span className="gla-asset-group-section__optional-label" />
					),
				}
			) }
			description={
				<p className="gla-asset-group-section__primary-description">
					{ __(
						'Create ads that effectively boost visibility and generate maximum conversions. Google will mix and match assets to create optimized ads in a variety of formats— maximizing your campaign’s performance.',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<VerticalGapLayout size="medium">
				<FinalUrlCard
					initialFinalUrl={
						adapter.baseAssetGroup[ ASSET_FORM_KEY.FINAL_URL ]
					}
					onAssetsChange={ adapter.resetAssetGroup }
					// Currently, the PMax Assets feature in this extension doesn't offer the function
					// to change the Final URL of the non-empty asset entity group, so it hides the
					// reselect button in the card footer.
					hideFooter={ ! adapter.isEmptyAssetEntityGroup }
				/>
				<AssetGroupCard />
			</VerticalGapLayout>
		</Section>
	);
}
