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
import AppInputControl from '.~/components/app-input-control';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import { useAppDispatch } from '.~/data';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import useGetRemainingCountryCodes from './useGetRemainingCountryCodes';

const AddRateModal = ( props ) => {
	const { onRequestClose } = props;
	const { upsertShippingRate } = useAppDispatch();
	const { code } = useStoreCurrency();
	const remainingCountryCodes = useGetRemainingCountryCodes();

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = ( values ) => {
		const { countryCodes, currency, rate } = values;

		countryCodes.forEach( ( el ) => {
			upsertShippingRate( {
				countryCode: el,
				currency,
				rate,
			} );
		} );

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: remainingCountryCodes,
				currency: code,
				rate: '',
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, handleSubmit } = formProps;

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
							<AppInputControl
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
