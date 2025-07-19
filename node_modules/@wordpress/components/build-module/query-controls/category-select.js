/**
 * Internal dependencies
 */
import { buildTermsTree } from './terms';
import TreeSelect from '../tree-select';

/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { jsx as _jsx } from "react/jsx-runtime";
export default function CategorySelect({
  __next40pxDefaultSize,
  label,
  noOptionLabel,
  categoriesList,
  selectedCategoryId,
  onChange: onChangeProp,
  ...props
}) {
  const termsTree = useMemo(() => {
    return buildTermsTree(categoriesList);
  }, [categoriesList]);
  return /*#__PURE__*/_jsx(TreeSelect, {
    label,
    noOptionLabel,
    onChange: onChangeProp,
    tree: termsTree,
    selectedId: selectedCategoryId !== undefined ? String(selectedCategoryId) : undefined,
    ...props,
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: __next40pxDefaultSize
  });
}
//# sourceMappingURL=category-select.js.map