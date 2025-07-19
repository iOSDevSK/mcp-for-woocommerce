/**
 * Internal dependencies
 */
import { VisuallyHidden } from '../visually-hidden';
import { Label as BaseLabel, LabelWrapper } from './styles/input-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
export default function Label({
  children,
  hideLabelFromVision,
  htmlFor,
  ...props
}) {
  if (!children) {
    return null;
  }
  if (hideLabelFromVision) {
    return /*#__PURE__*/_jsx(VisuallyHidden, {
      as: "label",
      htmlFor: htmlFor,
      children: children
    });
  }
  return /*#__PURE__*/_jsx(LabelWrapper, {
    children: /*#__PURE__*/_jsx(BaseLabel, {
      htmlFor: htmlFor,
      ...props,
      children: children
    })
  });
}
//# sourceMappingURL=label.js.map