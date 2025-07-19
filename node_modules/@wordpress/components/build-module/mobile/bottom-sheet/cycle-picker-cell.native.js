/**
 * Internal dependencies
 */
import Cell from './cell';
import { jsx as _jsx } from "react/jsx-runtime";
export default function BottomSheetCyclePickerCell(props) {
  const {
    value,
    options,
    onChangeValue,
    ...cellProps
  } = props;
  const nextOptionValue = () => {
    return options[(selectedOptionIndex + 1) % options.length].value;
  };
  const selectedOptionIndex = options.findIndex(option => option.value === value);
  const optionsContainsValue = options.length > 0 && selectedOptionIndex !== -1;
  return /*#__PURE__*/_jsx(Cell, {
    onPress: () => optionsContainsValue && onChangeValue(nextOptionValue()),
    editable: false,
    value: optionsContainsValue && options[selectedOptionIndex].name,
    ...cellProps
  });
}
//# sourceMappingURL=cycle-picker-cell.native.js.map