/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	FlexItem,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
} from '@wordpress/components';

import {
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useMCProductStatistics from '~/hooks/useMCProductStatistics';
import ProductStatusHelpPopover from './product-status-help-popover';
import SyncStatus from './status-box/sync-status';
import SyncProductStatistics from './status-box/sync-product-statistics';
import FeedStatus from './status-box/feed-status';
import AccountStatus from './status-box/account-status';
import CreateCampaignNotice from './create-campaign-notice';
import Text from '~/components/app-text';
import AppSpinner from '~/components/app-spinner';
import './index.scss';

const ProductStatistics = () => {
	const { hasFinishedResolution, data, refreshStats } =
		useMCProductStatistics();

	if ( hasFinishedResolution && ! data ) {
		return __(
			'An error occurred while retrieving your product feed. Please try again later.',
			'google-listings-and-ads'
		);
	}

	const isLoading = ! hasFinishedResolution || data?.loading;

	let summaryNumberLoadingProps = {};

	if ( isLoading ) {
		summaryNumberLoadingProps = {
			children: <AppSpinner />,
		};
	}

	return (
		<Card className="gla-product-statistics">
			<CardHeader justify="normal">
				<FlexItem>
					<Text as="h2" variant="title-small">
						{ __( 'Overview', 'google-listings-and-ads' ) }
					</Text>
				</FlexItem>
				<ProductStatusHelpPopover />
			</CardHeader>
			<CardBody
				className="gla-product-statistics__summaries"
				size={ null }
			>
				{ ! hasFinishedResolution && (
					<SummaryListPlaceholder numberOfItems={ 5 } />
				) }
				{ hasFinishedResolution && (
					<SummaryList>
						{ () => [
							<SummaryNumber
								key="active"
								label={ __(
									'Active',
									'google-listings-and-ads'
								) }
								value={
									isLoading ? '' : data?.statistics?.active
								}
								{ ...summaryNumberLoadingProps }
							/>,
							<SummaryNumber
								key="expiring"
								label={ __(
									'Expiring',
									'google-listings-and-ads'
								) }
								value={
									isLoading ? '' : data?.statistics?.expiring
								}
								{ ...summaryNumberLoadingProps }
							/>,
							<SummaryNumber
								key="pending"
								label={ __(
									'Pending',
									'google-listings-and-ads'
								) }
								value={
									isLoading ? '' : data?.statistics?.pending
								}
								{ ...summaryNumberLoadingProps }
							/>,
							<SummaryNumber
								key="disapproved"
								label={ __(
									'Disapproved',
									'google-listings-and-ads'
								) }
								value={
									isLoading
										? ''
										: data?.statistics?.disapproved
								}
								{ ...summaryNumberLoadingProps }
							/>,
							<SummaryNumber
								key="not_synced"
								label={ __(
									'Not Synced',
									'google-listings-and-ads'
								) }
								value={
									isLoading
										? ''
										: data?.statistics?.not_synced
								}
								{ ...summaryNumberLoadingProps }
							/>,
						] }
					</SummaryList>
				) }
			</CardBody>

			<CardFooter gap={ 0 }>
				<CreateCampaignNotice />
				<FeedStatus />
				<SyncStatus />
				<AccountStatus />
				{ hasFinishedResolution && data?.error && (
					<SyncProductStatistics
						error={ data.error }
						refreshStats={ refreshStats }
					/>
				) }
			</CardFooter>
		</Card>
	);
};

export default ProductStatistics;
