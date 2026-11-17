/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import AppInputControl from '~/components/app-input-control';
import { CAMPAIGN_NAME_MAX_LENGTH } from '~/constants';
import './index.scss';

/**
 * Renders a card to view and edit the campaign name.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @return {JSX.Element} The rendered component.
 */
export default function CampaignNameSection() {
	const { getInputProps } = useAdaptiveFormContext();

	return (
		<Section.Card className="gla-campaign-name-section">
			<Section.Card.Body padding="medium">
				<VerticalGapLayout size="medium">
					<div>
						<Section.Card.Title>
							{ __( 'Campaign name', 'google-listings-and-ads' ) }
						</Section.Card.Title>
						<Subsection.Subtitle>
							{ __(
								"This name helps you identify this campaign. Customers won't see this.",
								'google-listings-and-ads'
							) }
						</Subsection.Subtitle>
					</div>
					<AppInputControl
						{ ...getInputProps( 'campaignName' ) }
						label={ __(
							'Campaign name',
							'google-listings-and-ads'
						) }
						maxCharacterCount={ CAMPAIGN_NAME_MAX_LENGTH }
						kindCharacterCount="google-ads"
						hideLabelFromVision
					/>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
}
