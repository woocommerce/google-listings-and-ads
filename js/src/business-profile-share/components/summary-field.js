/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { Flex, TextareaControl, Tooltip } from '@wordpress/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import { SUMMARY_MAX_LENGTH } from '../constants';
import {
	countSummaryCharacters,
	truncateSummary,
} from '../utils/buildSummaryFromPost';
import './summary-field.scss';

const SUMMARY_TIP = __(
	'Place critical promotional copy within the first 80–100 characters.',
	'google-listings-and-ads'
);

const InfoIcon = forwardRef( function InfoIcon( props, ref ) {
	return (
		<span
			{ ...props }
			ref={ ref }
			className="gla-business-profile-summary-field__info-icon"
			tabIndex={ 0 }
		>
			<GridiconInfoOutline
				role="img"
				aria-label={ SUMMARY_TIP }
				size={ 16 }
			/>
		</span>
	);
} );

/**
 * Renders the field holding the text sent to Google Business Profile: a "Summary" label with a
 * fixed tip, a plain-text area, and a live count against the character limit. Text past the limit
 * is cut.
 *
 * @param {Object} props Component props.
 * @param {string} props.value The current text.
 * @param {( value: string ) => void} props.onChange Called with the new text, already cut at the limit.
 * @param {boolean} [props.disabled] Whether the field is disabled.
 * @return {JSX.Element} The field.
 */
export default function SummaryField( { value, onChange, disabled = false } ) {
	const handleChange = ( nextValue ) => {
		onChange( truncateSummary( nextValue ) );
	};

	return (
		<div className="gla-business-profile-summary-field">
			<TextareaControl
				label={
					<Flex as="span" justify="flex-start" gap={ 1 }>
						{ __( 'Summary', 'google-listings-and-ads' ) }
						<Tooltip text={ SUMMARY_TIP }>
							<InfoIcon />
						</Tooltip>
					</Flex>
				}
				value={ value }
				onChange={ handleChange }
				help={ sprintf(
					/* translators: 1: number of characters used, 2: character limit. */
					__( '%1$d/%2$d characters', 'google-listings-and-ads' ),
					countSummaryCharacters( value ),
					SUMMARY_MAX_LENGTH
				) }
				rows={ 6 }
				disabled={ disabled }
				__nextHasNoMarginBottom
			/>
		</div>
	);
}
