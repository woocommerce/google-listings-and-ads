/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedBadge from '../connected-badge';
import AccountCardTextDetail from '../account-card-text-detail';
import useGoogleAccount from '~/hooks/useGoogleAccount';

const GoogleAccountCard = () => {
	const { google } = useGoogleAccount();

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			description={ __(
				'The account you use to log in to Google products.',
				'google-listings-and-ads'
			) }
			detail={
				<AccountCardTextDetail>{ google.email }</AccountCardTextDetail>
			}
			indicator={ Boolean( google.email ) ? <ConnectedBadge /> : null }
			alignIndicator="top"
			alignIcon="top"
		/>
	);
};

export default GoogleAccountCard;
