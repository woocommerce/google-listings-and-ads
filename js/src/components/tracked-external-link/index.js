/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordExternalLinkClickEvent } from '../../utils/recordEvent';

/**
 * A component that wraps around `@woocommerce/components` `Link` component.
 * Upon clicking on the link, it will call `recordExternalLinkClickEvent` with `id` and `href` parameters.
 *
 * @param {Object} props Link props, plus an `id` prop that will be used in calling `gla_external_link_click` track event.
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

TrackedExternalLink.propTypes = {
	/**
	 * The `id` field in the `gla_external_link_click` event property.
	 */
	id: PropTypes.string.isRequired,
};

export default TrackedExternalLink;
