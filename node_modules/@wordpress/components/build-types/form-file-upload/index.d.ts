import type { WordPressComponentProps } from '../context';
import type { FormFileUploadProps } from './types';
/**
 * FormFileUpload allows users to select files from their local device.
 *
 * ```jsx
 * import { FormFileUpload } from '@wordpress/components';
 *
 * const MyFormFileUpload = () => (
 *   <FormFileUpload
 *     __next40pxDefaultSize
 *     accept="image/*"
 *     onChange={ ( event ) => console.log( event.currentTarget.files ) }
 *   >
 *     Upload
 *   </FormFileUpload>
 * );
 * ```
 */
export declare function FormFileUpload({ accept, children, multiple, onChange, onClick, render, ...props }: WordPressComponentProps<FormFileUploadProps, 'button', false>): import("react").JSX.Element;
export default FormFileUpload;
//# sourceMappingURL=index.d.ts.map