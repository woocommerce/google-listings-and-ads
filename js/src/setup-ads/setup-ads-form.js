/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AdsStepper from './ads-stepper';

const SetupAdsForm = () => {
	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	return (
		<Form
			initialValues={ {
				amount: '',
				country: [],
			} }
			validate={ handleValidate }
		>
			{ ( formProps ) => {
				return <AdsStepper formProps={ formProps } />;
			} }
		</Form>
	);
};

export default SetupAdsForm;
