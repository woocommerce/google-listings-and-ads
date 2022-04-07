/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { CardDivider } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import VerifyPhoneNumberContent from './verify-phone-number-content';
import EditPhoneNumberContent from './edit-phone-number-content';
import './phone-number-card.scss';

const noop = () => {};

const basePhoneNumberCardProps = {
	className: 'gla-phone-number-card',
	appearance: APPEARANCE.PHONE,
};

/**
 * @typedef { import(".~/hooks/useGoogleMCPhoneNumber").PhoneNumber } PhoneNumber
 */

function EditPhoneNumberCard( { phoneNumber, onPhoneNumberVerified } ) {
	const { loaded, data } = phoneNumber;
	const [ verifying, setVerifying ] = useState( false );
	const [ unverifiedPhoneNumber, setUnverifiedPhoneNumber ] = useState(
		null
	);

	let cardContent = <AppSpinner />;

	if ( loaded ) {
		cardContent = unverifiedPhoneNumber ? (
			<VerifyPhoneNumberContent
				{ ...unverifiedPhoneNumber }
				onVerificationStateChange={ ( isVerifying, isVerified ) => {
					setVerifying( isVerifying );

					if ( isVerified ) {
						onPhoneNumberVerified();
					}
				} }
			/>
		) : (
			<EditPhoneNumberContent
				initCountry={ data.country }
				initNationalNumber={ data.nationalNumber }
				onSendVerificationCodeClick={ setUnverifiedPhoneNumber }
			/>
		);
	}

	const description = unverifiedPhoneNumber
		? unverifiedPhoneNumber.display
		: __(
				'Please enter a phone number to be used for verification.',
				'google-listings-and-ads'
		  );

	const indicator = unverifiedPhoneNumber ? (
		<AppButton
			isSecondary
			text={ __( 'Edit', 'google-listings-and-ads' ) }
			disabled={ verifying }
			onClick={ () => setUnverifiedPhoneNumber( null ) }
		/>
	) : null;

	return (
		<AccountCard
			{ ...basePhoneNumberCardProps }
			description={ description }
			indicator={ indicator }
		>
			<CardDivider />
			{ cardContent }
		</AccountCard>
	);
}

/**
 * Clicking on the Merchant Center phone number edit button.
 *
 * @event gla_mc_phone_number_edit_button_click
 * @property {string} view which view the edit button is in. Possible values: `setup-mc`, `settings`.
 */

/**
 * Renders phone number data in Card UI and is able to edit.
 *
 * @param {Object} props React props.
 * @param {string} props.view The view the card is in.
 * @param {PhoneNumber} props.phoneNumber Phone number data.
 * @param {boolean|null} [props.initEditing=null] Specify the inital UI state.
 *     `true`: initialize with the editing UI.
 *     `false`: initialize with the viewing UI.
 *     `null`: determine the initial UI state according to the `data.isValid` after the `phoneNumber` loaded.
 * @param {Function} [props.onEditClick] Called when clicking on "Edit" button.
 *     If this callback is omitted, it will enter edit mode when clicking on "Edit" button.
 * @param {Function} [props.onPhoneNumberVerified] Called when the phone number is verified in edit mode.
 *
 * @fires gla_mc_phone_number_edit_button_click
 */
export default function PhoneNumberCard( {
	view,
	phoneNumber,
	initEditing = null,
	onEditClick,
	onPhoneNumberVerified = noop,
} ) {
	const { loaded, data } = phoneNumber;
	const [ isEditing, setEditing ] = useState( initEditing );

	// Handle the initial UI state of `initEditing = null`.
	// The `isEditing` state is on hold. Determine it after the `phoneNumber` loaded.
	useEffect( () => {
		if ( loaded && isEditing === null ) {
			setEditing( ! data.isValid );
		}
	}, [ loaded, data.isValid, isEditing ] );

	// Return a simple loading AccountCard since the initial edit state is unknown before loaded.
	if ( isEditing === null ) {
		return (
			<AccountCard
				{ ...basePhoneNumberCardProps }
				indicator={ <Spinner /> }
			/>
		);
	}

	if ( isEditing ) {
		const handlePhoneNumberVerified = () => {
			setEditing( false );
			onPhoneNumberVerified();
		};
		return (
			<EditPhoneNumberCard
				phoneNumber={ phoneNumber }
				onPhoneNumberVerified={ handlePhoneNumberVerified }
			/>
		);
	}

	let description = null;
	let indicator = <Spinner />;

	if ( loaded ) {
		description = data.display;
		indicator = (
			<AppButton
				isSecondary
				eventName="gla_mc_phone_number_edit_button_click"
				eventProps={ {
					view,
				} }
				onClick={ () => {
					if ( onEditClick ) {
						onEditClick();
					} else {
						setEditing( true );
					}
				} }
			>
				{ __( 'Edit', 'google-listings-and-ads' ) }
			</AppButton>
		);
	}

	return (
		<AccountCard
			{ ...basePhoneNumberCardProps }
			description={ description }
			indicator={ indicator }
		/>
	);
}
