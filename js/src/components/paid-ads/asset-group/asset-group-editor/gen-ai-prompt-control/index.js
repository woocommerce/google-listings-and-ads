/**
 * External dependencies
 */
import { TextareaControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

export const MAX_PROMPT_LENGTH = 1500;

/**
 * A prompt textarea with a live character counter, shared by the GenAI
 * "edit with prompt" and "generate with prompt" flows.
 *
 * @param {Object} props React props.
 * @param {string} props.value The current prompt value.
 * @param {Function} props.onChange Called with the new value on change.
 * @param {string} [props.placeholder] Placeholder text shown in the field.
 * @param {boolean} [props.disabled] Whether the field is disabled.
 * @param {number} [props.rows=4] Number of visible text rows.
 * @param {string} [props.className] Additional class name(s) for the control.
 */
export default function GenAIPromptControl( {
	value,
	onChange,
	placeholder,
	disabled,
	rows = 4,
	className,
} ) {
	const isOverLimit = value.length > MAX_PROMPT_LENGTH;

	return (
		<TextareaControl
			label={ __( 'Prompt', 'google-listings-and-ads' ) }
			placeholder={ placeholder }
			hideLabelFromVision
			help={ sprintf(
				// translators: 1: number of characters typed. 2: the maximum number of allowed characters.
				__( '%1$d/%2$d characters', 'google-listings-and-ads' ),
				value.length,
				MAX_PROMPT_LENGTH
			) }
			value={ value }
			onChange={ onChange }
			disabled={ disabled }
			rows={ rows }
			className={ classnames( 'gla-gen-ai-prompt-control', className, {
				'gla-gen-ai-prompt-control--error': isOverLimit,
			} ) }
		/>
	);
}
