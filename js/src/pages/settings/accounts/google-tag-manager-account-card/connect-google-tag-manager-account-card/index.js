/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import { GOOGLE_TAG_MANAGER_DESCRIPTION } from '../constants';
import { API_NAMESPACE, ERROR_SLOTS } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useDetailedErrorBySlots from '~/hooks/useDetailedErrorBySlots';
import extractDetailedApiError from '~/utils/extractDetailedApiError';
import Indicator from './indicator';
import AccountSelection from './account-selection';
import ConnectionErrorNotice from './connection-error-notice';

const CONNECTION_ERROR_SLOTS = [
	ERROR_SLOTS.GOOGLE_TAG_MANAGER_CONNECTION_ERROR_SLOT,
];

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
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();
	const {
		fetchGoogleTagManagerAccount,
		receiveDetailedError,
		clearDetailedErrorBySlots,
	} = useAppDispatch();
	const [ accountId, setAccountId ] = useState();
	const [ connectionError ] = useDetailedErrorBySlots(
		CONNECTION_ERROR_SLOTS
	);
	const hasConnectionError = Boolean( connectionError );
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
	 * Handles the "Connect" button click: connects the picked account and refreshes connection
	 * state. A failure switches the card to the connection-failed step rather than a transient
	 * notice, since there's no page navigation here to otherwise lose track of the failure.
	 *
	 * @return {Promise<void>} Resolves when the request completes.
	 */
	const handleConnectClick = async () => {
		try {
			await fetchConnect();
			await fetchGoogleTagManagerAccount();
			clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
		} catch ( error ) {
			const detailedError = await extractDetailedApiError( error );

			// Only trust a genuinely structured backend error for the message shown to the user —
			// `extractDetailedApiError`'s other branches synthesize a generic message (e.g. "An
			// unknown error occurred.") for network failures and other non-API-shaped errors, which
			// would otherwise shadow `ConnectionErrorNotice`'s own curated fallback copy. The slot is
			// still always marked (even with no message) — its presence is what surfaces the
			// failure at all.
			receiveDetailedError(
				ERROR_SLOTS.GOOGLE_TAG_MANAGER_CONNECTION_ERROR_SLOT,
				detailedError?.code === 'API_ERROR' ? detailedError.data : {}
			);
		}
	};

	/**
	 * Handles the "Try again" click on the connection-failed notice: clears the error so the card
	 * switches back to account selection, keeping the previously picked account.
	 */
	const handleTryAgainClick = () => {
		clearDetailedErrorBySlots( CONNECTION_ERROR_SLOTS );
	};

	/**
	 * Forwards `onTryAgain` to `ConnectionErrorNotice` — `AccountCard` only passes `errorSlots` to
	 * its `ErrorComponent`, so this closure is what supplies the retry callback.
	 *
	 * @param {Object} props Props `AccountCard` passes to its `ErrorComponent`.
	 * @return {JSX.Element} The connection-error notice.
	 */
	const ErrorComponent = ( props ) => {
		return (
			<ConnectionErrorNotice
				{ ...props }
				onTryAgain={ handleTryAgainClick }
			/>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_TAG_MANAGER }
			description={ GOOGLE_TAG_MANAGER_DESCRIPTION }
			alignIcon="top"
			alignIndicator="top"
			indicator={
				<Indicator
					hasConnectionError={ hasConnectionError }
					accountId={ accountId }
					isConnecting={ isConnecting }
					onConnectClick={ handleConnectClick }
				/>
			}
			detail={
				! hasConnectionError && (
					<AccountSelection
						accountId={ accountId }
						onAccountChange={ setAccountId }
					/>
				)
			}
			errorSlots={ CONNECTION_ERROR_SLOTS }
			ErrorComponent={ ErrorComponent }
			expandedDetail
		/>
	);
};

export default ConnectGoogleTagManagerAccountCard;
