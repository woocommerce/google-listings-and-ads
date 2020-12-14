/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const ResumeProgramButton = ( props ) => {
	const { programId } = props;

	// TODO: call backend API to resume program.
	const handleClick = () => {};

	return (
		<Button isLink onClick={ handleClick }>
			{ __( 'Resume', 'google-listings-and-ads' ) }
		</Button>
	);
};

export default ResumeProgramButton;
