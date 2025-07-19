/**
 * WordPress dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { __experimentalUseDragging as useDragging } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { CircleRoot, CircleIndicatorWrapper, CircleIndicator } from './styles/angle-picker-control-styles';
import { jsx as _jsx } from "react/jsx-runtime";
function AngleCircle({
  value,
  onChange,
  ...props
}) {
  const angleCircleRef = useRef(null);
  const angleCircleCenterRef = useRef();
  const previousCursorValueRef = useRef();
  const setAngleCircleCenter = () => {
    if (angleCircleRef.current === null) {
      return;
    }
    const rect = angleCircleRef.current.getBoundingClientRect();
    angleCircleCenterRef.current = {
      x: rect.x + rect.width / 2,
      y: rect.y + rect.height / 2
    };
  };
  const changeAngleToPosition = event => {
    if (event === undefined) {
      return;
    }

    // Prevent (drag) mouse events from selecting and accidentally
    // triggering actions from other elements.
    event.preventDefault();
    // Input control needs to lose focus and by preventDefault above, it doesn't.
    event.target?.focus();
    if (angleCircleCenterRef.current !== undefined && onChange !== undefined) {
      const {
        x: centerX,
        y: centerY
      } = angleCircleCenterRef.current;
      onChange(getAngle(centerX, centerY, event.clientX, event.clientY));
    }
  };
  const {
    startDrag,
    isDragging
  } = useDragging({
    onDragStart: event => {
      setAngleCircleCenter();
      changeAngleToPosition(event);
    },
    onDragMove: changeAngleToPosition,
    onDragEnd: changeAngleToPosition
  });
  useEffect(() => {
    if (isDragging) {
      if (previousCursorValueRef.current === undefined) {
        previousCursorValueRef.current = document.body.style.cursor;
      }
      document.body.style.cursor = 'grabbing';
    } else {
      document.body.style.cursor = previousCursorValueRef.current || '';
      previousCursorValueRef.current = undefined;
    }
  }, [isDragging]);
  return /*#__PURE__*/_jsx(CircleRoot, {
    ref: angleCircleRef,
    onMouseDown: startDrag,
    className: "components-angle-picker-control__angle-circle",
    ...props,
    children: /*#__PURE__*/_jsx(CircleIndicatorWrapper, {
      style: value ? {
        transform: `rotate(${value}deg)`
      } : undefined,
      className: "components-angle-picker-control__angle-circle-indicator-wrapper",
      tabIndex: -1,
      children: /*#__PURE__*/_jsx(CircleIndicator, {
        className: "components-angle-picker-control__angle-circle-indicator"
      })
    })
  });
}
function getAngle(centerX, centerY, pointX, pointY) {
  const y = pointY - centerY;
  const x = pointX - centerX;
  const angleInRadians = Math.atan2(y, x);
  const angleInDeg = Math.round(angleInRadians * (180 / Math.PI)) + 90;
  if (angleInDeg < 0) {
    return 360 + angleInDeg;
  }
  return angleInDeg;
}
export default AngleCircle;
//# sourceMappingURL=angle-circle.js.map