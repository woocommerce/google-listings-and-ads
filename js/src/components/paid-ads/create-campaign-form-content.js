/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import './campaign-form-content.scss';

const CreateCampaignFormContent = ( props ) => {
	const { formProps } = props;

	return (
		<div className="gla-campaign-form-content">
			<AudienceSection
				formProps={ formProps }
				countrySelectHelperText={ __(
					'You can only select one country per campaign. ',
					'google-listings-and-ads'
				) }
			/>
			<BudgetSection formProps={ formProps } />
			<FaqsSection />
		</div>
	);
};

export default CreateCampaignFormContent;
