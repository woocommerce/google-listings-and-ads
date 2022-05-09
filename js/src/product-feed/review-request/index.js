/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useActiveIssueType from '.~/hooks/useActiveIssueType';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import ReviewRequestModal from './review-request-modal';
import ReviewRequestNotice from './review-request-notice';
import { ISSUE_TYPE_ACCOUNT, REQUEST_REVIEW } from '.~/constants';
import REVIEW_STATUSES from './review-request-statuses';
import useMCIssuesTypeFilter from '.~/hooks/useMCIssuesTypeFilter';
import './index.scss';

const showNotice = ( status ) => !! REVIEW_STATUSES[ status ]?.title;

const ReviewRequest = ( { account = {} } ) => {
	const [ modalActive, setModalActive ] = useState( false );
	const activeIssueType = useActiveIssueType();
	const { mcRequestReview } = useAppDispatch();
	const { createNotice } = useDispatchCoreNotices();

	const {
		data: mcData,
		hasFinishedResolution: mcDataHasFinishedResolution,
	} = useMCIssuesTypeFilter( ISSUE_TYPE_ACCOUNT, 1, 200 );
	const { data: accountData, hasFinishedResolution } = account;

	if (
		! mcDataHasFinishedResolution ||
		! hasFinishedResolution ||
		! showNotice( accountData?.status ) ||
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

	const handleReviewRequest = () => {
		handleModalClose( 'confirm-request-review' );
		recordEvent( 'gla_request_review' );

		mcRequestReview()
			.then( () => {
				createNotice(
					'success',
					__(
						'Your account review was successfully requested.',
						'google-listings-and-ads'
					)
				);
				recordEvent( 'gla_request_review_success' );
			} )
			.catch( ( error ) => {
				recordEvent( 'gla_request_review_failure', {
					error: error?.message,
				} );
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
				onSendRequest={ handleReviewRequest }
			/>
			<ReviewRequestNotice
				account={ accountData }
				onRequestReviewClick={ handleNoticeClick }
			/>
		</div>
	);
};

export default ReviewRequest;
