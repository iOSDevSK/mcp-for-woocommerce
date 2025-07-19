/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
export function OptionGroup({
  className,
  options,
  ...additionalProps
}) {
  const role = 'aria-label' in additionalProps || 'aria-labelledby' in additionalProps ? 'group' : undefined;
  return /*#__PURE__*/_jsx("div", {
    ...additionalProps,
    role: role,
    className: clsx('components-circular-option-picker__option-group', 'components-circular-option-picker__swatches', className),
    children: options
  });
}
//# sourceMappingURL=circular-option-picker-option-group.js.map