/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { Flex } from '../../flex';
import { useCardHeader } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedCardHeader(props, forwardedRef) {
  const headerProps = useCardHeader(props);
  return /*#__PURE__*/_jsx(Flex, {
    ...headerProps,
    ref: forwardedRef
  });
}

/**
 * `CardHeader` renders an optional header within a `Card`.
 *
 * ```jsx
 * import { Card, CardBody, CardHeader } from `@wordpress/components`;
 *
 * <Card>
 * 	<CardHeader>...</CardHeader>
 * 	<CardBody>...</CardBody>
 * </Card>
 * ```
 */
export const CardHeader = contextConnect(UnconnectedCardHeader, 'CardHeader');
export default CardHeader;
//# sourceMappingURL=component.js.map