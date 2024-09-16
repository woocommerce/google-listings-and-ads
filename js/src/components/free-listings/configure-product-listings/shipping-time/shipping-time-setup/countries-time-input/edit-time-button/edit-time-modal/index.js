/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Form } from '@woocommerce/components';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AppModal from '.~/components/app-modal';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import SupportedCountrySelect from '.~/components/supported-country-select';
import validateShippingTimeGroup from '.~/utils/validateShippingTimeGroup';
import MinMaxShippingTimes from '../../../min-max-inputs';

/**
 *Form to edit time for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.audienceCountries List of all audience countries.
 * @param {AggregatedShippingTime} props.time
 * @param {(newTime: AggregatedShippingTime, deletedCountries: Array<CountryCode>) => void} props.onSubmit Called once the time is submitted.
 * @param {(deletedCountries: Array<CountryCode>) => void} props.onDelete Called with list of countries once Delete was requested.
 * @param {Function} props.onRequestClose Called when the form is requested ot be closed.
 */
const EditTimeModal = ( {
	audienceCountries,
	time,
	onDelete,
	onSubmit,
	onRequestClose,
} ) => {
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	// We actually may have times for more countries than the audience ones.
	const availableCountries = Array.from(
		new Set( [ ...time.countries, ...audienceCountries ] )
	);

	const handleDeleteClick = () => {
		onDelete( time.countries );
	};

	const handleSubmitCallback = ( values ) => {
		const remainingCountries = new Set( values.countries );
		const removedCountries = time.countries.filter(
			( el ) => ! remainingCountries.has( el )
		);

		onSubmit( values, removedCountries );
	};

	return (
		<Form
			initialValues={ {
				countries: time.countries,
				time: time.time,
				maxTime: time.maxTime,
			} }
			validate={ validateShippingTimeGroup }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, isValidForm, handleSubmit } = formProps;

				const handleIncrement = ( numberValue, field ) => {
					getInputProps( field ).onChange( numberValue );
				};

				return (
					<AppModal
						overflow="visible"
						shouldCloseOnEsc={ ! dropdownVisible }
						shouldCloseOnClickOutside={ ! dropdownVisible }
						className="gla-edit-time-modal"
						title={ __(
							'Estimate shipping time',
							'google-listings-and-ads'
						) }
						buttons={ [
							<AppButton
								key="delete"
								isTertiary
								isDestructive
								onClick={ handleDeleteClick }
							>
								{ __( 'Delete', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __(
									'Update shipping time',
									'google-listings-and-ads'
								) }
							</AppButton>,
						] }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<SupportedCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								countryCodes={ availableCountries }
								onDropdownVisibilityChange={
									setDropdownVisible
								}
								{ ...getInputProps( 'countries' ) }
							/>

							<div className="label">
								{ __(
									'Then the estimated shipping times displayed in the product listing are',
									'google-listings-and-ads'
								) }
							</div>

							<Flex
								direction="column"
								className="gla-countries-time-input-container"
							>
								<FlexItem>
									<MinMaxShippingTimes
										time={ getInputProps( 'time' ).value }
										maxTime={
											getInputProps( 'maxTime' ).value
										}
										handleBlur={ handleIncrement }
										handleIncrement={ handleIncrement }
									/>
								</FlexItem>
							</Flex>
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
