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

const EditPaidAdsCampaignFormContent = ( props ) => {
	const { formProps } = props;

	return (
		<>
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
		</>
	);
};

export default EditPaidAdsCampaignFormContent;
