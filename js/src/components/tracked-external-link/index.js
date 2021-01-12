/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordExternalLinkClickEvent } from '../../utils/recordEvent';

/**
 * A component that wraps around `@woocommerce/components` `Link` component.
 * Upon clicking on the link, it will call `recordExternalLinkClickEvent` with `id` and `href` parameters.
 *
 * You should always provide `id` and `href` props.
 *
 * @param {*} props Link props, plus an `id` prop that will be used in calling `recordExternalLinkClickEvent`.
 */
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
