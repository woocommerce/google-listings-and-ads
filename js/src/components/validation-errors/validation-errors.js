/**
 * Internal dependencies
 */
import './validation-errors.scss';

/**
 * Renders form validation error messages.
 *
 * @param {Object} props React props
 * @param {string|string[]} [props.messages] Validation error message(s).
 */
export default function ValidationErrors( { messages: rawMessages } ) {
	let messages = rawMessages;

	if ( ! rawMessages?.length ) {
		return null;
	}

	if ( ! Array.isArray( rawMessages ) ) {
		messages = [ rawMessages ];
	}

	return (
		<ul className="gla-validation-errors">
			{ messages.map( ( message ) => (
				<li key={ message }>{ message }</li>
			) ) }
		</ul>
	);
}
