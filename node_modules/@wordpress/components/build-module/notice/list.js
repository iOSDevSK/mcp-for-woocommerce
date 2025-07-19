/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import Notice from '.';
import { createElement as _createElement } from "react";
import { jsxs as _jsxs } from "react/jsx-runtime";
const noop = () => {};

/**
 * `NoticeList` is a component used to render a collection of notices.
 *
 *```jsx
 * import { Notice, NoticeList } from `@wordpress/components`;
 *
 * const MyNoticeList = () => {
 *	const [ notices, setNotices ] = useState( [
 *		{
 *			id: 'second-notice',
 *			content: 'second notice content',
 *		},
 *		{
 *			id: 'fist-notice',
 *			content: 'first notice content',
 *		},
 *	] );
 *
 *	const removeNotice = ( id ) => {
 *		setNotices( notices.filter( ( notice ) => notice.id !== id ) );
 *	};
 *
 *	return <NoticeList notices={ notices } onRemove={ removeNotice } />;
 *};
 *```
 */
function NoticeList({
  notices,
  onRemove = noop,
  className,
  children
}) {
  const removeNotice = id => () => onRemove(id);
  className = clsx('components-notice-list', className);
  return /*#__PURE__*/_jsxs("div", {
    className: className,
    children: [children, [...notices].reverse().map(notice => {
      const {
        content,
        ...restNotice
      } = notice;
      return /*#__PURE__*/_createElement(Notice, {
        ...restNotice,
        key: notice.id,
        onRemove: removeNotice(notice.id)
      }, notice.content);
    })]
  });
}
export default NoticeList;
//# sourceMappingURL=list.js.map