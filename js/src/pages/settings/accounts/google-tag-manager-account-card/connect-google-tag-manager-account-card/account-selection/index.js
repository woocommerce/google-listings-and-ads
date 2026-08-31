/**
 * Internal dependencies
 */
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import NoTagManagerAccountNotice from './no-tag-manager-account-notice';
import SingleTagManagerAccountNotice from './single-tag-manager-account-notice';
import MultipleTagManagerAccountsNotice from './multiple-tag-manager-accounts-notice';

/**
 * Renders the account-selection detail: the zero-accounts notice (see
 * `NoTagManagerAccountNotice`), the single candidate account shown as plain text, or a selector
 * when more than one exists.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked account ID.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes.
 * @return {JSX.Element|null} The detail, or `null` until the accounts list has resolved.
 */
export default function AccountSelection( { accountId, onAccountChange } ) {
	const { existingAccounts, hasFinishedResolution } =
		useExistingGoogleTagManagerAccounts();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( ! existingAccounts?.length ) {
		return <NoTagManagerAccountNotice />;
	}

	if ( existingAccounts.length === 1 ) {
		const [ singleAccount ] = existingAccounts;

		return <SingleTagManagerAccountNotice account={ singleAccount } />;
	}

	return (
		<MultipleTagManagerAccountsNotice
			accountId={ accountId }
			onAccountChange={ onAccountChange }
		/>
	);
}
