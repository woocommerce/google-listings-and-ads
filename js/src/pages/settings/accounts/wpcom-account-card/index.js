/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useJetpackAccount from '~/hooks/useJetpackAccount';
import getConnectedJetpackInfo from '~/utils/getConnectedJetpackInfo';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedBadge from '../connected-badge';
import AccountCardTextDetail from '../account-card-text-detail';

/**
 * Renders the WordPress.com account card, which displays information about the connected Jetpack account.
 * @return {JSX.Element} The WordPress.com account card component.
 */
const WPComAccountCard = () => {
	const { jetpack } = useJetpackAccount();

	const isActive = jetpack.active === 'yes';

	return (
		<AccountCard
			appearance={ APPEARANCE.WPCOM }
			description={ __(
				'The account that connects your store to Google for WooCommerce.',
				'google-listings-and-ads'
			) }
			detail={
				<AccountCardTextDetail>
					{ getConnectedJetpackInfo( jetpack ) }
				</AccountCardTextDetail>
			}
			indicator={ isActive ? <ConnectedBadge /> : null }
			alignIndicator="top"
			alignIcon="top"
		/>
	);
};

export default WPComAccountCard;
