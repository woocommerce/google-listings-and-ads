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
import './index.scss';

/**
 *Form to edit time for selected country(-ies).
 *
 * @param {Object} props
 * @param {AggregatedShippingTime} props.time
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>) => void} props.onSubmit Called once the time is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 * @param {Function} props.onRequestClose Called when the form is requested ot be closed.
 */
const EditTimeModal = ( { time, onDelete, onSubmit, onRequestClose } ) => {
	const handleDeleteClick = () => {
		onDelete( time.countries );
	};

	const handleValidate = () => {
		const errors = {};

		// TODO: validation logic.

		return errors;
	};

	const handleSubmitCallback = ( newAggregatedTime ) => {
		const remainingCountries = new Set( newAggregatedTime.countries );
		const removedCountries = time.countries.filter(
			( el ) => ! remainingCountries.has( el )
		);

		onSubmit( newAggregatedTime, removedCountries );
	};

	return (
		<Form
			initialValues={ {
				countries: time.countries,
				time: time.time,
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
								{ __(
									'Update shipping time',
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
								<AudienceCountrySelect
									multiple
									{ ...getInputProps( 'countries' ) }
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

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
