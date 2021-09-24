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
import { useAppDispatch } from '.~/data';
import useIsMounted from '.~/hooks/useIsMounted';
import useCountdown from './useCountdown';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AppButton from '.~/components/app-button';
import VerificationCodeControl from './verification-code-control';
import { VERIFICATION_METHOD } from './constants';

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
 * @param {(isVerifying: boolean, isVerified: boolean) => void} props.onVerificationStateChange Called when the verification state is changed.
 */
export default function VerifyPhoneNumberContent( {
	verificationMethod,
	country,
	number,
	display,
	onVerificationStateChange,
} ) {
	const isMounted = useIsMounted();
	const [ method, setMethod ] = useState( verificationMethod );
	const { second, callCount, startCountdown } = useCountdown( method );
	const [ verification, setVerification ] = useState( null );
	const [ verifying, setVerifying ] = useState( false );
	const [ error, setError ] = useState( null );
	const verificationIdRef = useRef( {} );
	const {
		requestPhoneVerificationCode,
		verifyPhoneNumber,
	} = useAppDispatch();

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
		verificationIdRef.current[ method ] = null;

		requestPhoneVerificationCode( country, number, method )
			.then( ( { verificationId } ) => {
				verificationIdRef.current[ method ] = verificationId;
			} )
			.catch( ( e ) => {
				if ( isMounted() ) {
					setError( e );
					startCountdown( 0 );
				}
			} );
	}, [
		country,
		number,
		method,
		startCountdown,
		requestPhoneVerificationCode,
		isMounted,
	] );

	const handleVerifyClick = () => {
		setError( null );
		setVerifying( true );
		onVerificationStateChange( true, false );

		const id = verificationIdRef.current[ method ];
		verifyPhoneNumber( id, verification.code, method )
			.then( () => {
				onVerificationStateChange( false, true );
			} )
			.catch( ( e ) => {
				if ( isMounted() ) {
					setError( e );
					setVerifying( false );
					onVerificationStateChange( false, false );
				}
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

	const verificationId = verificationIdRef.current[ method ];
	const disableVerify = ! ( verification?.isFilled && verificationId );

	return (
		<>
			<Section.Card.Body>
				{ error && (
					<Subsection>
						<Notice status="error" isDismissible={ false }>
							{ error.display }
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
							disabled={ disableVerify }
							loading={ verifying }
							text={ __(
								'Verify phone number',
								'google-listings-and-ads'
							) }
							onClick={ handleVerifyClick }
						/>
						<AppButton
							isSecondary
							disabled={ second > 0 || verifying }
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
