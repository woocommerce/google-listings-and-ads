/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useFreeAdCredit from '.~/hooks/useFreeAdCredit';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import GoogleAdsAccountCard from '.~/components/google-ads-account-card';
import FreeAdCredit from '../../../setup-ads/ads-stepper/setup-accounts/free-ad-credit';

/**
 * Renders a section layout to connect a Google Ads account or show the connected account.
 * After completing the connection, it also shows the free ad credit tip if applicable.
 */
export default function GoogleAdsAccountSection() {
	const hasFreeAdCredit = useFreeAdCredit();

	return (
		<Section
			title={ __( 'Google Ads', 'google-listings-and-ads' ) }
			description={ __(
				'A Google Ads account is required to create paid campaigns with your product listings',
				'google-listings-and-ads'
			) }
		>
			<VerticalGapLayout size="medium">
				<GoogleAdsAccountCard />
				{ hasFreeAdCredit && <FreeAdCredit /> }
			</VerticalGapLayout>
		</Section>
	);
}
