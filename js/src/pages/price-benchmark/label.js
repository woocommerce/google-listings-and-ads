/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { LABELS } from './constants';
import AppTooltip from '~/components/app-tooltip';

/**
 * Label component for displaying a title with an optional tooltip.
 *
 * @param {Object} props - The component props.
 * @param {string} props.labelKey - The key used to retrieve the label data from the LABELS object.
 * @param {boolean} [props.alignLeft=false] - Whether to align the label to the left.
 * @return {JSX.Element|null} The rendered label component or null if the labelKey is not found.
 */
const Label = ( { labelKey, alignLeft = false } ) => {
	if ( ! LABELS[ labelKey ] ) {
		return null;
	}

	const { title, tooltip } = LABELS[ labelKey ];

	return (
		<span
			className={ classnames( 'gla-price-benchmark__label', {
				'gla-price-benchmark__label--align-left': alignLeft,
			} ) }
		>
			{ ! tooltip && title }

			{ tooltip && <AppTooltip text={ tooltip }>{ title }</AppTooltip> }
		</span>
	);
};

export default Label;
