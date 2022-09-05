export const glaData = window.glaData;

export const FREE_LISTINGS_PROGRAM_ID = 0;

// Products report related
export const REPORT_SOURCE_PARAM = 'report-source';
export const REPORT_SOURCE_PAID = 'paid';
export const REPORT_SOURCE_FREE = 'free';
export const REPORT_SOURCE_DEFAULT = REPORT_SOURCE_PAID;

// Programs report related
export const REPORT_PROGRAM_PARAM = 'programs';

export const MINUTE_IN_SECONDS = 60;
export const HOUR_IN_SECONDS = 60 * MINUTE_IN_SECONDS;
export const DAY_IN_SECONDS = 24 * HOUR_IN_SECONDS;
export const MONTH_IN_SECONDS = 30 * DAY_IN_SECONDS;

export const GUIDE_NAMES = {
	SUBMISSION_SUCCESS: 'submission-success',
	CAMPAIGN_CREATION_SUCCESS: 'campaign-creation-success',
};

export const LOCAL_STORAGE_KEYS = {
	CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN:
		'gla-can-onboarding-setup-ces-prompt-open',
	IS_BEFORE_MIGRATION_NOTICE_DISMISSED:
		'gla-is-before-migration-notice-dismissed',
	IS_AFTER_MIGRATION_NOTICE_DISMISSED:
		'gla-is-after-migration-notice-dismissed',
	IS_REPORTS_MIGRATION_NOTICE_DISMISSED:
		'gla-is-reports-migration-notice-dismissed',
};

export const API_RESPONSE_CODES = {
	WPCOM_DISCONNECTED: 'JETPACK_DISCONNECTED',
	GOOGLE_DISCONNECTED: 'GOOGLE_DISCONNECTED',
};

export const SHIPPING_RATE_METHOD = {
	FLAT_RATE: 'flat_rate',
};

export const CAMPAIGN_ADS_MIGRATION = {
	MAXIMUM_MONTHS_TO_DISPLAY_NOTICE: 1,
};

// MC Issues Related
export const ISSUE_TYPE_PRODUCT = 'product';
export const ISSUE_TYPE_ACCOUNT = 'account';
export const REQUEST_REVIEW = 'request-review';
export const ISSUE_TABLE_PER_PAGE = 5;

// Account status related
export const GOOGLE_ADS_ACCOUNT_STATUS = {
	CONNECTED: 'connected',
	DISCONNECTED: 'disconnected',
	INCOMPLETE: 'incomplete',
};
