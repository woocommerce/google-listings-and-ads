/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Button to resume a paused campaign.
 *
 * @param {Object} props
 * @param {string} props.programId Id of a program to be resumed.
 *
 */
const ResumeProgramButton = ( props ) => {
	const { programId } = props;

	// TODO: call backend API to resume program.
	const handleClick = () => {
		// eslint-disable-next-line no-console
		console.warn(
			'The actual resume action is not implemented/integrated yet.',
			programId
		);
	};

	return (
		<Button isLink onClick={ handleClick }>
			{ __( 'Resume', 'google-listings-and-ads' ) }
		</Button>
	);
};

export default ResumeProgramButton;
