/* eslint-disable import/no-extraneous-dependencies */
/* eslint-disable no-unused-vars */
/**
 * External dependencies
 */
import { Browser, Page } from 'puppeteer';

declare global {
	const page: Page;
	const browser: Browser;
}

/**
 * Export as module.
 *
 * See:
 * https://www.typescriptlang.org/docs/handbook/declaration-files/templates/global-modifying-module-d-ts.html
 * https://medium.com/jspoint/typescript-type-declaration-files-4b29077c43
 */
export {};
