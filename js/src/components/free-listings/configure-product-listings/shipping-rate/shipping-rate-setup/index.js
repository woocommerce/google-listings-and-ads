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
import useShippingRates from '.~/hooks/useShippingRates';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import ShippingCountriesForm from './countries-form';
import './index.scss';

const ShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const {
		loading: loadingShippingRates,
		data: shippingRates,
	} = useShippingRates();
	const { code: currencyCode } = useStoreCurrency();
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes || loadingShippingRates ) {
		return <AppSpinner />;
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<ShippingCountriesForm
					shippingRates={ shippingRates }
					currencyCode={ currencyCode }
					selectedCountryCodes={ selectedCountryCodes }
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
