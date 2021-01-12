/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TrackedExternalLink from '../../../components/tracked-external-link';

/**
 * Internal dependencies
 */
import './index.scss';

const EditProgramLink = ( props ) => {
	const { programId } = props;

	// TODO: what is the external URL for editing the program.
	const url = `https://example.com/${ programId }`;

	return (
		<TrackedExternalLink
			id="dashboard:edit-program"
			href={ url }
			className="edit-program-link"
		>
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</TrackedExternalLink>
	);
};

export default EditProgramLink;
