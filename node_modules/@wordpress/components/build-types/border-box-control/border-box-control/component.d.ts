import type { LabelProps, BorderControlProps } from '../../border-control/types';
/**
 * An input control for the color, style, and width of the border of a box. The
 * border can be customized as a whole, or individually for each side of the box.
 *
 * ```jsx
 * import { BorderBoxControl } from '@wordpress/components';
 * import { __ } from '@wordpress/i18n';
 *
 * const colors = [
 * 	{ name: 'Blue 20', color: '#72aee6' },
 * 	// ...
 * ];
 *
 * const MyBorderBoxControl = () => {
 * 	const defaultBorder = {
 * 		color: '#72aee6',
 * 		style: 'dashed',
 * 		width: '1px',
 * 	};
 * 	const [ borders, setBorders ] = useState( {
 * 		top: defaultBorder,
 * 		right: defaultBorder,
 * 		bottom: defaultBorder,
 * 		left: defaultBorder,
 * 	} );
 * 	const onChange = ( newBorders ) => setBorders( newBorders );
 *
 * 	return (
 * 		<BorderBoxControl
 * 			__next40pxDefaultSize
 * 			colors={ colors }
 * 			label={ __( 'Borders' ) }
 * 			onChange={ onChange }
 * 			value={ borders }
 * 		/>
 * 	);
 * };
 * ```
 */
export declare const BorderBoxControl: import("../../context").WordPressComponent<"div", Pick<import("../../color-palette/types").ColorPaletteProps, "colors" | "__experimentalIsRenderedInSidebar"> & {
    disableCustomColors?: boolean;
    enableAlpha?: boolean;
} & LabelProps & Pick<BorderControlProps, "size" | "enableStyle"> & {
    onChange: (value: import("../types").AnyBorder) => void;
    popoverPlacement?: import("../../popover/types").PopoverProps["placement"];
    popoverOffset?: import("../../popover/types").PopoverProps["offset"];
    value: import("../types").AnyBorder;
    __next40pxDefaultSize?: boolean;
} & import("react").RefAttributes<any>, false>;
export default BorderBoxControl;
//# sourceMappingURL=component.d.ts.map