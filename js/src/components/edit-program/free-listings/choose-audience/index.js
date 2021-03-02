/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useTargetAudience from '.~/hooks/useTargetAudience';
import StepContent from '.~/components/edit-program/step-content';
import StepContentHeader from '.~/components/edit-program/step-content-header';
import FormContent from './form-content';
import './index.scss';

/**
 * Form to choose audience for free listings.
 *
 * To be used in onboardign and further editing.
 * Does not provide any save strategy, this is to be bound externaly.
 *
 * @param {Object} props
 * @param {string} props.stepHeader Header text to indicate the step number.
 * @param {function(Object)} props.onContinue Callback called with form data once continue button is clicked.
 */
const ChooseAudience = ( props ) => {
	const { onContinue = () => {}, stepHeader } = props;
	const { data } = useTargetAudience();

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
				{ data && (
					<Form
						initialValues={ {
							locale: data.locale,
							language: data.language,
							location: data.location,
							countries: data.countries || [],
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
};

export default ChooseAudience;
