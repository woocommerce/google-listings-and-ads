/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppSpinner from '.~/components/app-spinner';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import FormContent from './form-content';
import useTargetAudienceWithSuggestions from './useTargetAudienceWithSuggestions';

/**
 * Step with a form to choose audience.
 * Auto-saves.
 *
 * @see .~/components/free-listings/choose-audience
 * @param {Object} props
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked.
 */
const ChooseAudience = ( props ) => {
	const { onContinue = () => {} } = props;
	const { loading, data } = useTargetAudienceWithSuggestions();

	if ( loading ) {
		return <AppSpinner />;
	}

	const handleValidate = ( values ) => {
		const errors = {};

		if ( ! values.location ) {
			errors.location = __(
				'Please select a location option.',
				'google-listings-and-ads'
			);
		}

		if ( values.location === 'selected' && values.countries.length === 0 ) {
			errors.countries = __(
				'Please select at least one country.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmitCallback = () => {
		onContinue();
	};

	return (
		<div className="gla-choose-audience">
			<StepContent>
				<StepContentHeader
					title={ __(
						'Choose your audience',
						'google-listings-and-ads'
					) }
					description={ __(
						'Configure who sees your product listings on Google.',
						'google-listings-and-ads'
					) }
				/>
				<Form
					initialValues={ {
						locale: data.locale,
						language: data.language,
						location: data.location,
						countries: data.countries || [],
					} }
					validate={ handleValidate }
					onSubmit={ handleSubmitCallback }
				>
					{ ( formProps ) => {
						return <FormContent formProps={ formProps } />;
					} }
				</Form>
			</StepContent>
		</div>
	);
};

export default ChooseAudience;
