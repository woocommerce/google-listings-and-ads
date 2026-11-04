/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountDetailRow from './account-detail-row';
import GoogleAdsTagIndicator from './google-ads-tag-indicator';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainerUrl from '~/hooks/useGoogleTagManagerContainerUrl';

/**
 * @typedef {import('~/data/types.js').GoogleAdsConversionTag} GoogleAdsConversionTag
 */

/**
 * Renders the status of the plugin's own Google Ads tag: its ID, linked to the connected Google Tag
 * Manager container when there is one, and whether the plugin adds the tag ("Active") or leaves it
 * to the container ("Controlled externally").
 *
 * @param {Object} props Component props.
 * @param {GoogleAdsConversionTag} props.conversionTag The plugin's own Google Ads tag.
 * @return {JSX.Element} The Google Ads tag row.
 */
const GoogleAdsTagRow = ( { conversionTag } ) => {
	const { account: tagManagerAccount } = useGoogleTagManagerAccount();
	const containerUrl = useGoogleTagManagerContainerUrl( tagManagerAccount );

	return (
		<AccountDetailRow
			label={ __( 'Google Ads tag', 'google-listings-and-ads' ) }
			indicator={
				<GoogleAdsTagIndicator enabled={ conversionTag.enabled } />
			}
		>
			{ containerUrl ? (
				<ExternalLink href={ containerUrl }>
					{ conversionTag.id }
				</ExternalLink>
			) : (
				<span>{ conversionTag.id }</span>
			) }
		</AccountDetailRow>
	);
};

export default GoogleAdsTagRow;
