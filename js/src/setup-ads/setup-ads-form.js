/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import SetupAdsFormContent from './setup-ads-form-content';

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
				return <SetupAdsFormContent formProps={ formProps } />;
			} }
		</Form>
	);
};

export default SetupAdsForm;
