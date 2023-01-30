/**
 * Internal dependencies
 */
import AdaptiveForm from '.~/components/adaptive-form';
import validateCampaign from '.~/components/paid-ads/validateCampaign';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * Renders a form based on AdaptiveForm for managing campaign and assets.
 *
 * @augments AdaptiveForm
 * @param {Object} props React props.
 * @param {CampaignFormValues} props.initialCampaign Initial campaign values.
 */
export default function CampaignAssetsForm( {
	initialCampaign,
	...adaptiveFormProps
} ) {
	return (
		<AdaptiveForm
			initialValues={ initialCampaign }
			validate={ validateCampaign }
			{ ...adaptiveFormProps }
		/>
	);
}
