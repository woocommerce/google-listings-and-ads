/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountDetailRow from './account-detail-row';
import Badge from '~/components/badge';
import HelpPopover from '~/components/help-popover';
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

	// Only an explicit `false` means the container controls the tag; anything else shows it as active.
	const indicator =
		conversionTag.enabled !== false ? (
			<Badge intent="success">
				{ __( 'Active', 'google-listings-and-ads' ) }
			</Badge>
		) : (
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

	return (
		<AccountDetailRow
			label={ __( 'Google Ads tag', 'google-listings-and-ads' ) }
			indicator={ indicator }
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
