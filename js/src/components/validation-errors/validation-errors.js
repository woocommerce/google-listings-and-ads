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
export default function ValidationErrors( { messages } ) {
	let messagesList = messages;

	if ( ! messages?.length ) {
		return null;
	}

	if ( ! Array.isArray( messages ) ) {
		messagesList = [ messages ];
	}

	return (
		<ul className="gla-validation-errors">
			{ messagesList.map( ( message ) => (
				<li key={ message }>{ message }</li>
			) ) }
		</ul>
	);
}
