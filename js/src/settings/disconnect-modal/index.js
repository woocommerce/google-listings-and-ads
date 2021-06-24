/**
 * Internal dependencies
 */
import ConfirmModal from './confirm-modal';
import './index.scss';

export * from './constants';

export default function DisconnectModal( props ) {
	// TODO: add a survey modal when the method of saving the survey results has been decided.
	return <ConfirmModal { ...props } />;
}
