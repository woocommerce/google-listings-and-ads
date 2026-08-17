/**
 * Internal dependencies
 */
import { SEARCH_CONSOLE_ACCOUNT_STEP } from '~/constants';
import PropertySelectionDetail from './property-selection-detail';
import VerificationDetail from './verification-detail';
import ActionNeededDetail from './action-needed-detail';
import ReconnectDetail from './reconnect-detail';
import ConnectionFailedDetail from './connection-failed-detail';
import GenericDetail from './generic-detail';

const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

const DETAIL_BY_STEP = {
	[ PROPERTY_SELECTION ]: PropertySelectionDetail,
	[ VERIFICATION ]: VerificationDetail,
	[ ACTION_NEEDED ]: ActionNeededDetail,
	[ RECONNECT ]: ReconnectDetail,
	[ CONNECTION_FAILED ]: ConnectionFailedDetail,
};

/**
 * Renders the `AccountCard` `detail` content for the current incomplete-flow step, falling back
 * to a generic "resume setup" message for a step that isn't covered by a more specific one.
 *
 * @param {Object} props Component props.
 * @param {string} [props.step] The current incomplete-flow step.
 * @param {Object} [props.account] The Search Console account.
 * @return {JSX.Element} The detail.
 */
export default function Detail( { step, account } ) {
	const DetailComponent = DETAIL_BY_STEP[ step ] ?? GenericDetail;

	return <DetailComponent account={ account } />;
}
