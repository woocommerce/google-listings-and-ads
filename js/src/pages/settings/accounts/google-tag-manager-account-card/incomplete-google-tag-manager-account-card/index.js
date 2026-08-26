/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import useConnectGoogleTagManagerAccount from '../hooks/useConnectGoogleTagManagerAccount';
import Indicator from './indicator';
import Detail from './detail';

/**
 * Renders the Google Tag Manager account card for every not-yet-connected status: the
 * zero-accounts CTA, account selection, and container selection. All of these share the same
 * `AccountCard` layout, varying the `indicator` and `detail` content for the current status.
 *
 * The account-selection status's pending pick and its "Connect" submit action are owned here
 * (not inside the account-selection detail step itself) because "Connect" renders in the
 * `indicator` slot, not inline next to the selector — `Indicator` and `Detail` are siblings, so
 * the value they both need has to live in their common parent.
 *
 * @return {JSX.Element} The account card.
 */
const IncompleteGoogleTagManagerAccountCard = () => {
	const [ accountId, setAccountId ] = useState();
	const { connect, loading: isConnecting } =
		useConnectGoogleTagManagerAccount();

	const handleConnectClick = () => connect( accountId );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<Indicator
					accountId={ accountId }
					isConnecting={ isConnecting }
					onConnectClick={ handleConnectClick }
				/>
			}
			detail={
				<Detail
					accountId={ accountId }
					onAccountChange={ setAccountId }
				/>
			}
			expandedDetail
		/>
	);
};

export default IncompleteGoogleTagManagerAccountCard;
