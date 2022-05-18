/**
 * Internal dependencies
 */
import TrackableLink from '../trackable-link';

/**
 * When a documentation link is clicked.
 *
 * @event gla_documentation_link_click
 * @property {string} link_id link identifier
 * @property {string} context indicate which link is clicked
 * @property {string} href link's URL
 */

/**
 * Renders a trackable link to external documentation.
 *
 * Uses a `TrackableLink` that will open the link in new tab and
 *
 * @fires gla_documentation_link_click
 *
 * @param {Object} props Props to be forwarded to {@link TrackableLink}.
 * @param {string} props.context The screen or page of this AppDocumentationLink. This will be sent as part of track event properties.
 * @param {string} props.linkId An identifier for this DocumentationLink. This will be sent as part of track event properties.
 * @param {string} props.href `href` to be passed to `TrackableLink` component.
 * @param {string} [props.type="external"] Type of `TrackableLink`.
 * @param {string} [props.target="_blank"] Target of `TrackableLink`.
 */
const AppDocumentationLink = ( props ) => {
	const { context, linkId, href, ...rest } = props;

	// Put eventName after spreading `rest` to prevent eventName from being overridden.
	return (
		<TrackableLink
			eventProps={ { context, link_id: linkId, href } }
			type="external"
			target="_blank"
			href={ href }
			{ ...rest }
			eventName="gla_documentation_link_click"
		/>
	);
};

export default AppDocumentationLink;
