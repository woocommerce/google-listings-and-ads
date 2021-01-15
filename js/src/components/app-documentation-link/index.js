/**
 * Internal dependencies
 */
import TrackableLink from '../trackable-link';

/**
 * Renders a trackable link to external documentation.
 *
 * Uses a `TrackableLink` that will open the link in new tab and
 * call `recordEvent` with `gla_documentation_link_click` event name.
 *
 * - `eventName="gla_documentation_link_click"`
 * - `eventProps={ { context, link_id: linkId, href } }`
 * - `type="external"` by default.
 * - `target="_blank"` by default.
 *
 * @param {Object} props Props to be forwarded to {@link TrackableLink}.
 * @param {string} props.context The screen or page of this AppDocumentationLink. This will be sent as part of track event properties.
 * @param {string} props.linkId An identifier for this DocumentationLink. This will be sent as part of track event properties.
 * @param {string} props.href `href` to be passed to `TrackableLink` component.
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

export default AppDocumentationLink;
