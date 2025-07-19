/**
 * Internal dependencies
 */
import BaseSlot from './slot';
import Fill from './fill';
import Provider from './provider';
import { jsx as _jsx } from "react/jsx-runtime";
export { Fill, Provider };
export function Slot({
  bubblesVirtually,
  ...restProps
}) {
  return /*#__PURE__*/_jsx(BaseSlot, {
    ...restProps
  });
}
export function createSlotFill(name) {
  const FillComponent = props => /*#__PURE__*/_jsx(Fill, {
    name: name,
    ...props
  });
  FillComponent.displayName = name + 'Fill';
  const SlotComponent = props => /*#__PURE__*/_jsx(Slot, {
    name: name,
    ...props
  });
  SlotComponent.displayName = name + 'Slot';
  return {
    Fill: FillComponent,
    Slot: SlotComponent
  };
}
//# sourceMappingURL=index.native.js.map