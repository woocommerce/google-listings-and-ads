/**
 * Internal dependencies
 */
import { CONNECT_STEP } from '../constants';
import AccountSelection from '../account-selection';
import ConnectionFailed from './connection-failed';

const { CONNECTION_FAILED } = CONNECT_STEP;

/**
 * Renders this card's detail content for the current step: the connection-failed notice, or the
 * normal account-selection detail for every other step.
 *
 * @param {Object} props Component props.
 * @param {string} props.step The current `CONNECT_STEP`.
 * @param {string} [props.accountId] The currently picked account ID (forwarded to `AccountSelection`).
 * @param {( accountId: string ) => void} props.onAccountChange Callback when the picked account changes (forwarded to `AccountSelection`).
 * @param {() => void} props.onTryAgain Callback when the user clicks "Try again" on the connection-failed notice.
 * @return {JSX.Element} The detail.
 */
export default function Detail( {
	step,
	accountId,
	onAccountChange,
	onTryAgain,
} ) {
	if ( step === CONNECTION_FAILED ) {
		return <ConnectionFailed onTryAgain={ onTryAgain } />;
	}

	return (
		<AccountSelection
			accountId={ accountId }
			onAccountChange={ onAccountChange }
		/>
	);
}
