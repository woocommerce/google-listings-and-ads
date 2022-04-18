/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

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
 * Form modal to edit or delete shipping rate group.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes, to be used as options in AppCountrySelect.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(values: ShippingRateGroup) => void} props.onSubmit Called when the shipping rate group is submitted.
 * @param {() => void} props.onDelete Called when users clicked on the Delete button.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const EditRateFormModal = ( {
	countryOptions,
	initialValues,
	onSubmit,
	onRequestClose = noop,
	onDelete = noop,
} ) => {
	const handleDeleteClick = () => {
		onRequestClose();
		onDelete();
	};

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
								key="delete"
								isTertiary
								isDestructive
								onClick={ handleDeleteClick }
							>
								{ __( 'Delete', 'google-listings-and-ads' ) }
							</Button>,
							<Button
								key="submit"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __(
									'Update shipping rate',
									'google-listings-and-ads'
								) }
							</Button>,
						] }
						onRequestClose={ onRequestClose }
					/>
				);
			} }
		</RateForm>
	);
};

export default EditRateFormModal;
