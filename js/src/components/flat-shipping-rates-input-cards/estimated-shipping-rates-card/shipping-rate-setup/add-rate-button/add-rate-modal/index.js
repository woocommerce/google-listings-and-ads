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
import AppInputPriceControl from '.~/components/app-input-price-control/index.js';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * Form to add a new rate for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countries A list of country codes to choose from.
 * @param {Function} props.onRequestClose
 * @param {function(AggregatedShippingRate): void} props.onSubmit Called with submitted value.
 */
const AddRateModal = ( { countries, onRequestClose, onSubmit } ) => {
	const { code } = useStoreCurrency();

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

	const handleSubmitCallback = ( values ) => {
		onSubmit( values );
		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countries,
				currency: code,
				rate: 0,
			} }
			validate={ handleValidate }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, isValidForm, handleSubmit } = formProps;

				return (
					<AppModal
						className="gla-edit-rate-modal"
						title={ __(
							'Estimate a shipping rate',
							'google-listings-and-ads'
						) }
						buttons={ [
							<Button
								key="save"
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
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<div>
								<div className="label">
									{ __(
										'If customer is in',
										'google-listings-and-ads'
									) }
								</div>
								<AppCountrySelect
									options={ countries }
									multiple
									{ ...getInputProps( 'countries' ) }
								/>
							</div>
							<AppInputPriceControl
								label={ __(
									'Then the estimated shipping rate displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ code }
								{ ...getInputProps( 'rate' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default AddRateModal;
/**
 * @typedef {import("../../countries-form.js").AggregatedShippingRate} AggregatedShippingRate
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
