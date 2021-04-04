/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TrackableLink from '.~/components/trackable-link';

/**
 * Internal dependencies
 */
import './index.scss';

const EditProgramLink = ( props ) => {
	const { programId } = props;

	const url =
		programId === 0
			? getNewPath( { programId }, '/google/edit-free-campaign' )
			: getNewPath( { programId }, '/google/edit-paid-ads-campaign' );

	return (
		<TrackableLink
			eventName="gla_dashboard_edit_program_click"
			eventProps={ { programId, url } }
			href={ url }
			className="edit-program-link"
		>
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</TrackableLink>
	);
};

export default EditProgramLink;
