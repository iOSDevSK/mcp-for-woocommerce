/**
 * Internal dependencies
 */
import { buildTermsTree } from './terms';
import TreeSelect from '../tree-select';
import { jsx as _jsx } from "react/jsx-runtime";
export default function AuthorSelect({
  __next40pxDefaultSize,
  label,
  noOptionLabel,
  authorList,
  selectedAuthorId,
  onChange: onChangeProp
}) {
  if (!authorList) {
    return null;
  }
  const termsTree = buildTermsTree(authorList);
  return /*#__PURE__*/_jsx(TreeSelect, {
    label,
    noOptionLabel,
    onChange: onChangeProp,
    tree: termsTree,
    selectedId: selectedAuthorId !== undefined ? String(selectedAuthorId) : undefined,
    __nextHasNoMarginBottom: true,
    __next40pxDefaultSize: __next40pxDefaultSize
  });
}
//# sourceMappingURL=author-select.js.map