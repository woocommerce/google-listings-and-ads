/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppSpinner from '.~/components/app-spinner';
import ShippingCountriesForm from './countries-form';
import './index.scss';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Form control to edit shipping rate settings.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component, containing `offers_free_shipping` and `free_shipping_threshold` properties.
 * @param {Array<CountryCode>} props.selectedCountryCodes Array of country codes of all audience countries.
 */
const ShippingRateSetup = ( { formProps, selectedCountryCodes } ) => {
	const { getInputProps, values } = formProps;
	const { code: currencyCode } = useStoreCurrency();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					{ ...getInputProps( 'shipping_country_rates' ) }
					currencyCode={ currencyCode }
					audienceCountries={ selectedCountryCodes }
				/>
				<CheckboxControl
					label={ __(
						'I also offer free shipping for all countries for products over a certain price.',
						'google-listings-and-ads'
					) }
					{ ...getInputProps( 'offers_free_shipping' ) }
				/>
				{ values.offers_free_shipping && (
					<div className="price-over-input">
						<AppInputControl
							label={ __(
								'I offer free shipping for products priced over',
								'google-listings-and-ads'
							) }
							suffix={ currencyCode }
							{ ...getInputProps( 'free_shipping_threshold' ) }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
