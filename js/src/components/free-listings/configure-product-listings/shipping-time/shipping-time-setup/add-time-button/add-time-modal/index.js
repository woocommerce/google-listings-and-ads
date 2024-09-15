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
import Stepper from '../../time-stepper';

/**
 * Form to add a new time for selected country(-ies).
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countries A list of country codes to choose from.
 * @param {Function} props.onRequestClose
 * @param {function(AggregatedShippingTime): void} props.onSubmit Called with submitted value.
 */
const AddTimeModal = ( { countries, onRequestClose, onSubmit } ) => {
	const [ dropdownVisible, setDropdownVisible ] = useState( false );

	const handleSubmitCallback = ( values ) => {
		onSubmit( values );
		onRequestClose();
	};

	return (
		<Form
			initialValues={ {
				countries,
				time: 0,
				maxTime: 0,
			} }
			validate={ validateShippingTimeGroup }
			onSubmit={ handleSubmitCallback }
		>
			{ ( formProps ) => {
				const { getInputProps, isValidForm, handleSubmit } = formProps;

				return (
					<AppModal
						overflow="visible"
						shouldCloseOnEsc={ ! dropdownVisible }
						shouldCloseOnClickOutside={ ! dropdownVisible }
						title={ __(
							'Estimate shipping time',
							'google-listings-and-ads'
						) }
						buttons={ [
							<AppButton
								key="save"
								isPrimary
								disabled={ ! isValidForm }
								onClick={ handleSubmit }
							>
								{ __(
									'Add shipping time',
									'google-listings-and-ads'
								) }
							</AppButton>,
						] }
						onRequestClose={ onRequestClose }
						className="gla-add-time-modal"
					>
						<VerticalGapLayout>
							<SupportedCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								countryCodes={ countries }
								onDropdownVisibilityChange={
									setDropdownVisible
								}
								{ ...getInputProps( 'countries' ) }
							/>
							<div className="label">
								{ __(
									'Then the estimated shipping times displayed in the product listing are:',
									'google-listings-and-ads'
								) }
							</div>

							<Flex
								direction="column"
								className="gla-countries-time-input-container"
							>
								<FlexItem>
									<Flex justify="space-between" gap="4">
										<FlexItem>
											<div className="gla-countries-time-input">
												<Stepper
													time={
														getInputProps( 'time' )
															.value
													}
													field="time"
													handleBlur={
														getInputProps( 'time' )
															.onChange
													}
													handleIncrement={
														getInputProps( 'time' )
															.onChange
													}
												/>
											</div>
										</FlexItem>
										<FlexItem>
											<span>
												{ __(
													'to',
													'google-listings-and-ads'
												) }
											</span>
										</FlexItem>
										<FlexItem>
											<div className="gla-countries-time-input">
												<Stepper
													time={
														getInputProps(
															'maxTime'
														).value
													}
													field="maxTime"
													handleBlur={
														getInputProps(
															'maxTime'
														).onChange
													}
													handleIncrement={
														getInputProps(
															'maxTime'
														).onChange
													}
												/>
											</div>
										</FlexItem>
									</Flex>
								</FlexItem>
							</Flex>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default AddTimeModal;

/**
 * @typedef { import(".~/data/actions").AggregatedShippingTime } AggregatedShippingTime
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */
