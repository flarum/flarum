import Separator from 'flarum/components/Separator';
import classList from 'flarum/utils/classList';

function isSeparator(item) {
  return item && item.component === Separator;
}

function withoutUnnecessarySeparators(items) {
  const newItems = [];
  let prevItem;

  items.forEach((item, i) => {
    if (!isSeparator(item) || (prevItem && !isSeparator(prevItem) && i !== items.length - 1)) {
      prevItem = item;
      newItems.push(item);
    }
  });

  return newItems;
}

/**
 * The `listItems` helper wraps a collection of components in <li> tags,
 * stripping out any unnecessary `Separator` components.
 *
 * @param {*} items
 * @return {Array}
 */
export default function listItems(items) {
  if (!(items instanceof Array)) items = [items];

  return withoutUnnecessarySeparators(items).map(item => {
    const isListItem = item.component && item.component.isListItem;
    const active = item.component && item.component.isActive && item.component.isActive(item.props);
    const className = item.props ? item.props.itemClassName : item.itemClassName;

    if (isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
    }

    return isListItem
      ? item
      : <li className={classList([
          (item.itemName ? 'item-' + item.itemName : ''),
          className,
          (active ? 'active' : '')
        ])}
          key={item.itemName}>
          {item}
        </li>;
  });
}
