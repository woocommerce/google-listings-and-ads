/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import GridiconExternal from 'gridicons/dist/external';

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
			Edit <GridiconExternal size={ 12 } />
		</Link>
	);
};

export default EditProgramLink;
