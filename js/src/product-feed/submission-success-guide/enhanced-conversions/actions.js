/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import CTA from './cta';

const Actions = ( { onModalClose = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();

	const handleOnConfirm = useCallback( () => {
		createNotice(
			'info',
			__( 'Status successfully set', 'google-listings-and-ads' )
		);

		onModalClose();
	}, [ createNotice, onModalClose ] );

	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<CTA onConfirm={ handleOnConfirm } />
		</Fragment>
	);
};

export default Actions;
