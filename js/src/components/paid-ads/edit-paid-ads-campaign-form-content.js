/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AudienceSection from '.~/components/paid-ads/audience-section';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import './campaign-form-content.scss';

/**
 * Renders the camapign edit form content.
 *
 * Please note:
 *   After introducing the multi-country targeting feature for Google Ads campaign,
 *   the campaigns created after this feature are only multi-country,
 *   and campaigns created before this are single-country targeting.
 *
 *   So the campaign to be edited can be either single or multi-country targeting.
 *   We use `prop.allowMultiple` to render different UI and wordings.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {boolean} [props.allowMultiple=true] Indicate this component should be presented in single or multi-country targeting.
 */
const EditPaidAdsCampaignFormContent = ( {
	formProps,
	allowMultiple = true,
} ) => {
	const helperText = allowMultiple
		? __(
				'Once a campaign has been created, you cannot change the target country(s).',
				'google-listings-and-ads'
		  )
		: __(
				'Once a campaign has been created, you cannot change the target country.',
				'google-listings-and-ads'
		  );

	return (
		<div className="gla-campaign-form-content">
			<AudienceSection
				disabled
				multiple={ allowMultiple }
				countrySelectHelperText={ helperText }
				formProps={ formProps }
			/>
			<BudgetSection formProps={ formProps } />
			<FaqsSection />
		</div>
	);
};

export default EditPaidAdsCampaignFormContent;
