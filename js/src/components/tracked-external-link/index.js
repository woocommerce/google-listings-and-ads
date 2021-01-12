/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordExternalLinkClickEvent } from '../../utils/recordEvent';

const TrackedExternalLink = ( props ) => {
	const { id, href, onClick = () => {}, ...rest } = props;

	const handleClick = () => {
		recordExternalLinkClickEvent( id, href );
		onClick();
	};

	return (
		<Link
			type="external"
			href={ href }
			target="_blank"
			{ ...rest }
			onClick={ handleClick }
		></Link>
	);
};

export default TrackedExternalLink;
