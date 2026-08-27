/**
 * Internal dependencies
 */
import { GOOGLE_TAG_MANAGER_STEP } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import CreateAccount from './create-account';
import AccountSelection from './account-selection';
import ContainerSelection from './container-selection';

const { NO_ACCOUNT, ACCOUNT_SELECTION, CONTAINER_SELECTION } =
	GOOGLE_TAG_MANAGER_STEP;

const DETAIL_BY_STEP = {
	[ NO_ACCOUNT ]: CreateAccount,
	[ ACCOUNT_SELECTION ]: AccountSelection,
	[ CONTAINER_SELECTION ]: ContainerSelection,
};

/**
 * Renders the `AccountCard` `detail` content for the current not-yet-connected step, falling
 * back to the zero-accounts CTA for the disconnected/error status (which has no `step` at all)
 * and anything else not covered by a more specific step.
 *
 * @param {Object} props Component props.
 * @param {string} [props.accountId] The currently picked (not yet connected) account ID —
 *   forwarded to whichever step's component renders; only `AccountSelection` uses it. Its
 *   "Connect" submit action lives in the sibling `Indicator`, owned by their common parent.
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account
 *   changes — forwarded the same way.
 * @return {JSX.Element|null} The detail, or `null` until the account has resolved.
 */
export default function Detail( { accountId, onAccountChange } ) {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const DetailComponent = DETAIL_BY_STEP[ account?.step ] ?? CreateAccount;

	return (
		<DetailComponent
			accountId={ accountId }
			onAccountChange={ onAccountChange }
		/>
	);
}
