/**
 * WordPress dependencies
 */
import { useRef, forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useRovingTabIndexContext } from './roving-tab-index-context';
import { jsx as _jsx } from "react/jsx-runtime";
export const RovingTabIndexItem = forwardRef(function UnforwardedRovingTabIndexItem({
  children,
  as: Component,
  ...props
}, forwardedRef) {
  const localRef = useRef();
  const ref = forwardedRef || localRef;
  // @ts-expect-error - We actually want to throw an error if this is undefined.
  const {
    lastFocusedElement,
    setLastFocusedElement
  } = useRovingTabIndexContext();
  let tabIndex;
  if (lastFocusedElement) {
    tabIndex = lastFocusedElement === (
    // TODO: The original implementation simply used `ref.current` here, assuming
    // that a forwarded ref would always be an object, which is not necessarily true.
    // This workaround maintains the original runtime behavior in a type-safe way,
    // but should be revisited.
    'current' in ref ? ref.current : undefined) ? 0 : -1;
  }
  const onFocus = event => setLastFocusedElement?.(event.target);
  const allProps = {
    ref,
    tabIndex,
    onFocus,
    ...props
  };
  if (typeof children === 'function') {
    return children(allProps);
  }
  if (!Component) {
    return null;
  }
  return /*#__PURE__*/_jsx(Component, {
    ...allProps,
    children: children
  });
});
export default RovingTabIndexItem;
//# sourceMappingURL=roving-tab-index-item.js.map