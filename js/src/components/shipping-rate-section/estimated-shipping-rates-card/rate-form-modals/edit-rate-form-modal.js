/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import RateFormModal from './rate-form-modal.js';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("../typedefs.js").ShippingRateGroup } ShippingRateGroup
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
		<RateFormModal
			countryOptions={ countryOptions }
			initialValues={ initialValues }
			renderButtons={ ( formProps ) => {
				const { isValidForm, handleSubmit } = formProps;

				const handleUpdateClick = () => {
					onRequestClose();
					handleSubmit();
				};

				return [
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
						onClick={ handleUpdateClick }
					>
						{ __(
							'Update shipping rate',
							'google-listings-and-ads'
						) }
					</Button>,
				];
			} }
			onSubmit={ onSubmit }
			onRequestClose={ onRequestClose }
		/>
	);
};

export default EditRateFormModal;
