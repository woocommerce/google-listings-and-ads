/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const EditProgramLink = ( props ) => {
	const { programId } = props;

	// TODO: what is the external URL for editing the program.
	const url = `https://example.com/${ programId }`;

	return (
		<Link
			type="external"
			href={ url }
			target="_blank"
			className="edit-program-link"
		>
			{ __( 'Edit', 'google-listings-and-ads' ) }
		</Link>
	);
};

export default EditProgramLink;
