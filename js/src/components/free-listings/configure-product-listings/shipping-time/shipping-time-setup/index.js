/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import Section from '.~/wcdl/section';
import AppSpinner from '.~/components/app-spinner';
import ShippingCountriesForm from './countries-form';

/**
 * Form control to edit shipping rate settings.
 */
const ShippingTimeSetup = () => {
	const { getInputProps, adapter } = useAdaptiveFormContext();

	if ( ! adapter.audienceCountries ) {
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
				<ShippingCountriesForm
					{ ...getInputProps( 'shipping_country_times' ) }
					audienceCountries={ adapter.audienceCountries }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ShippingTimeSetup;
