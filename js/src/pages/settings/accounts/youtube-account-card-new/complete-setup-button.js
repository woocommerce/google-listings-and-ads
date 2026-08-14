const CompleteSetupButton = ( { onClick, loading } ) => {
	return (
		<AppButton
			eventName="gla_link_youtube_account_button_click"
			eventProps={ { context: 'settings-youtube' } }
			onClick={ handleFinishSetup }
			disabled={ loading }
			loading={ loading }
			isSecondary
		>
			{ __( 'Complete setup', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default CompleteSetupButton;
