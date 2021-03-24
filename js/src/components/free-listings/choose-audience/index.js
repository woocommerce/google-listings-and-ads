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
import '.~/components/free-listings/choose-audience/index.scss';

/**
 * Step with a form to choose audience.
 *
 * To be used in onboarding and further editing.
 * Does not provide any save strategy, this is to be bound externaly.
 * Copied from {@link .~/setup-mc/setup-stepper/choose-audience/index.js}.
 *
 * @param {Object} props
 * @param {string} props.stepHeader Header text to indicate the step number.
 * @param {string} [props.initialData] Target audience data, if not given AppSinner will be rendered.
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked.
 */
export default function ChooseAudience( {
	stepHeader,
	initialData,
	onContinue = () => {},
} ) {
	if ( ! initialData ) {
		return <AppSpinner />;
	}

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	return (
		<div className="gla-choose-audience">
			<StepContent>
				<StepContentHeader
					step={ stepHeader }
					title={ __(
						'Choose your audience',
						'google-listings-and-ads'
					) }
					description={ __(
						'Configure who sees your product listings on Google.',
						'google-listings-and-ads'
					) }
				/>
				{ initialData && (
					<Form
						initialValues={ {
							locale: initialData.locale,
							language: initialData.language,
							location: initialData.location,
							countries: initialData.countries || [],
						} }
						validate={ handleValidate }
						onSubmitCallback={ onContinue }
					>
						{ ( formProps ) => {
							return <FormContent formProps={ formProps } />;
						} }
					</Form>
				) }
			</StepContent>
		</div>
	);
}
