/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TrackableLink from '../../../components/trackable-link';

/**
 * Internal dependencies
 */
import './index.scss';

const EditProgramLink = ( props ) => {
	const { programId } = props;

	// TODO: what is the external URL for editing the program.
	const url = `https://example.com/${ programId }`;

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
