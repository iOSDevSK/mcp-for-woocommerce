/**
 * External dependencies
 */
import { Text, View } from 'react-native';

/**
 * WordPress dependencies
 */
import { withPreferredColorScheme } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const HR = ({
  getStylesFromColorScheme,
  lineStyle,
  marginLeft,
  marginRight,
  style,
  textStyle,
  text,
  ...props
}) => {
  const renderLine = key => /*#__PURE__*/_jsx(View, {
    style: [getStylesFromColorScheme(styles.line, styles.lineDark), lineStyle]
  }, key);
  const renderText = key => /*#__PURE__*/_jsx(View, {
    style: styles.textContainer,
    children: /*#__PURE__*/_jsx(Text, {
      style: [styles.text, textStyle],
      children: text
    })
  }, key);
  const renderInner = () => {
    if (!text) {
      return renderLine();
    }
    return [renderLine(1), renderText(2), renderLine(3)];
  };
  return /*#__PURE__*/_jsx(View, {
    style: [styles.container, {
      marginLeft,
      marginRight
    }, style],
    ...props,
    children: renderInner()
  });
};
export const HorizontalRule = withPreferredColorScheme(HR);
//# sourceMappingURL=index.native.js.map