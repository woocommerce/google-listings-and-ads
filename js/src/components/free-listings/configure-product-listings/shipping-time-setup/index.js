/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import AppSpinner from '~/components/app-spinner';
import CountriesTimeInput from './countries-time-input';

/**
 * Form control to edit shipping rate settings.
 */
const ShippingTimeSetup = () => {
	const {
		adapter: { audienceCountries, renderRequestedValidation },
	} = useAdaptiveFormContext();

	if ( ! audienceCountries ) {
		return <AppSpinner />;
	}

	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping times',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>

				<CountriesTimeInput />

				{ renderRequestedValidation( 'flat_shipping_times' ) }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ShippingTimeSetup;
