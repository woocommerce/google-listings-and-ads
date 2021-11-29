/**
 * External dependencies
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppInputControl from '.~/components/app-input-control';
import './verification-code-control.scss';

const KEY_CODE_LEFT = 37;
const KEY_CODE_RIGHT = 39;
const KEY_CODE_BACKSPACE = 8;

const DIGIT_LENGTH = 6;
const initDigits = Array( DIGIT_LENGTH ).fill( '' );

const toCallbackData = ( digits ) => {
	const code = digits.join( '' );
	const isFilled = code.length === DIGIT_LENGTH;
	return { code, isFilled };
};

/**
 * @callback onCodeChange
 * @param {Object} verification Data payload.
 * @param {string} verification.code The current entered verification code.
 * @param {boolean} verification.isFilled Whether all digits of validation code are filled.
 */

/**
 * Renders a row of input elements for entering six-digit verification code.
 *
 * @param {Object} props React props.
 * @param {onCodeChange} props.onCodeChange Called when the verification code are changed.
 * @param {string} [props.resetNeedle=''] When the passed value changes, it will trigger internal state resetting for this component.
 */
export default function VerificationCodeControl( {
	onCodeChange,
	resetNeedle = '',
} ) {
	const inputsRef = useRef( [] );
	const cursorRef = useRef( 0 );
	const onCodeChangeRef = useRef();
	const [ digits, setDigits ] = useState( initDigits );

	onCodeChangeRef.current = onCodeChange;

	/**
	 * Moves focus to the closest <input> node of the given `targetIdx`.
	 *
	 * Since the <InputControl> has an internal state management that always controls the actual `value` prop of the <input>,
	 * the <InputControl> is forced the <input> to be a controlled input.
	 * When using it, it's always necessary to specify `value` prop from the below <AppInputControl>
	 * to avoid the warning - A component is changing an uncontrolled input to be controlled.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/input-control/input-field.js#L47-L68
	 * @see https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/input-control/input-field.js#L115-L118
	 *
	 * But after specifying the `value` prop,
	 * the synchronization of external and internal `value` state will depend on whether the input is focused.
	 * It'd sync external to internal only if the input is not focused.
	 *
	 * When setting focus to the first and last inputs along with updating its showing value,
	 * the conflict between external and internal `value` will happen.
	 * So here We use the determination of whether the `targetIdx` is an out-of-range value
	 * to decide whether to delay the focus() calling.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/input-control/input-field.js#L73-L90
	 *
	 * @param {number} targetIdx
	 *   Index of the node to move the focus to.
	 *   When calling with an out-of-range value, it will be converted to the closest valid index and delays triggering of focus().
	 */
	const maybeMoveFocus = ( targetIdx ) => {
		const validIdx = Math.max( Math.min( targetIdx, DIGIT_LENGTH - 1 ), 0 );
		const node = inputsRef.current[ validIdx ];

		if ( node === node.ownerDocument.activeElement ) {
			return;
		}

		if ( targetIdx === validIdx ) {
			node.focus();
		} else {
			// Move the focus calling after the synchronization tick finished.
			setTimeout( () => node.focus() );
		}
	};

	const handleKeyDown = ( e ) => {
		const { dataset, selectionStart, selectionEnd, value } = e.target;
		const idx = Number( dataset.idx );

		switch ( e.keyCode ) {
			case KEY_CODE_LEFT:
			case KEY_CODE_BACKSPACE:
				if ( selectionStart === 0 && selectionEnd === 0 ) {
					maybeMoveFocus( idx - 1 );
				}
				break;

			case KEY_CODE_RIGHT:
				if ( selectionStart === 1 || ! value ) {
					maybeMoveFocus( idx + 1 );
				}
				break;
		}
	};

	// Track the cursor's position.
	const handleBeforeInput = ( e ) => {
		cursorRef.current = e.target.selectionStart;
	};

	const handleInput = ( e ) => {
		const { value, dataset } = e.target;
		const idx = Number( dataset.idx );
		const nextDigits = [ ...digits ];

		// Only keep the entered/pasted digits from the starting position of input cursor.
		const enteredDigits = value
			.substring( cursorRef.current, e.target.selectionStart )
			.replace( /\D/g, '' )
			.slice( 0, DIGIT_LENGTH - idx )
			.split( '' );

		if ( enteredDigits.length === 0 ) {
			// If no number is entered, add an empty string for subsequent processes to clear the current input to empty.
			enteredDigits.push( '' );
		} else {
			maybeMoveFocus( idx + enteredDigits.length );
		}

		e.target.value = enteredDigits[ 0 ];
		nextDigits.splice( idx, enteredDigits.length, ...enteredDigits );

		if ( nextDigits.join() !== digits.join() ) {
			setDigits( nextDigits );

			onCodeChange( toCallbackData( nextDigits ) );
		}
	};

	// Reset the inputs' values and focus to the first input.
	useEffect( () => {
		inputsRef.current.forEach( ( el ) => ( el.value = '' ) );

		setDigits( initDigits );
		onCodeChangeRef.current( toCallbackData( initDigits ) );
		maybeMoveFocus( -1 );
	}, [ resetNeedle ] );

	return (
		<Flex
			className="gla-verification-code-control"
			justify="normal"
			gap={ 2 }
		>
			{ digits.map( ( value, idx ) => {
				return (
					<AppInputControl
						key={ idx }
						ref={ ( el ) => ( inputsRef.current[ idx ] = el ) }
						data-idx={ idx }
						value={ value }
						onKeyDown={ handleKeyDown }
						onBeforeInput={ handleBeforeInput }
						onInput={ handleInput }
						autoComplete="off"
					/>
				);
			} ) }
		</Flex>
	);
}
