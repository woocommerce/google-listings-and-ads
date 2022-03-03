/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * Form to edit rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {AggregatedShippingRate} props.rate
 * @param {(newRate: AggregatedShippingRate, deletedCountries: Array<CountryCode>) => void} props.onSubmit Called once the rate is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 * @param {Function} props.onRequestClose Called when the form is requested ot be closed.
 */
const EditRateModal = ( {
	audienceCountries,
	rate,
	onDelete,
	onSubmit,
	onRequestClose,
} ) => {
	// We actually may have rates for more countries than the audience ones.
	const availableCountries = Array.from(
		new Set( [ ...rate.countries, ...audienceCountries ] )
	);

	const handleDeleteClick = () => {
		onDelete( rate.countries );
	};

	const handleValidate = ( values ) => {
		const errors = {};

		if ( values.countries.length === 0 ) {
			errors.countries = __(
				'Please specify at least one country.',
				'google-listings-and-ads'
			);
		}

		if ( values.price < 0 ) {
			errors.price = __(
				'The estimated shipping rate cannot be less than 0.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmitCallback = ( newAggregatedRate ) => {
		const remainingCountries = new Set( newAggregatedRate.countries );
		const removedCountries = rate.countries.filter(
			( el ) => ! remainingCountries.has( el )
		);

		onSubmit( newAggregatedRate, removedCountries );
	};

	return (
		<Form
			initialValues={ {
				countries: rate.countries,
				currency: rate.currency,
				price: rate.price,
			} }
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
						className="gla-edit-rate-modal"
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
								options={ availableCountries }
								multiple
								{ ...getInputProps( 'countries' ) }
							/>
							<AppInputPriceControl
								label={ __(
									'Then the estimated shipping rate displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ values.currency }
								{ ...getInputProps( 'price' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default EditRateModal;

/**
 * @typedef {import("../../../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
