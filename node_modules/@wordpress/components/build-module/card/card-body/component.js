/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { Scrollable } from '../../scrollable';
import { View } from '../../view';
import { useCardBody } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedCardBody(props, forwardedRef) {
  const {
    isScrollable,
    ...otherProps
  } = useCardBody(props);
  if (isScrollable) {
    return /*#__PURE__*/_jsx(Scrollable, {
      ...otherProps,
      ref: forwardedRef
    });
  }
  return /*#__PURE__*/_jsx(View, {
    ...otherProps,
    ref: forwardedRef
  });
}

/**
 * `CardBody` renders an optional content area for a `Card`.
 * Multiple `CardBody` components can be used within `Card` if needed.
 *
 * ```jsx
 * import { Card, CardBody } from `@wordpress/components`;
 *
 * <Card>
 * 	<CardBody>
 * 		...
 * 	</CardBody>
 * </Card>
 * ```
 */
export const CardBody = contextConnect(UnconnectedCardBody, 'CardBody');
export default CardBody;
//# sourceMappingURL=component.js.map