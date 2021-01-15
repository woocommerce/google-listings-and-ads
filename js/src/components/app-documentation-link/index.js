/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TrackableLink from '../trackable-link';

/**
 * Renders a `TrackableLink` that will open the link in new tab and
 * call `recordEvent` with `gla_documentation_link_click` event name.
 *
 * - `eventName="gla_documentation_link_click"`
 * - `eventProps={ { context, link_id: linkId, href } }`
 * - `type="external"` by default.
 * - `target="_blank"` by default.
 *
 * @param {Object} props Props to be forwarded to {@link TrackableLink}.
 * @param {String} props.context Describe what the context is.
 * @param {String} [props.linkId] Describe what linkId is. It's optional, isn't it?
 * @param {String} props.href Describe href.
 */
const AppDocumentationLink = ( props ) => {
	const { context, linkId, href, ...rest } = props;

	return (
		<TrackableLink
			eventName="gla_documentation_link_click"
			eventProps={ { context, link_id: linkId, href } }
			type="external"
			target="_blank"
			href={ href }
			{ ...rest }
		/>
	);
};

AppDocumentationLink.propTypes = {
	/**
	 * The screen or page of this DocumentationLink. This will be sent as part of track event properties.
	 */
	context: PropTypes.string.isRequired,

	/**
	 * An identifier for this DocumentationLink. This will be sent as part of track event properties.
	 */
	linkId: PropTypes.string.isRequired,
};

export default AppDocumentationLink;
