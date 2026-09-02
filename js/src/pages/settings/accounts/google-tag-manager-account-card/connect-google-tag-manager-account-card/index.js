/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import Indicator from './indicator';
import AccountSelection from './account-selection';

/**
 * Renders the Google Tag Manager account card for the not-yet-connected state: the zero-accounts
 * CTA, single-account auto-select, and multi-account selection, culminating in the "Connect"
 * action. Once an account is connected, `IncompleteGoogleTagManagerAccountCard` takes over for
 * the remaining container-selection step.
 *
 * The picked account and its "Connect" submit action are owned here (not inside
 * `AccountSelection` itself) because "Connect" renders in the `indicator` slot, not inline next
 * to the selector — `Indicator` and `AccountSelection` are siblings, so the value they both need
 * has to live in their common parent. The single-candidate auto-select lives here for the same
 * reason: it's this component's own `accountId` state being set.
 *
 * @return {JSX.Element} The account card.
 */
const ConnectGoogleTagManagerAccountCard = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();
	const { fetchGoogleTagManagerAccount } = useAppDispatch();
	const [ accountId, setAccountId ] = useState();
	const [ fetchConnect, { loading: isConnecting } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/accounts`,
		method: 'POST',
		data: {
			id: accountId,
		},
	} );

	// With only one candidate there's nothing to pick — auto-select it so "Connect" enables
	// without showing a selector that only ever has one option.
	useEffect( () => {
		if ( ! hasFinishedResolution || existingAccounts?.length !== 1 ) {
			return;
		}

		setAccountId( existingAccounts[ 0 ].id );
	}, [ existingAccounts, hasFinishedResolution ] );

	/**
	 * Handles the "Connect" button click: connects the picked account and refreshes connection state.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnectClick = async () => {
		try {
			await fetchConnect();
			await fetchGoogleTagManagerAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect this Google Tag Manager account. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

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
				<AccountSelection
					accountId={ accountId }
					onAccountChange={ setAccountId }
				/>
			}
			expandedDetail
		/>
	);
};

export default ConnectGoogleTagManagerAccountCard;
