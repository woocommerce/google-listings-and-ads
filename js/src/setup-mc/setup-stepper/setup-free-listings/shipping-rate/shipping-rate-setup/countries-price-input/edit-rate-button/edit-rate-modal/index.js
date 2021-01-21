/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../../../../../../../../data';
import AppModal from '../../../../../../../../components/app-modal';
import AppInputControl from '../../../../../../../../components/app-input-control';
import AppCountryMultiSelect from '../../../../../../../../components/app-country-multi-select';
import VerticalGapLayout from '../../../../../components/vertical-gap-layout';
import './index.scss';

const EditRateModal = ( props ) => {
	const { rate, onRequestClose } = props;
	const { addShippingRate, deleteShippingRate } = useDispatch( STORE_KEY );

	const handleDeleteClick = () => {
		rate.countries.forEach( ( el ) => {
			deleteShippingRate( el );
		} );

		onRequestClose();
	};

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = ( values ) => {
		const { countryCodes, currency, price } = values;

		countryCodes.forEach( ( el ) => {
			addShippingRate( {
				countryCode: el,
				currency,
				rate: price,
			} );
		} );

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
								<AppCountryMultiSelect
									{ ...getInputProps( 'countryCodes' ) }
								/>
							</div>
							<AppInputControl
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
