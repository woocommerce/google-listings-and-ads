/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '.~/components/app-modal';
import AppInputPriceControl from '.~/components/app-input-price-control/index.js';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AppCountrySelect from '.~/components/app-country-select';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Rate modal.
 *
 * The `formProps` prop should come from the RateForm component.
 *
 * This is used by AddRateFormModal and EditRateFormModal.
 *
 * @param {Object} props
 * @param {Array<CountryCode>} props.countryOptions Array of country codes, to be used as options in AppCountrySelect.
 * @param {() => void} props.onRequestClose Callback to close the modal.
 * @param {Array<Button>} props.buttons Buttons for the AppModal.
 * @param {Object} props.formProps Form props. This should come from RateForm.
 */
const RateModal = ( {
	countryOptions,
	buttons,
	formProps,
	onRequestClose,
} ) => {
	const { getInputProps, values } = formProps;

	return (
		<AppModal
			title={ __(
				'Estimate a shipping rate',
				'google-listings-and-ads'
			) }
			buttons={ buttons }
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
};

export default RateModal;
