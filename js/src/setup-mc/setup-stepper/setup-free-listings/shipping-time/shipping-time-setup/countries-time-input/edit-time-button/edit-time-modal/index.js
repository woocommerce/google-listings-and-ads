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
import VerticalGapLayout from '../../../../../components/vertical-gap-layout';
import AudienceCountrySelect from '../../../../../components/audience-country-select';
import './index.scss';
import { useAppDispatch } from '.~/data';

const EditTimeModal = ( props ) => {
	const { time: groupedTime, onRequestClose } = props;
	const { upsertShippingTime, deleteShippingTime } = useAppDispatch();

	const handleDeleteClick = () => {
		groupedTime.countries.forEach( ( el ) => {
			deleteShippingTime( el );
		} );

		onRequestClose();
	};

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

		const valuesCountrySet = new Set( values.countryCodes );
		groupedTime.countries.forEach( ( el ) => {
			if ( ! valuesCountrySet.has( el ) ) {
				deleteShippingTime( el );
			}
		} );

		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countryCodes: groupedTime.countries,
				time: groupedTime.time,
			} }
			validate={ handleValidate }
			onSubmitCallback={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, handleSubmit } = formProps;

				return (
					<AppModal
						className="gla-edit-time-modal"
						title={ __(
							'Estimate shipping time',
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

export default EditTimeModal;
