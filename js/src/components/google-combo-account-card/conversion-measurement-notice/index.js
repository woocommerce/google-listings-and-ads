/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppNotice from '.~/components/app-notice';

const ConversionMeasurementNotice = () => {
	return (
		<AppNotice status="success" isDismissible={ false }>
			{ __(
				'Google Ads conversion measurement has been set up for your store.',
				'google-listings-and-ads'
			) }
		</AppNotice>
	);
};

export default ConversionMeasurementNotice;
