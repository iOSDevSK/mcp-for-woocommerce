declare const ConnectedBorderBoxControlSplitControls: import("../../context").WordPressComponent<"div", Pick<import("../../color-palette/types").ColorPaletteProps, "colors" | "__experimentalIsRenderedInSidebar"> & {
    disableCustomColors?: boolean;
    enableAlpha?: boolean;
} & Pick<import("../types").BorderBoxControlProps, "size" | "enableStyle"> & {
    onChange: (value: import("../../border-control/types").Border | undefined, side: import("../types").BorderSide) => void;
    popoverPlacement?: import("../../popover/types").PopoverProps["placement"];
    popoverOffset?: import("../../popover/types").PopoverProps["offset"];
    value?: import("../types").Borders;
} & import("react").RefAttributes<any>, true>;
export default ConnectedBorderBoxControlSplitControls;
//# sourceMappingURL=component.d.ts.map