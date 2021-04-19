/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';
import AppStandaloneToggleControl from '.~/components/app-standalone-toggle-control';
import AppTooltip from '.~/components/app-tooltip';

const FreeListingsDisabledToggle = () => {
	return (
		<AppTooltip
			text={ __(
				'Free listings cannot be paused through WooCommerce. Go to Google Merchant Center for advanced settings.',
				'google-listings-and-ads'
			) }
		>
			<AppStandaloneToggleControl checked disabled />
		</AppTooltip>
	);
};

export default FreeListingsDisabledToggle;
