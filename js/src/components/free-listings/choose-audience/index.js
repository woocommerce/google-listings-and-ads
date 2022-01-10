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
 * @param {string} [props.initialData] Target audience data, if not given AppSinner will be rendered.
 * @param {(change: {name, value}, values: Object) => void} props.onChange Callback called with form data once form data is changed. Forwarded from {@link Form.Props.onChange}.
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked.
 */
export default function ChooseAudience( {
	initialData,
	onChange = () => {},
	onContinue = () => {},
} ) {
	if ( ! initialData ) {
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
				{ initialData && (
					<Form
						initialValues={ {
							locale: initialData.locale,
							language: initialData.language,
							location: initialData.location,
							countries: initialData.countries || [],
						} }
						validate={ handleValidate }
						onSubmit={ onContinue }
						onChange={ onChange }
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
