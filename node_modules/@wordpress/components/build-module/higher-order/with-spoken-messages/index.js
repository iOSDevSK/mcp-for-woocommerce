/**
 * WordPress dependencies
 */
import { createHigherOrderComponent, useDebounce } from '@wordpress/compose';
import { speak } from '@wordpress/a11y';

/** @typedef {import('react').ComponentType} ComponentType */

/**
 * A Higher Order Component used to provide speak and debounced speak functions.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-a11y/#speak
 *
 * @param {ComponentType} Component The component to be wrapped.
 *
 * @return {ComponentType} The wrapped component.
 */
import { jsx as _jsx } from "react/jsx-runtime";
export default createHigherOrderComponent(Component => props => /*#__PURE__*/_jsx(Component, {
  ...props,
  speak: speak,
  debouncedSpeak: useDebounce(speak, 500)
}), 'withSpokenMessages');
//# sourceMappingURL=index.js.map