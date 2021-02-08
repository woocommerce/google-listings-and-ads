const isPreLaunchChecklistComplete = ( values ) => {
	return (
		values.website_live &&
		values.checkout_process_secure &&
		values.payment_methods_visible &&
		values.refund_tos_visible &&
		values.contact_info_visible
	);
};

export default isPreLaunchChecklistComplete;
