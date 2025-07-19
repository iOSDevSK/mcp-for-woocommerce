/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import { MediaPlaceholder } from './styles/focal-point-picker-style';
import { isVideoType } from './utils';
import { jsx as _jsx } from "react/jsx-runtime";
export default function Media({
  alt,
  autoPlay,
  src,
  onLoad,
  mediaRef,
  // Exposing muted prop for test rendering purposes
  // https://github.com/testing-library/react-testing-library/issues/470
  muted = true,
  ...props
}) {
  if (!src) {
    return /*#__PURE__*/_jsx(MediaPlaceholder, {
      className: "components-focal-point-picker__media components-focal-point-picker__media--placeholder",
      ref: mediaRef,
      ...props
    });
  }
  const isVideo = isVideoType(src);
  return isVideo ? /*#__PURE__*/_jsx("video", {
    ...props,
    autoPlay: autoPlay,
    className: "components-focal-point-picker__media components-focal-point-picker__media--video",
    loop: true,
    muted: muted,
    onLoadedData: onLoad,
    ref: mediaRef,
    src: src
  }) : /*#__PURE__*/_jsx("img", {
    ...props,
    alt: alt,
    className: "components-focal-point-picker__media components-focal-point-picker__media--image",
    onLoad: onLoad,
    ref: mediaRef,
    src: src
  });
}
//# sourceMappingURL=media.js.map