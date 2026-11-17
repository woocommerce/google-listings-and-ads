/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import { ADVERTISING_SOURCE } from '~/constants';
import './assets-opt-in-card.scss';

/**
 * Renders the optional card for adding the merchant's own text, images and videos to a campaign that advertises
 * the store's products. It renders nothing when the campaign advertises a page, since those campaigns always have assets.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @return {JSX.Element|null} The rendered component.
 */
export default function AssetsOptInCard() {
	const { values, getInputProps } = useAdaptiveFormContext();

	if ( values.advertisingSource !== ADVERTISING_SOURCE.PRODUCT ) {
		return null;
	}

	const inputProps = getInputProps( 'includeCreativeAssets' );

	return (
		<Section.Card className="gla-assets-opt-in-card">
			<Section.Card.Body>
				<VerticalGapLayout size="large">
					<CheckboxControl
						{ ...inputProps }
						label={
							<>
								{ __(
									'Add text, images, and videos',
									'google-listings-and-ads'
								) }
								<span className="gla-assets-opt-in-card__optional">
									{ __(
										'(Optional)',
										'google-listings-and-ads'
									) }
								</span>
							</>
						}
						help={ __(
							'Add your own headlines, descriptions, images, and videos alongside your products. Google AI suggests some to start with.',
							'google-listings-and-ads'
						) }
					/>

					{ inputProps.checked && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'If you also sell services, your ads in this campaign will mainly show your products rather than your services. Google recommends promoting your services in a separate campaign.',
								'google-listings-and-ads'
							) }
						</Notice>
					) }
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
}
