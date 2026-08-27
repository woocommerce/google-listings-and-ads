/**
 * External dependencies
 */
import { Flex, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './notice-detail.scss';

/**
 * Renders the colored notice used as the `Detail` content for the zero-accounts and
 * account-selection steps: body copy, optional extra content (e.g. a selector), and zero or
 * more actions.
 *
 * @param {Object} props Component props.
 * @param {'info'|'warning'|'error'} props.status Notice color.
 * @param {string|string[]} props.body Notice body copy. Pass an array for a multi-line message — each line renders as its own paragraph.
 * @param {JSX.Element} [props.extraContent] Extra content rendered below the body (e.g. a selector).
 * @param {JSX.Element[]} [props.actions] The step's action controls, each needing its own `key`.
 * @return {JSX.Element} The notice.
 */
export default function NoticeDetail( {
	status,
	body,
	extraContent,
	actions = [],
} ) {
	const lines = Array.isArray( body ) ? body : [ body ];

	return (
		<Notice
			status={ status }
			isDismissible={ false }
			className="gla-google-tag-manager-account-card__notice"
		>
			{ lines.map( ( line, index ) => (
				<p
					// Keys by the line's own text when it's a plain string (the common case);
					// falls back to a position-derived key for an interpolated element.
					key={ typeof line === 'string' ? line : `line-${ index }` }
					className="gla-google-tag-manager-account-card__notice-body"
				>
					{ line }
				</p>
			) ) }
			{ extraContent }
			{ actions.length > 0 && (
				<Flex
					justify="flex-start"
					className="gla-google-tag-manager-account-card__notice-actions"
					expanded={ false }
					wrap
				>
					{ actions }
				</Flex>
			) }
		</Notice>
	);
}
