/**
 * External dependencies
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
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
	const [ digits, setDigits ] = useState( initDigits );
	const [ focus, setFocus ] = useState( 0 );

	/**
	 * Moves focus to the input at given input
	 * if it exists.
	 *
	 * @param {number} targetIdx Index of the node to move the focus to.
	 */
	const maybeMoveFocus = ( targetIdx ) => {
		const node = inputsRef.current[ targetIdx ];
		if ( node ) {
			node.focus();
		}
	};

	const getEventData = ( e ) => {
		const { value, dataset } = e.target;
		const idx = Number( dataset.idx );

		return {
			idx,
			value: e.clipboardData?.getData( 'text/plain' ) ?? value,
		};
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

	const updateState = useCallback(
		( nextDigits ) => {
			setDigits( nextDigits );
			onCodeChange( toCallbackData( nextDigits ) );
		},
		[ onCodeChange ]
	);

	// Track the cursor's position.
	const handleBeforeInput = ( e ) => {
		cursorRef.current = e.target.selectionStart;
	};

	const handleUpdate = ( e ) => {
		e.preventDefault();

		const { nextDigits, nextFocusIdx } = e.clipboardData
			? handlePaste( e )
			: handleInput( e );

		setFocus( nextFocusIdx );

		if ( nextDigits !== digits ) {
			updateState( nextDigits );
		}
	};

	const handleInput = ( e ) => {
		const { value, idx } = getEventData( e );

		// Only keep the first entered char from the starting position of key cursor.
		const digit = value.substr( cursorRef.current, 1 ).replace( /\D/, '' );

		// If that char is not a digit, then clear the input to empty.
		if ( digit !== value ) {
			e.target.value = digit;
		}

		const nextDigits = [ ...digits ];
		nextDigits[ idx ] = digit;

		// always increase focus index by one except for digit deletions
		return { nextDigits, nextFocusIdx: digit ? idx + 1 : idx };
	};

	const handlePaste = ( e ) => {
		const { idx, value } = getEventData( e );

		// only allow n digits, from the current idx position until the end
		const newDigits = [
			...value.replace( /\D/g, '' ).substr( 0, DIGIT_LENGTH - idx ),
		];

		const nextDigits = [ ...digits ];
		newDigits.forEach(
			( digit, i ) => ( nextDigits[ i + idx ] = newDigits[ i ] )
		);

		// set next focus index to the last inserted digit
		return {
			nextDigits,
			nextFocusIdx: Math.min( newDigits.length + idx, DIGIT_LENGTH - 1 ),
		};
	};

	/**
	 * Set the focus to the first input if the control's value is (back) at the initial state.
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
	 * So here we await the `digits` is reset back to `initDigits` by above useEffect and sync to internal value,
	 * then move the focus calling after the synchronization tick finished.
	 *
	 * Note the above also impacts in the state updates for the focused element...
	 * That's why we need setFocus state in order to sync these internal values.
	 *
	 * @see https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/input-control/input-field.js#L73-L90
	 */
	useEffect( () => {
		updateState( initDigits );
		setFocus( 0 );
	}, [ resetNeedle, updateState ] );

	useEffect( () => {
		maybeMoveFocus( focus );
	}, [ focus ] );

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
						onInput={ handleUpdate }
						onPaste={ handleUpdate }
						autoComplete="off"
					/>
				);
			} ) }
		</Flex>
	);
}
