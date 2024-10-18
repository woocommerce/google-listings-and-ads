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
const ConnectAdsFooter = ( { isConnected } ) => {
	const text = isConnected
		? __(
				'Or, connect to a different Google Ads account',
				'google-listings-and-ads'
		  )
		: __(
				'Or, create a new Google Ads account',
				'google-listings-and-ads'
		  );
	return <AppButton isTertiary>{ text }</AppButton>;
};

export default ConnectAdsFooter;
