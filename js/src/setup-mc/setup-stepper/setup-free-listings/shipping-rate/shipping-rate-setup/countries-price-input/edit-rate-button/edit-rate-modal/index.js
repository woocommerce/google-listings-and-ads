/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import './index.scss';

const EditRateModal = ( props ) => {
	const { rate, onRequestClose } = props;
	const { upsertShippingRates, deleteShippingRates } = useAppDispatch();

	const handleDeleteClick = () => {
		deleteShippingRates( rate.countries );

		onRequestClose();
	};

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = ( values ) => {
		const { countryCodes, currency, price } = values;

		upsertShippingRates( {
			countryCodes,
			currency,
			rate: price,
		} );

		const valuesCountrySet = new Set( values.countryCodes );
		const deletedCountryCodes = rate.countries.filter(
			( el ) => ! valuesCountrySet.has( el )
		);
		if ( deletedCountryCodes.length ) {
			deleteShippingRates( deletedCountryCodes );
		}

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: rate.countries,
				currency: rate.currency,
				price: rate.price,
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, values, handleSubmit } = formProps;

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
								onClick={ handleSubmit }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
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
								<AudienceCountrySelect
									multiple
									{ ...getInputProps( 'countryCodes' ) }
								/>
							</div>
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
