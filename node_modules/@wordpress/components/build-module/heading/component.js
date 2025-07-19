/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { contextConnect } from '../context';
import { View } from '../view';
import { useHeading } from './hook';
import { jsx as _jsx } from "react/jsx-runtime";
function UnconnectedHeading(props, forwardedRef) {
  const headerProps = useHeading(props);
  return /*#__PURE__*/_jsx(View, {
    ...headerProps,
    ref: forwardedRef
  });
}

/**
 * `Heading` renders headings and titles using the library's typography system.
 *
 * ```jsx
 * import { __experimentalHeading as Heading } from "@wordpress/components";
 *
 * function Example() {
 *   return <Heading>Code is Poetry</Heading>;
 * }
 * ```
 */
export const Heading = contextConnect(UnconnectedHeading, 'Heading');
export default Heading;
//# sourceMappingURL=component.js.map