/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import ReviewRequestModal from './review-request-modal';
import ReviewRequestNotice from './review-request-notice';
import { ISSUE_TYPE_ACCOUNT, REQUEST_REVIEW } from '.~/constants';
import REVIEW_STATUSES from './review-request-statuses';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import './index.scss';

const showNotice = ( status ) => !! REVIEW_STATUSES[ status ]?.title;

/**
 * @typedef { import(".~/data/actions").AccountStatus } AccountStatus
 */

/**
 * @fires gla_modal_closed with `action: 'request-review-success' | 'maybe-later' | 'dismiss', context: REQUEST_REVIEW`
 * @fires gla_modal_open with `context: REQUEST_REVIEW`
 *
 * @param {Object} props Component props
 * @param { { isResolving: boolean, hasFinishedResolution: boolean, data: AccountStatus, invalidateResolution: Function } } props.account Account data payload coming from the data store.
 */
const ReviewRequest = ( { account = {} } ) => {
	const [ modalActive, setModalActive ] = useState( false );
	const activeIssueType = useActiveIssueType();

	const {
		data: mcData,
		hasFinishedResolution: mcDataHasFinishedResolution,
	} = useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT, 1, 200 );
	const { data: accountData, hasFinishedResolution } = account;

	if (
		! mcDataHasFinishedResolution ||
		! hasFinishedResolution ||
		! showNotice( accountData.status ) ||
		activeIssueType !== ISSUE_TYPE_ACCOUNT
	) {
		return null;
	}

	const handleNoticeClick = () => {
		setModalActive( true );
		recordEvent( 'gla_modal_open', { context: REQUEST_REVIEW } );
	};

	const handleModalClose = ( action ) => {
		setModalActive( false );
		recordEvent( 'gla_modal_closed', {
			context: REQUEST_REVIEW,
			action,
		} );
	};

	return (
		<div className="gla-review-request">
			<ReviewRequestModal
				issues={ mcData.issues.filter( ( issue ) =>
					accountData.issues.includes( issue.code )
				) }
				isActive={ modalActive }
				onClose={ handleModalClose }
			/>
			<ReviewRequestNotice
				account={ accountData }
				onRequestReviewClick={ handleNoticeClick }
			/>
		</div>
	);
};

export default ReviewRequest;
