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
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AudienceCountrySelect from '.~/components/audience-country-select';
import { useAppDispatch } from '.~/data';
import useGetRemainingCountryCodes from './useGetRemainingCountryCodes';

const AddTimeModal = ( props ) => {
	const { onRequestClose } = props;
	const { upsertShippingTime } = useAppDispatch();
	const remainingCountryCodes = useGetRemainingCountryCodes();

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = ( values ) => {
		const { countryCodes, time } = values;

		countryCodes.forEach( ( el ) => {
			upsertShippingTime( {
				countryCode: el,
				time,
			} );
		} );

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: remainingCountryCodes,
				time: '',
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, handleSubmit } = formProps;

				return (
					<AppModal
						title={ __(
							'Estimate shipping time',
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
									'Then the estimated shipping time displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ __(
									'days',
									'google-listings-and-ads'
								) }
								{ ...getInputProps( 'time' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default AddTimeModal;
