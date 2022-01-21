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
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import { useAppDispatch } from '.~/data';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import useGetRemainingCountryCodes from './useGetRemainingCountryCodes';

const AddRateModal = ( props ) => {
	const { onRequestClose } = props;
	const { upsertShippingRates } = useAppDispatch();
	const { code } = useStoreCurrency();
	const remainingCountryCodes = useGetRemainingCountryCodes();

	const handleValidate = ( values ) => {
		const errors = {};

		if ( values.countryCodes.length === 0 ) {
			errors.countryCodes = __(
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
		const { countryCodes, currency, rate } = values;

		const shippingRates = countryCodes.map( ( el ) => ( {
			country: el,
			method: 'flat_rate',
			currency,
			rate,
		} ) );

		upsertShippingRates( shippingRates );

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: remainingCountryCodes,
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
