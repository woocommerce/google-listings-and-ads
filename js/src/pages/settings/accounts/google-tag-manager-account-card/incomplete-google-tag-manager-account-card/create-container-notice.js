/**
 * Internal dependencies
 */
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import {
	CREATE_CONTAINER_PERMISSION_DENIED_MESSAGE,
	READ_ONLY_CONNECTION_MESSAGE,
} from '../constants';
import NoticeDetail from '../components/notice-detail';

/**
 * The notice shown for each reason a container can't be created.
 */
const NOTICE_BY_REASON = {
	denied: {
		status: 'warning',
		message: CREATE_CONTAINER_PERMISSION_DENIED_MESSAGE,
	},
	readOnly: {
		status: 'info',
		message: READ_ONLY_CONNECTION_MESSAGE,
	},
};

/**
 * Renders the explanation shown in place of the "Create new container" link when containers can't
 * be created: either Tag Manager refused to create one in the selected account, or the Google
 * connection is read-only. Renders nothing when containers can be created.
 *
 * @return {JSX.Element|null} The notice, or `null` when containers can be created.
 */
export default function CreateContainerNotice() {
	const { account } = useGoogleTagManagerAccount();

	if ( account.canCreateContainer !== false ) {
		return null;
	}

	const { status, message } =
		NOTICE_BY_REASON[
			account.createContainerDenied ? 'denied' : 'readOnly'
		];

	return (
		<div className="gla-google-tag-manager-account-card__notice-wrapper">
			<NoticeDetail status={ status } body={ <p>{ message }</p> } />
		</div>
	);
}
