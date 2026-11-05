/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import HelpPopover from '~/components/help-popover';

/**
 * Renders whether the plugin adds its own Google Ads tag ("Active") or leaves it to the connected
 * Google Tag Manager container ("Controlled externally", with a popover explaining why).
 *
 * @param {Object} props Component props.
 * @param {boolean} [props.enabled] Whether the plugin adds its own Google Ads tag. Only `false` shows it as controlled externally.
 * @return {JSX.Element} The Google Ads tag indicator.
 */
const GoogleAdsTagIndicator = ( { enabled } ) => {
	if ( enabled !== false ) {
		return (
			<Badge intent="success">
				{ __( 'Active', 'google-listings-and-ads' ) }
			</Badge>
		);
	}

	return (
		<>
			<Badge>
				{ __( 'Controlled externally', 'google-listings-and-ads' ) }
			</Badge>
			<HelpPopover
				id="google-ads-tag-controlled-externally"
				position="bottom left"
				iconSize={ 24 }
			>
				{ __(
					"Your Google Tag Manager container already has a tag that tracks Google Ads events. To avoid tracking the same events twice, Google for WooCommerce doesn't add its own Google Ads tag.",
					'google-listings-and-ads'
				) }
			</HelpPopover>
		</>
	);
};

export default GoogleAdsTagIndicator;
