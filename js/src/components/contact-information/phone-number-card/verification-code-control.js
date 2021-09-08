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

	const maybeMoveFocus = ( targetIdx ) => {
		const node = inputsRef.current[ targetIdx ];
		if ( node ) {
			node.focus();
		}
	};

	const handleKeyDown = ( e ) => {
		const { dataset, selectionStart, value } = e.target;
		const idx = Number( dataset.idx );

		switch ( e.keyCode ) {
			case KEY_CODE_LEFT:
			case KEY_CODE_BACKSPACE:
				if ( selectionStart === 0 ) {
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

	const handleBeforeInput = ( e ) => {
		cursorRef.current = e.target.selectionStart;
	};

	const handleInput = ( e ) => {
		const { value, dataset } = e.target;
		const idx = Number( dataset.idx );

		// Only keep the first entered char from the starting position of key cursor.
		// If that char is not a digit, then clear the input to empty.
		const digit = value.substr( cursorRef.current, 1 ).replace( /\D/, '' );
		if ( digit !== value ) {
			e.target.value = digit;
		}

		if ( digit ) {
			maybeMoveFocus( idx + 1 );
		}

		if ( digit !== digits[ idx ] ) {
			const nextDigits = [ ...digits ];
			nextDigits[ idx ] = digit;
			setDigits( nextDigits );

			onCodeChange( toCallbackData( nextDigits ) );
		}
	};

	useEffect( () => {
		maybeMoveFocus( 0 );
	}, [] );

	useEffect( () => {
		inputsRef.current.forEach( ( el ) => ( el.value = '' ) );
		maybeMoveFocus( 0 );

		setDigits( initDigits );
		onCodeChangeRef.current( toCallbackData( initDigits ) );
	}, [ resetNeedle ] );

	return (
		<Flex
			className="gla-verification-code-control"
			justify="normal"
			gap={ 2 }
		>
			{ digits.map( ( _, idx ) => {
				return (
					<AppInputControl
						key={ idx }
						ref={ ( el ) => ( inputsRef.current[ idx ] = el ) }
						data-idx={ idx }
						onKeyDown={ handleKeyDown }
						onBeforeInput={ handleBeforeInput }
						onInput={ handleInput }
					/>
				);
			} ) }
		</Flex>
	);
}
