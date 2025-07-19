/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * WordPress dependencies
 */
import { Rect, SVG } from '@wordpress/primitives';

/**
 * Internal dependencies
 */
import { ALIGNMENTS, getAlignmentIndex } from './utils';
import { jsx as _jsx } from "react/jsx-runtime";
const BASE_SIZE = 24;
const GRID_CELL_SIZE = 7;
const GRID_PADDING = (BASE_SIZE - 3 * GRID_CELL_SIZE) / 2;
const DOT_SIZE = 2;
const DOT_SIZE_SELECTED = 4;
function AlignmentMatrixControlIcon({
  className,
  disablePointerEvents = true,
  size,
  width,
  height,
  style = {},
  value = 'center',
  ...props
}) {
  var _ref, _ref2;
  return /*#__PURE__*/_jsx(SVG, {
    xmlns: "http://www.w3.org/2000/svg",
    viewBox: `0 0 ${BASE_SIZE} ${BASE_SIZE}`,
    width: (_ref = size !== null && size !== void 0 ? size : width) !== null && _ref !== void 0 ? _ref : BASE_SIZE,
    height: (_ref2 = size !== null && size !== void 0 ? size : height) !== null && _ref2 !== void 0 ? _ref2 : BASE_SIZE,
    role: "presentation",
    className: clsx('component-alignment-matrix-control-icon', className),
    style: {
      pointerEvents: disablePointerEvents ? 'none' : undefined,
      ...style
    },
    ...props,
    children: ALIGNMENTS.map((align, index) => {
      const dotSize = getAlignmentIndex(value) === index ? DOT_SIZE_SELECTED : DOT_SIZE;
      return /*#__PURE__*/_jsx(Rect, {
        x: GRID_PADDING + index % 3 * GRID_CELL_SIZE + (GRID_CELL_SIZE - dotSize) / 2,
        y: GRID_PADDING + Math.floor(index / 3) * GRID_CELL_SIZE + (GRID_CELL_SIZE - dotSize) / 2,
        width: dotSize,
        height: dotSize,
        fill: "currentColor"
      }, align);
    })
  });
}
export default AlignmentMatrixControlIcon;
//# sourceMappingURL=icon.js.map