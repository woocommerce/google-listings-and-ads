/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RateForm from './rate-form.js';
import RateModal from './rate-modal.js';

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
const AddRateFormModal = ( {
	countryOptions,
	initialValues,
	onSubmit,
	onRequestClose,
} ) => {
	return (
		<RateForm
			initialValues={ initialValues }
			onSubmit={ onSubmit }
			onRequestClose={ onRequestClose }
		>
			{ ( formProps ) => {
				const { isValidForm, handleSubmit } = formProps;

				return (
					<RateModal
						formProps={ formProps }
						countryOptions={ countryOptions }
						buttons={ [
							<Button
								key="submit"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __(
									'Add shipping rate',
									'google-listings-and-ads'
								) }
							</Button>,
						] }
					/>
				);
			} }
		</RateForm>
	);
};

export default AddRateFormModal;
