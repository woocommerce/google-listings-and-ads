/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';

/**
 * Footer component.
 *
 * @return {JSX.Element} Footer component.
 */
const ConnectAdsFooter = () => (
	<AppButton isTertiary>
		{ __(
			'Or, create a new Google Ads account',
			'google-listings-and-ads'
		) }
	</AppButton>
);

export default ConnectAdsFooter;
