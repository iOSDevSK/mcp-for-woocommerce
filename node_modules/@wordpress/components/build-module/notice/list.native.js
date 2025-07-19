/**
 * External dependencies
 */
import { View } from 'react-native';

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import Notice from './';
import styles from './style.scss';
import { useCallback } from '@wordpress/element';
import { createElement as _createElement } from "react";
import { jsx as _jsx } from "react/jsx-runtime";
function NoticeList() {
  const {
    notices
  } = useSelect(select => {
    const {
      getNotices
    } = select(noticesStore);
    return {
      notices: getNotices()
    };
  }, []);
  const {
    removeNotice
  } = useDispatch(noticesStore);
  const onRemoveNotice = useCallback(id => {
    removeNotice(id);
  }, [removeNotice]);
  if (!notices.length) {
    return null;
  }
  return /*#__PURE__*/_jsx(View, {
    style: styles.list,
    children: notices.map(notice => {
      return /*#__PURE__*/_createElement(Notice, {
        ...notice,
        key: notice.id,
        onNoticeHidden: onRemoveNotice
      });
    })
  });
}
export default NoticeList;
//# sourceMappingURL=list.native.js.map