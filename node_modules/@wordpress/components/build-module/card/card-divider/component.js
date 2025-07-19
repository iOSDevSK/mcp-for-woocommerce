/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { Divider } from '../../divider';
import { useCardDivider } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedCardDivider(props, forwardedRef) {
  const dividerProps = useCardDivider(props);
  return /*#__PURE__*/_jsx(Divider, {
    ...dividerProps,
    ref: forwardedRef
  });
}

/**
 * `CardDivider` renders an optional divider within a `Card`.
 * It is typically used to divide multiple `CardBody` components from each other.
 *
 * ```jsx
 * import { Card, CardBody, CardDivider } from `@wordpress/components`;
 *
 * <Card>
 *  <CardBody>...</CardBody>
 *  <CardDivider />
 *  <CardBody>...</CardBody>
 * </Card>
 * ```
 */
export const CardDivider = contextConnect(UnconnectedCardDivider, 'CardDivider');
export default CardDivider;
//# sourceMappingURL=component.js.map