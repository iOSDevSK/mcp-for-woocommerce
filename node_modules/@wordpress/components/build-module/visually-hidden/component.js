/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { useContextSystem, contextConnect } from '../context';
import { visuallyHidden } from './styles';
import { View } from '../view';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedVisuallyHidden(props, forwardedRef) {
  const {
    style: styleProp,
    ...contextProps
  } = useContextSystem(props, 'VisuallyHidden');
  return /*#__PURE__*/_jsx(View, {
    ref: forwardedRef,
    ...contextProps,
    style: {
      ...visuallyHidden,
      ...(styleProp || {})
    }
  });
}

/**
 * `VisuallyHidden` is a component used to render text intended to be visually
 * hidden, but will show for alternate devices, for example a screen reader.
 *
 * ```jsx
 * import { VisuallyHidden } from `@wordpress/components`;
 *
 * function Example() {
 *   return (
 *     <VisuallyHidden>
 *       <label>Code is Poetry</label>
 *     </VisuallyHidden>
 *   );
 * }
 * ```
 */
export const VisuallyHidden = contextConnect(UnconnectedVisuallyHidden, 'VisuallyHidden');
export default VisuallyHidden;
//# sourceMappingURL=component.js.map