/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Form to edit rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes options, to be used as options in AppCountrySelect.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(newRate: ShippingRateGroup, deletedCountries: Array<CountryCode>) => void} props.onSubmit Called once the rate is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const EditRateFormModal = ( {
	countryOptions,
	initialValues,
	onDelete = noop,
	onSubmit = noop,
	onRequestClose = noop,
} ) => {
	const handleDeleteClick = () => {
		onRequestClose();
		onDelete( initialValues.countries );
	};

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

	const handleSubmitCallback = ( newAggregatedRate ) => {
		onRequestClose();

		const remainingCountries = new Set( newAggregatedRate.countries );
		const removedCountries = initialValues.countries.filter(
			( el ) => ! remainingCountries.has( el )
		);

		onSubmit( newAggregatedRate, removedCountries );
	};

	return (
		<Form
			initialValues={ initialValues }
			validate={ handleValidate }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const {
					getInputProps,
					values,
					isValidForm,
					handleSubmit,
				} = formProps;

				return (
					<AppModal
						title={ __(
							'Estimate a shipping rate',
							'google-listings-and-ads'
						) }
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
								key="save"
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
					>
						<VerticalGapLayout>
							<AppCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								options={ countryOptions }
								multiple
								{ ...getInputProps( 'countries' ) }
							/>
							<AppInputPriceControl
								label={ __(
									'Then the estimated shipping rate displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ values.currency }
								{ ...getInputProps( 'rate' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default EditRateFormModal;
