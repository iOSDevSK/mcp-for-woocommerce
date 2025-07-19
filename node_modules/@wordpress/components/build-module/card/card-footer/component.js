/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../../context';
import { Flex } from '../../flex';
import { useCardFooter } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedCardFooter(props, forwardedRef) {
  const footerProps = useCardFooter(props);
  return /*#__PURE__*/_jsx(Flex, {
    ...footerProps,
    ref: forwardedRef
  });
}

/**
 * `CardFooter` renders an optional footer within a `Card`.
 *
 * ```jsx
 * import { Card, CardBody, CardFooter } from `@wordpress/components`;
 *
 * <Card>
 * 	<CardBody>...</CardBody>
 * 	<CardFooter>...</CardFooter>
 * </Card>
 * ```
 */
export const CardFooter = contextConnect(UnconnectedCardFooter, 'CardFooter');
export default CardFooter;
//# sourceMappingURL=component.js.map