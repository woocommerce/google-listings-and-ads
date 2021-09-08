/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	useRef,
	createInterpolateElement,
} from '@wordpress/element';
import { Notice, Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCountdown from './useCountdown';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AppButton from '.~/components/app-button';
import VerificationCodeControl from './verification-code-control';
import { VERIFICATION_METHOD } from './constants';

// TODO: [full-contact-info] remove mock functions
const requestVerificationCode = ( country, phoneNumber, method ) => {
	// eslint-disable-next-line no-console
	console.log( 'requestVerificationCode', { country, phoneNumber, method } );
	return new Promise( ( resolve ) =>
		setTimeout( () => resolve( `${ method }-12345` ), 1000 )
	);
};

// TODO: [full-contact-info] remove mock functions
const verifyPhoneNumber = ( id, code, method ) => {
	// eslint-disable-next-line no-console
	console.log( 'verifyPhoneNumber', { id, code, method } );
	return new Promise( ( resolve, reject ) => {
		setTimeout( () => {
			if ( code === '000000' ) {
				resolve();
			} else {
				reject( 'Incorrect verification code. Please try again.' );
			}
		}, 2000 );
	} );
};

const appearanceDict = {
	[ VERIFICATION_METHOD.SMS ]: {
		toInstruction( phoneNumber ) {
			return createInterpolateElement(
				__(
					'A text message with the 6-digit verification code has been sent to <userPhoneNumber />.',
					'google-listings-and-ads'
				),
				{ userPhoneNumber: <strong>{ phoneNumber }</strong> }
			);
		},
		textResend: __( 'Resend code', 'google-listings-and-ads' ),
		// translators: %d: seconds to wait until the next verification code can be requested via SMS.
		textResendCooldown: __(
			'Resend code (in %ds)',
			'google-listings-and-ads'
		),
		textSwitch: __(
			'Or, receive a verification code through a phone call',
			'google-listings-and-ads'
		),
	},
	[ VERIFICATION_METHOD.PHONE_CALL ]: {
		toInstruction( phoneNumber ) {
			return createInterpolateElement(
				__(
					'You will receive a phone call at <userPhoneNumber /> with an automated message containing the 6-digit verification code.',
					'google-listings-and-ads'
				),
				{ userPhoneNumber: <strong>{ phoneNumber }</strong> }
			);
		},
		textResend: __( 'Call again', 'google-listings-and-ads' ),
		// translators: %d: seconds to wait until the next verification code can be requested via phone call.
		textResendCooldown: __(
			'Call again (in %ds)',
			'google-listings-and-ads'
		),
		textSwitch: __(
			'Or, receive a verification code through text message',
			'google-listings-and-ads'
		),
	},
};

/**
 * Renders inputs for verifying phone number in Card.Body UI.
 * Please note that this component will send a verification code request if the current method hasn't been requested yet after rendering.
 *
 * @param {Object} props React props.
 * @param {string} props.verificationMethod The initial verification method.
 * @param {string} props.country The country code. Example: 'US'.
 * @param {string} props.number The phone number string in E.164 format. Example: '+12133734253'.
 * @param {string} props.display The phone number string in international format. Example: '+1 213 373 4253'.
 * @param {Function} props.onPhoneNumberVerified Called when the phone number is verified.
 */
export default function VerifyPhoneNumberContent( {
	verificationMethod,
	country,
	number,
	display,
	onPhoneNumberVerified,
} ) {
	const [ method, setMethod ] = useState( verificationMethod );
	const { second, callCount, startCountdown } = useCountdown( method );
	const [ verification, setVerification ] = useState( null );
	const [ verifying, setVerifying ] = useState( false );
	const [ error, setError ] = useState( null );
	const verificationIdRef = useRef( {} );

	const isSMS = method === VERIFICATION_METHOD.SMS;

	const switchMethod = () => {
		if ( isSMS ) {
			setMethod( VERIFICATION_METHOD.PHONE_CALL );
		} else {
			setMethod( VERIFICATION_METHOD.SMS );
		}
	};

	const handleVerificationCodeRequest = useCallback( () => {
		setError( null );
		startCountdown( 60 );

		requestVerificationCode( country, number, method )
			.then( ( id ) => {
				verificationIdRef.current[ method ] = id;
			} )
			.catch( () => {
				startCountdown( 0 );
				// TODO: [full-contact-info] add error handling.
			} );
	}, [ country, number, method, startCountdown ] );

	const handleVerifyClick = () => {
		setError( null );
		setVerifying( true );

		const id = verificationIdRef.current[ method ];
		verifyPhoneNumber( id, verification.code, method )
			.then( () => {
				onPhoneNumberVerified();
			} )
			.catch( ( e ) => {
				// TODO: [full-contact-info] align to the real error data structure.
				setError( e );
				setVerifying( false );
			} );
	};

	// Trigger a verification code request if the current method hasn't been requested yet.
	useEffect( () => {
		if ( callCount === 0 ) {
			handleVerificationCodeRequest();
		}
	}, [ method, callCount, handleVerificationCodeRequest ] );

	// Render related.
	const {
		toInstruction,
		textResend,
		textResendCooldown,
		textSwitch,
	} = appearanceDict[ method ];

	return (
		<>
			<Section.Card.Body>
				{ error && (
					<Subsection>
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					</Subsection>
				) }
				<Subsection>
					<Subsection.Title>
						{ __(
							'Enter verification code',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
					{ toInstruction( display ) }
				</Subsection>
				<Subsection>
					<VerificationCodeControl
						resetNeedle={ method + callCount }
						onCodeChange={ setVerification }
					/>
				</Subsection>
				<Subsection>
					<Flex justify="normal" gap={ 4 }>
						<AppButton
							isSecondary
							disabled={ ! verification?.isFilled }
							loading={ verifying }
							text={ __(
								'Verify phone number',
								'google-listings-and-ads'
							) }
							onClick={ handleVerifyClick }
						/>
						<AppButton
							isSecondary
							disabled={ second > 0 }
							text={
								second
									? sprintf( textResendCooldown, second )
									: textResend
							}
							onClick={ handleVerificationCodeRequest }
						/>
					</Flex>
				</Subsection>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppButton
					isLink
					disabled={ verifying }
					text={ textSwitch }
					onClick={ switchMethod }
				/>
			</Section.Card.Footer>
		</>
	);
}
