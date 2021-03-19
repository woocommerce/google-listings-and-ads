/**
 * Internal dependencies
 */
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AudienceSection from './audience-section';
import BudgetSection from './budget-section';
import FaqsSection from './faqs-section';

const FormContent = ( props ) => {
	const { formProps, submitButton } = props;

	return (
		<>
			<AudienceSection formProps={ formProps } />
			<BudgetSection formProps={ formProps } />
			<FaqsSection />
			<StepContentFooter>{ submitButton }</StepContentFooter>
		</>
	);
};

export default FormContent;
