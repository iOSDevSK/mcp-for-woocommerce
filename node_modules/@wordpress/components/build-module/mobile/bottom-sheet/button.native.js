/**
 * External dependencies
 */
import { TouchableOpacity, View, Text } from 'react-native';

/**
 * Internal dependencies
 */
import styles from './styles.scss';
import { jsx as _jsx } from "react/jsx-runtime";
const BottomSheetButton = ({
  onPress,
  disabled,
  text,
  color
}) => /*#__PURE__*/_jsx(TouchableOpacity, {
  accessible: true,
  onPress: onPress,
  disabled: disabled,
  children: /*#__PURE__*/_jsx(View, {
    style: {
      flexDirection: 'row',
      justifyContent: 'center'
    },
    children: /*#__PURE__*/_jsx(Text, {
      style: {
        ...styles.buttonText,
        color
      },
      children: text
    })
  })
});
export default BottomSheetButton;
//# sourceMappingURL=button.native.js.map