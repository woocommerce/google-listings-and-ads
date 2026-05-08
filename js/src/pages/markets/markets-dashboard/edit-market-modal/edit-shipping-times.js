/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import CountriesTimeInput from '~/components/free-listings/configure-product-listings/shipping-time-setup/countries-time-input';
import Subsection from '~/components/subsection';

const EditShippingTimes = () => {
	const {
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();

	return (
		<div className="gla-edit-estimated-times">
			<p className="gla-edit-estimated-times__label">
				{ __(
					'Estimated shipping times',
					'google-listings-and-ads'
				) }
			</p>
			<CountriesTimeInput />
			<Subsection.HelperText>
				{ __(
					'Delivery times apply per country, regardless of language or currency.',
					'google-listings-and-ads'
				) }
			</Subsection.HelperText>
			{ renderRequestedValidation( 'flat_shipping_times' ) }
		</div>
	);
};

export default EditShippingTimes;
