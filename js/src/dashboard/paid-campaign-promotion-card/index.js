/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';
import {
	Card,
	CardHeader,
	__experimentalText as Text,
} from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import TrackableLink from '.~/components/trackable-link';
import './index.scss';

const PromotionContent = ( { adsAccount } ) => {
	const href = getNewPath( null, '/google/setup-ads' );
	const showFreeCredit =
		adsAccount.sub_account || adsAccount.status === 'disconnected';

	return (
		<>
			<Text variant="body">
				{ showFreeCredit
					? __(
							'Create your first campaign and get up to $150* free',
							'google-listings-and-ads'
					  )
					: __(
							'Create your first campaign',
							'google-listings-and-ads'
					  ) }
			</Text>
			<TrackableLink
				className="components-button is-secondary is-small"
				eventName="gla_dashboard_link_clicked"
				eventProps={ {
					context: 'add-paid-campaign-promotion',
					href,
				} }
				href={ href }
			>
				{ __( 'Add paid campaign', 'google-listings-and-ads' ) }
			</TrackableLink>
		</>
	);
};

function PaidCampaignPromotionCard( { title } ) {
	const { googleAdsAccount } = useGoogleAdsAccount();

	return (
		<Card>
			<CardHeader size="medium">
				<Text variant="title.small">{ title }</Text>
			</CardHeader>
			<div className="gla-dashboard__performance__promotion-container">
				{ googleAdsAccount ? (
					<PromotionContent adsAccount={ googleAdsAccount } />
				) : (
					<Spinner />
				) }
			</div>
		</Card>
	);
}

export default PaidCampaignPromotionCard;
