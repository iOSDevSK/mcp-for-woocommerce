/**
 * WordPress dependencies
 */
import { useState, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RovingTabIndexProvider } from './roving-tab-index-context';

/**
 * Provider for adding roving tab index behaviors to tree grid structures.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/components/src/tree-grid/README.md
 */
import { jsx as _jsx } from "react/jsx-runtime";
export default function RovingTabIndex({
  children
}) {
  const [lastFocusedElement, setLastFocusedElement] = useState();

  // Use `useMemo` to avoid creation of a new object for the providerValue
  // on every render. Only create a new object when the `lastFocusedElement`
  // value changes.
  const providerValue = useMemo(() => ({
    lastFocusedElement,
    setLastFocusedElement
  }), [lastFocusedElement]);
  return /*#__PURE__*/_jsx(RovingTabIndexProvider, {
    value: providerValue,
    children: children
  });
}
//# sourceMappingURL=roving-tab-index.js.map