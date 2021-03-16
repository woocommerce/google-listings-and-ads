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

/**
 * Internal dependencies
 */
import TrackableLink from '.~/components/trackable-link';
import './index.scss';

function PaidCampaignPromotionCard( { title } ) {
	const href = getNewPath( null, '/google/setup-ads' );

	return (
		<Card>
			<CardHeader size="medium">
				<Text variant="title.small">{ title }</Text>
			</CardHeader>
			<div className="gla-dashboard__performance__promotion-content">
				<Text variant="body">
					{ __(
						'Create your first campaign and get up to $150* free',
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
			</div>
		</Card>
	);
}

export default PaidCampaignPromotionCard;
