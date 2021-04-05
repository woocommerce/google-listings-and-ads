/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from './budget-section';
import FaqsSection from '../../../components/paid-ads/faqs-section';

const FormContent = ( props ) => {
	const { formProps, submitButton } = props;

	return (
		<>
			<AudienceSection
				formProps={ formProps }
				countrySelectHelperText={ __(
					'You can only select one country per campaign. ',
					'google-listings-and-ads'
				) }
			/>
			<BudgetSection formProps={ formProps } />
			<FaqsSection />
			<StepContentFooter>{ submitButton }</StepContentFooter>
		</>
	);
};

export default FormContent;
