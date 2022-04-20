/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * @typedef { import("./typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Rate form.
 *
 * This is used by AddRateFormModal and EditRateFormModal.
 *
 * @param {Object} props
 * @param {(formProps: Object) => JSX.Element} props.children Form children.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(values: ShippingRateGroup) => void} props.onSubmit Called with submitted value.
 */
const RateForm = ( { children, initialValues, onSubmit } ) => {
	/**
	 * @param {ShippingRateGroup} values
	 */
	const handleValidate = ( values ) => {
		const errors = {};

		if ( values.countries.length === 0 ) {
			errors.countries = __(
				'Please specify at least one country.',
				'google-listings-and-ads'
			);
		}

		if ( values.rate < 0 ) {
			errors.rate = __(
				'The estimated shipping rate cannot be less than 0.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	return (
		<Form
			initialValues={ initialValues }
			validate={ handleValidate }
			onSubmit={ onSubmit }
		>
			{ children }
		</Form>
	);
};

export default RateForm;
