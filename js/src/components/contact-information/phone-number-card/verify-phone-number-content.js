/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useCallback,
	createInterpolateElement,
} from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useCountdown from './useCountdown';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AppButton from '.~/components/app-button';
import VerificationCodeControl from './verification-code-control';
import {
	VERIFICATION_METHOD_SMS,
	VERIFICATION_METHOD_PHONE_CALL,
} from './constants';

const appearanceDict = {
	[ VERIFICATION_METHOD_SMS ]: {
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
	[ VERIFICATION_METHOD_PHONE_CALL ]: {
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
 * @param {string} props.display The phone number string in international format. Example: '+1 213 373 4253'.
 */
export default function VerifyPhoneNumberContent( {
	verificationMethod,
	display,
} ) {
	const [ method, setMethod ] = useState( verificationMethod );
	const [ second, callCount, startCountdown ] = useCountdown( method );
	const [ verification, setVerification ] = useState( null );

	const isSMS = method === VERIFICATION_METHOD_SMS;

	const switchMethod = () => {
		if ( isSMS ) {
			setMethod( VERIFICATION_METHOD_PHONE_CALL );
		} else {
			setMethod( VERIFICATION_METHOD_SMS );
		}
	};

	const handleVerificationCodeRequest = useCallback( () => {
		startCountdown( 60 );
	}, [ startCountdown ] );

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
							text={ __(
								'Verify phone number',
								'google-listings-and-ads'
							) }
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
					text={ textSwitch }
					onClick={ switchMethod }
				/>
			</Section.Card.Footer>
		</>
	);
}
