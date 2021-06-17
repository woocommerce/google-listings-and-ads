/* eslint-disable import/no-unresolved */
/* eslint-disable no-unused-vars */
/**
 * External dependencies
 */
import { Browser, Page } from 'puppeteer';

declare global {
	const page: Page;
	const browser: Browser;
}
