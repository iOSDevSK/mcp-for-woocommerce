declare const ConnectedBorderControlDropdown: import("../../context").WordPressComponent<"div", Pick<import("../../color-palette/types").ColorPaletteProps, "colors" | "__experimentalIsRenderedInSidebar"> & {
    disableCustomColors?: boolean;
    enableAlpha?: boolean;
} & Pick<import("../types").BorderControlProps, "size" | "enableStyle"> & {
    border?: import("../types").Border;
    isStyleSettable: boolean;
    __unstablePopoverProps?: Omit<import("../../popover/types").PopoverProps, "children">;
    onChange: (newBorder?: import("../types").Border) => void;
    previousStyleSelection?: string;
    showDropdownHeader?: boolean;
} & import("react").RefAttributes<any>, true>;
export default ConnectedBorderControlDropdown;
//# sourceMappingURL=component.d.ts.map