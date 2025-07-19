/**
 * External dependencies
 */
import { RgbStringColorPicker, RgbaStringColorPicker } from 'react-colorful';
import { colord } from 'colord';

/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { jsx as _jsx } from "react/jsx-runtime";
export const Picker = ({
  color,
  enableAlpha,
  onChange
}) => {
  const Component = enableAlpha ? RgbaStringColorPicker : RgbStringColorPicker;
  const rgbColor = useMemo(() => color.toRgbString(), [color]);
  return /*#__PURE__*/_jsx(Component, {
    color: rgbColor,
    onChange: nextColor => {
      onChange(colord(nextColor));
    }
    // Pointer capture fortifies drag gestures so that they continue to
    // work while dragging outside the component over objects like
    // iframes. If a newer version of react-colorful begins to employ
    // pointer capture this will be redundant and should be removed.
    ,
    onPointerDown: ({
      currentTarget,
      pointerId
    }) => {
      currentTarget.setPointerCapture(pointerId);
    },
    onPointerUp: ({
      currentTarget,
      pointerId
    }) => {
      currentTarget.releasePointerCapture(pointerId);
    }
  });
};
//# sourceMappingURL=picker.js.map