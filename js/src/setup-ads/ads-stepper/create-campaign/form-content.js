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

const FormContent = ( props ) => {
	const { formProps } = props;

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
		</>
	);
};

export default FormContent;
