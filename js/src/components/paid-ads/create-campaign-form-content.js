/**
 * Internal dependencies
 */
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import './campaign-form-content.scss';

const CreateCampaignFormContent = ( props ) => {
	const { formProps } = props;
	const disabledBudgetSection = ! formProps.values.country.length;

	return (
		<div className="gla-campaign-form-content">
			<AudienceSection formProps={ formProps } />
			<BudgetSection
				formProps={ formProps }
				disabled={ disabledBudgetSection }
			/>
			<FaqsSection />
		</div>
	);
};

export default CreateCampaignFormContent;
