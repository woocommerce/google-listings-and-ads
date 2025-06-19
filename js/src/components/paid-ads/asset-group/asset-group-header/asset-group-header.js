/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Tip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import FinalUrlCard from './final-url-card';
import AppDocumentationLink from '~/components/app-documentation-link';

/**
 * Renders the header section for the asset group form where the user selects the URL to manage the assets for.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 */
export default function AssetGroupHeader() {
	const { adapter } = useAdaptiveFormContext();
	const showTip = adapter.hasImportedAssets;

	return (
		<Section
			className="gla-asset-group-section"
			title={ createInterpolateElement(
				__(
					'Add additional assets <optional>(Optional)</optional>',
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
							'Upload text and image assets to effectively reach and engage your target shoppers. Google will mix and match your assets, continually testing combinations to create personalized and optimal shopping experiences.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						<AppDocumentationLink
							context="asset-group"
							linkId="asset-group-learn-more"
							href="https://support.google.com/google-ads/answer/10729160"
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppDocumentationLink>
					</p>
				</>
			}
		>
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
			{ showTip && (
				<Tip>
					{ __(
						'We auto-populated assets directly from your Final URL. We encourage you to edit or add more in order to best showcase your business.',
						'google-listings-and-ads'
					) }
				</Tip>
			) }
		</Section>
	);
}
