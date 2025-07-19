/**
 * External dependencies
 */
import clsx from 'clsx';
/**
 * WordPress dependencies
 */
import { forwardRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
function UnforwardedColorIndicator(props, forwardedRef) {
  const {
    className,
    colorValue,
    ...additionalProps
  } = props;
  return /*#__PURE__*/_jsx("span", {
    className: clsx('component-color-indicator', className),
    style: {
      background: colorValue
    },
    ref: forwardedRef,
    ...additionalProps
  });
}

/**
 * ColorIndicator is a React component that renders a specific color in a
 * circle. It's often used to summarize a collection of used colors in a child
 * component.
 *
 * ```jsx
 * import { ColorIndicator } from '@wordpress/components';
 *
 * const MyColorIndicator = () => <ColorIndicator colorValue="#0073aa" />;
 * ```
 */
export const ColorIndicator = forwardRef(UnforwardedColorIndicator);
export default ColorIndicator;
//# sourceMappingURL=index.js.map