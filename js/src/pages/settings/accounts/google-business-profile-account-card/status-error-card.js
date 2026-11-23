/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import { GOOGLE_BUSINESS_PROFILE_DESCRIPTION } from './constants';
import NoticeDetail from './notice-detail';

/**
 * Renders the card shown when the Google Business Profile connection status couldn't be loaded.
 * The status is unknown, so the card shows neither the connected nor the not-connected state, only
 * an error notice with a "Try again" refetch of the status.
 *
 * @return {JSX.Element} The account card.
 */
export default function StatusErrorCard() {
	const { fetchGoogleBusinessProfileAccount } = useAppDispatch();
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	const handleTryAgainClick = async () => {
		setIsRefreshing( true );
		await fetchGoogleBusinessProfileAccount();
		setIsRefreshing( false );
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_BUSINESS_PROFILE }
			description={ GOOGLE_BUSINESS_PROFILE_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			detail={
				<NoticeDetail
					status="error"
					body={
						<p>
							{ __(
								"We couldn't load your Google Business Profile connection status.",
								'google-listings-and-ads'
							) }
						</p>
					}
					actions={ [
						<AppButton
							key="try-again"
							onClick={ handleTryAgainClick }
							loading={ isRefreshing }
							isSecondary
						>
							{ __( 'Try again', 'google-listings-and-ads' ) }
						</AppButton>,
					] }
				/>
			}
			expandedDetail
		/>
	);
}
