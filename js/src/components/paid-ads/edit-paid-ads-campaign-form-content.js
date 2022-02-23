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

const EditPaidAdsCampaignFormContent = ( props ) => {
	const { formProps } = props;

	return (
		<div className="gla-campaign-form-content">
			<AudienceSection
				disabled
				countrySelectHelperText={ __(
					'Once a campaign has been created, you cannot change the target country.',
					'google-listings-and-ads'
				) }
				formProps={ formProps }
			/>
			<BudgetSection formProps={ formProps } />
			<FaqsSection />
		</div>
	);
};

export default EditPaidAdsCampaignFormContent;
