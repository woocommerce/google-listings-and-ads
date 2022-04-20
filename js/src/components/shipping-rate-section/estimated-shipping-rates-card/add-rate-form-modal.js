/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Form } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RateModal from './rate-modal.js';
import validate from './validate.js';

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
		<Form
			initialValues={ initialValues }
			validate={ validate }
			onSubmit={ onSubmit }
		>
			{ ( formProps ) => {
				const { isValidForm, handleSubmit } = formProps;

				const handleAddClick = () => {
					onRequestClose();
					handleSubmit();
				};

				return (
					<RateModal
						formProps={ formProps }
						countryOptions={ countryOptions }
						buttons={ [
							<Button
								key="submit"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleAddClick }
							>
								{ __(
									'Add shipping rate',
									'google-listings-and-ads'
								) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					/>
				);
			} }
		</Form>
	);
};

export default AddRateFormModal;
