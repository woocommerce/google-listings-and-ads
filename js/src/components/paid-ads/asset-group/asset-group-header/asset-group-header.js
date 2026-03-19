/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useRef,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { Tip, Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import FinalUrlCard from './final-url-card';
import AppDocumentationLink from '~/components/app-documentation-link';
import GenAICard from '../../gen-ai-card';
import GenAIProgress from '../../gen-ai-progress';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

/**
 * Renders the header section for the asset group form where the user selects the URL to manage the assets for.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 */
export default function AssetGroupHeader() {
	const hasLoadedInitialHomepageAssetsRef = useRef( false );
	const { adapter } = useAdaptiveFormContext();
	const {
		hasImportedAssets,
		hasAISuggestedTextAssets,
		hasAISuggestedMediaAssets,
		fetchAssets,
		isFetchingAssets,
		isEditing,
	} = adapter;
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	const fetchCampaignAssets = useCallback(
		async ( id, type ) => {
			const suggestedAssets = await fetchAssets( id, type );
			adapter.resetAssetGroup( suggestedAssets );
		},
		[ fetchAssets, adapter ]
	);

	useEffect( () => {
		async function loadAssets() {
			if (
				hasLoadedInitialHomepageAssetsRef.current ||
				adapter.baseAssetGroup[ ASSET_FORM_KEY.FINAL_URL ] ||
				isEditing
			) {
				return;
			}

			hasLoadedInitialHomepageAssetsRef.current = true;

			// Load homepage assets on first render by passing `id: 0` and a `type` other than `post` or `term`.
			// `id` is a required parameter, but it is ignored when loading homepage assets.
			// Related: https://github.com/woocommerce/google-listings-and-ads/blob/d23bdb504bce1ed8a10a4bd92608aeb5137fbe60/src/Ads/AssetSuggestionsService.php#L210-L216
			await fetchCampaignAssets( 0, 'homepage' );
		}

		loadAssets();
	}, [ fetchCampaignAssets, adapter.baseAssetGroup, isEditing ] );

	if ( isFetchingAssets ) {
		return <GenAIProgress />;
	}

	const title = hasGoogleMCConnection
		? createInterpolateElement(
				__(
					'Add additional assets <optional>(Optional)</optional>',
					'google-listings-and-ads'
				),
				{
					optional: (
						<span className="gla-asset-group-section__optional-label" />
					),
				}
		  )
		: __( 'Add assets', 'google-listings-and-ads' );

	return (
		<Section
			className="gla-asset-group-section"
			title={ title }
			description={
				<>
					<p className="gla-asset-group-section__primary-description">
						{ __(
							'Upload text and image assets to effectively reach and engage your target customers. Google will mix and match your assets, continually testing combinations to create a personalized and optimal experience.',
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
			<div className="gla-asset-group-section__content">
				<Flex direction="column" gap={ 4 }>
					<FlexItem>
						<Flex direction="column" gap={ 4 }>
							<FlexItem>
								<FinalUrlCard
									initialFinalUrl={
										adapter.baseAssetGroup[
											ASSET_FORM_KEY.FINAL_URL
										]
									}
									onAssetsChange={ adapter.resetAssetGroup }
									// Currently, the PMax Assets feature in this extension doesn't offer the function
									// to change the Final URL of the non-empty asset entity group, so it hides the
									// reselect button in the card footer.
									hideFooter={
										! adapter.isEmptyAssetEntityGroup
									}
								/>
							</FlexItem>
							{ hasImportedAssets && (
								<FlexItem>
									<Tip>
										{ __(
											"We've used your final URL to auto-populate some assets for you. For the best results, we recommend that you add more assets.",
											'google-listings-and-ads'
										) }
									</Tip>
								</FlexItem>
							) }
						</Flex>
					</FlexItem>

					{ hasAISuggestedTextAssets && hasAISuggestedMediaAssets && (
						<FlexItem>
							<GenAICard />
						</FlexItem>
					) }
				</Flex>
			</div>
		</Section>
	);
}
