/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import FinalUrlCard from './final-url-card';
import ImagesSelector from './images-selector';
import TextsEditor from './texts-editor';
import AssetField from './asset-field';
import './asset-group-section.scss';

/**
 * Renders the form content for managing an asset group of a campaign with Section UI.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 */
export default function AssetGroupSection() {
	const { values, adapter } = useAdaptiveFormContext();

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
				<FinalUrlCard
					initialFinalUrl={ values.final_url }
					onAssetsChange={ adapter.resetAssetGroup }
				/>
				<div>Assets card will be added here</div>
				<h3>Temporary demo for AssetField and TextsEditor:</h3>
				<div>
					<AssetField
						heading={ __(
							'Rectangular images',
							'google-listings-and-ads'
						) }
						subheading={ __(
							'At least 1 required. Add up to 20.',
							'google-listings-and-ads'
						) }
						help={
							<>
								<p>
									{ __(
										'Add images that meet or can be cropped to the recommended sizes. Note: The maximum file size for any image is 5120 KB.',
										'google-listings-and-ads'
									) }
								</p>
								<p>
									<strong>
										{ __(
											'Landscape image (1.91:1)',
											'google-listings-and-ads'
										) }
									</strong>
								</p>
								<p>
									{ __(
										'Recommended size: 1200 x 628',
										'google-listings-and-ads'
									) }
									<br />
									{ __(
										'Min. size: 600 x 314',
										'google-listings-and-ads'
									) }
								</p>
							</>
						}
						numOfIssues={ 2 }
					>
						<ImagesSelector
							maxNumberOfImages={ 3 }
							imageConfig={ {
								minWidth: 600,
								minHeight: 314,
								suggestedWidth: 1200,
								suggestedHeight: 628,
							} }
						/>
					</AssetField>
					<AssetField
						heading={ __(
							'Square images',
							'google-listings-and-ads'
						) }
						subheading={ __(
							'At least 1 required. Add up to 20.',
							'google-listings-and-ads'
						) }
						numOfIssues={ 1 }
						disabled
					/>
					<AssetField
						heading={ __( 'Headline', 'google-listings-and-ads' ) }
						subheading={ __(
							'At least 3 required. Add up to 5.',
							'google-listings-and-ads'
						) }
						help={ __(
							'The headline is the first line of your ad and is most likely the first thing people notice, so consider including words that people may have entered in their Google search.',
							'google-listings-and-ads'
						) }
						numOfIssues={ 0 }
						initialExpanded
					>
						<TextsEditor
							minNumberOfTexts={ 3 }
							maxNumberOfTexts={ 5 }
							maxCharacterCounts={ [ 15, 30, 30, 30, 30 ] }
							placeholder={ __(
								'Headline',
								'google-listings-and-ads'
							) }
							addButtonText={ __(
								'Add headline',
								'google-listings-and-ads'
							) }
							initialTexts={ [ 'Hello', 'World' ] }
							onChange={ ( v ) =>
								// eslint-disable-next-line no-console
								console.log( 'Headline:onChange', v )
							}
						/>
					</AssetField>
					<AssetField
						heading={ __(
							'Long headlines',
							'google-listings-and-ads'
						) }
						subheading={ __(
							'At least 1 required. Add up to 5.',
							'google-listings-and-ads'
						) }
						help={
							<>
								<p>
									{ __(
										'The long headline is the first line of your ad, and appears instead of your short headline in larger ads. Long headlines can be up to 90 characters, and may appear with or without your description.',
										'google-listings-and-ads'
									) }
								</p>
								<p>
									{ __(
										'The length of the rendered headline will depend on the site it appears on. If shortened, it will end with an ellipsis(…).',
										'google-listings-and-ads'
									) }
								</p>
							</>
						}
						numOfIssues={ 0 }
					>
						<TextsEditor
							minNumberOfTexts={ 1 }
							maxNumberOfTexts={ 5 }
							maxCharacterCounts={ 90 }
							placeholder={ __(
								'Long headlines',
								'google-listings-and-ads'
							) }
							addButtonText={ __(
								'Add long headline',
								'google-listings-and-ads'
							) }
							initialTexts={ [] }
							onChange={ ( v ) =>
								// eslint-disable-next-line no-console
								console.log( 'Long headlines:onChange', v )
							}
						/>
					</AssetField>
				</div>
			</VerticalGapLayout>
			<h3>Temporary demo for showing the current assets data:</h3>
			<pre>{ JSON.stringify( assetGroup, null, 2 ) }</pre>
		</Section>
	);
}
