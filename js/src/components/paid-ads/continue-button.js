/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery, getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import {
	CAMPAIGN_STEP as STEP,
	CAMPAIGN_STEP_NUMBER_MAP as STEP_NUMBER_MAP,
} from '.~/constants';
import { recordStepContinueEvent } from '.~/utils/tracks';
function getCurrentStep() {
	const { step } = getQuery();
	if ( Object.values( STEP ).includes( step ) ) {
		return step;
	}
	return STEP.CAMPAIGN;
}

/**
 * An AppButton that renders Continue button on paid ad campaing.
 *
 * @param {Object} props Props
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {'create-ads'|'edit-ads'} props.trackingContext A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 * @return {import(".~/components/paid-ads/continue-button").default} The button.
 */
const ContinueButton = ( props ) => {
	const { formProps, trackingContext } = props;
	const eventName = 'gla_paid_campaign_step';
	const step = getCurrentStep();
	const setStep = ( nextStep ) => {
		const url = getNewPath( { ...getQuery(), step: nextStep } );
		getHistory().push( url );
	};
	const handleContinueClick = ( nextStep ) => {
		recordStepContinueEvent(
			eventName,
			STEP_NUMBER_MAP[ step ],
			STEP_NUMBER_MAP[ nextStep ],
			trackingContext
		);
		setStep( nextStep );
	};

	return (
		<AppButton
			isPrimary
			text={ __( 'Continue', 'google-listings-and-ads' ) }
			disabled={ ! formProps.isValidForm }
			onClick={ () => handleContinueClick( STEP.ASSET_GROUP ) }
		/>
	);
};

export default ContinueButton;
