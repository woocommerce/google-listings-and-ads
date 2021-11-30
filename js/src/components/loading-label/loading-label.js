/**
 * External dependencies
 */
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './loading-label.scss';

/**
 * Renders a text with a leading <Spinner>.
 *
 * @param {Object} props React props.
 * @param {string} props.text Lodding text.
 */
export default function LoadingLabel( { text } ) {
	return (
		<div className="gla-loading-label">
			<Spinner />
			{ text }
		</div>
	);
}
