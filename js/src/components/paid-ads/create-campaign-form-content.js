/**
 * Internal dependencies
 */
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import FaqsSection from '.~/components/paid-ads/faqs-section';

/**
 * Renders the audience and budget sections for creating a new campaign.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {JSX.Element} [props.budgetSectionChildren] The children to be rendered in the budget section.
 */
const CreateCampaignFormContent = ( { formProps, budgetSectionChildren } ) => {
	const disabledBudgetSection = ! formProps.values.countryCodes.length;

	return (
		<div className="gla-campaign-form-content">
			<AudienceSection formProps={ formProps } />
			<BudgetSection
				formProps={ formProps }
				disabled={ disabledBudgetSection }
			>
				{ budgetSectionChildren }
			</BudgetSection>
			<FaqsSection />
		</div>
	);
};

export default CreateCampaignFormContent;
