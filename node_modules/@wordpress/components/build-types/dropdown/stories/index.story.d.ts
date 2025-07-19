/**
 * External dependencies
 */
import type { Meta, StoryObj } from '@storybook/react';
/**
 * Internal dependencies
 */
import Dropdown from '..';
declare const meta: Meta<typeof Dropdown>;
export default meta;
export declare const Default: StoryObj<typeof Dropdown>;
/**
 * To apply more padding to the dropdown content, use the provided `<DropdownContentWrapper>`
 * convenience wrapper. A `paddingSize` of `"medium"` is suitable for relatively larger dropdowns (default is `"small"`).
 */
export declare const WithMorePadding: StoryObj<typeof Dropdown>;
/**
 * The `<DropdownContentWrapper>` convenience wrapper can also be used to remove padding entirely,
 * with a `paddingSize` of `"none"`. This can also serve as a clean foundation to add arbitrary
 * paddings, for example when child components already have padding on their own.
 */
export declare const WithNoPadding: StoryObj<typeof Dropdown>;
export declare const WithMenuItems: StoryObj<typeof Dropdown>;
//# sourceMappingURL=index.story.d.ts.map