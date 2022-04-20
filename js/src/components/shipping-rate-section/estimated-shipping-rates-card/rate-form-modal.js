/**
 * External dependencies
 */
import { Form } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import validate from './validate.js';
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control/index.js';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * @typedef { import("@wordpress/components").Button } Button
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 * @typedef { import("./typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * Base rate form modal.
 *
 * This is used by AddRateFormModal and EditRateFormModal.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes, to be used as options in AppCountrySelect.
 * @param {ShippingRateGroup} props.initialValues Initial values for the form.
 * @param {(formProps: Object) => Array<Button>} props.renderButtons Function to render buttons for the modal. `formProps` will be passed into this render function.
 * @param {(values: ShippingRateGroup) => void} props.onSubmit Called with submitted value.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 */
const RateFormModal = ( {
	countryOptions,
	initialValues,
	renderButtons = noop,
	onSubmit,
	onRequestClose,
} ) => {
	return (
		<Form
			initialValues={ initialValues }
			validate={ validate }
			onSubmit={ onSubmit }
		>
			{ ( formProps ) => {
				const { values, getInputProps } = formProps;

				return (
					<AppModal
						title={ __(
							'Estimate a shipping rate',
							'google-listings-and-ads'
						) }
						buttons={ renderButtons( formProps ) }
						onRequestClose={ onRequestClose }
					>
						<VerticalGapLayout>
							<AppCountrySelect
								label={ __(
									'If customer is in',
									'google-listings-and-ads'
								) }
								options={ countryOptions }
								multiple
								{ ...getInputProps( 'countries' ) }
							/>
							<AppInputPriceControl
								label={ __(
									'Then the estimated shipping rate displayed in the product listing is',
									'google-listings-and-ads'
								) }
								suffix={ values.currency }
								{ ...getInputProps( 'rate' ) }
							/>
						</VerticalGapLayout>
					</AppModal>
				);
			} }
		</Form>
	);
};

export default RateFormModal;
