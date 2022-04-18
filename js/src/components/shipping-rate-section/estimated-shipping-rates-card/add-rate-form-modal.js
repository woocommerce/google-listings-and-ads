/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RateFormModal from './rate-form-modal.js';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Form to add a new rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes, to be used as options in AppCountrySelect.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(values: ShippingRateGroup) => void} props.onSubmit Called with submitted value.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const AddRateFormModal = ( props ) => {
	return (
		<RateFormModal
			{ ...props }
			submitButtonChildren={ __(
				'Add shipping rate',
				'google-listings-and-ads'
			) }
		/>
	);
};

export default AddRateFormModal;
