/**
 * Internal dependencies
 */
import Cell from './cell';
import Picker from '../picker';
import { jsx as _jsx } from "react/jsx-runtime";
export default function BottomSheetPickerCell(props) {
  const {
    options,
    hideCancelButton,
    onChangeValue,
    value,
    ...cellProps
  } = props;
  let picker;
  const onCellPress = () => {
    picker.presentPicker();
  };
  const onChange = newValue => {
    onChangeValue(newValue);
  };
  const option = options.find(opt => opt.value === value);
  const label = option ? option.label : value;
  return /*#__PURE__*/_jsx(Cell, {
    onPress: onCellPress,
    editable: false,
    value: label,
    ...cellProps,
    children: /*#__PURE__*/_jsx(Picker, {
      leftAlign: true,
      hideCancelButton: hideCancelButton,
      ref: instance => picker = instance,
      options: options,
      onChange: onChange
    })
  });
}
//# sourceMappingURL=picker-cell.native.js.map