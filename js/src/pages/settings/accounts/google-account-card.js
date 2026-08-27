/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedBadge from './connected-badge';
import AccountCardTextDetail from './account-card-text-detail';
import useGoogleAccount from '~/hooks/useGoogleAccount';

/**
 * Renders the Google account card.
 *
 * @return {JSX.Element} The Google account card.
 */
const GoogleAccountCard = () => {
	const { google } = useGoogleAccount();
	const email = google?.email;

	return (
		<AccountCard
			alignIcon="top"
			alignIndicator="top"
			appearance={ APPEARANCE.GOOGLE }
			description={ __(
				'The account you use to log in to Google products.',
				'google-listings-and-ads'
			) }
			detail={
				email ? (
					<AccountCardTextDetail>{ email }</AccountCardTextDetail>
				) : null
			}
			indicator={ email ? <ConnectedBadge /> : null }
		/>
	);
};

export default GoogleAccountCard;
