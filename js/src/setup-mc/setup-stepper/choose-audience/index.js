/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useTargetAudience from '.~/hooks/useTargetAudience';
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import FormContent from './form-content';
import './index.scss';
import useShippingRates from '.~/hooks/useShippingRates';
import useShippingTimes from '.~/hooks/useShippingTimes';
import AppSpinner from '.~/components/app-spinner';

const ChooseAudience = ( props ) => {
	const { onContinue = () => {} } = props;
	const { data } = useTargetAudience();

	// Note: we are loading shipping rates and shipping times early here.
	// when users change target audience countries,
	// we want to read the shipping rates and times
	// and clear them.
	const { data: shippingRatesData } = useShippingRates();
	const { data: shippingTimesData } = useShippingTimes();

	if ( ! data || ! shippingRatesData || ! shippingTimesData ) {
		return <AppSpinner />;
	}

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = () => {
		onContinue();
	};

	return (
		<div className="gla-choose-audience">
			<StepContent>
				<StepContentHeader
					step={ __( 'STEP TWO', 'google-listings-and-ads' ) }
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
						onSubmitCallback={ handleSubmitCallback }
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
