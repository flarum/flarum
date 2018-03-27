class Item {
  constructor(content, priority) {
    this.content = content;
    this.priority = priority;
  }
}

/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList {
  constructor() {
    /**
     * The items in the list.
     *
     * @type {Object}
     * @public
     */
    this.items = {};
  }

  /**
   * Check whether an item is present in the list.
   *
   * @param key
   * @returns {boolean}
   */
  has(key) {
    return !!this.items[key];
  }

  /**
   * Get the content of an item.
   *
   * @param {String} key
   * @return {*}
   * @public
   */
  get(key) {
    return this.items[key].content;
  }

  /**
   * Add an item to the list.
   *
   * @param {String} key A unique key for the item.
   * @param {*} content The item's content.
   * @param {Integer} [priority] The priority of the item. Items with a higher
   *     priority will be positioned before items with a lower priority.
   * @public
   */
  add(key, content, priority = 0) {
    this.items[key] = new Item(content, priority);
  }

  /**
   * Replace an item in the list, only if it is already present.
   *
   * @param {String} key
   * @param {*} [content]
   * @param {Integer} [priority]
   * @public
   */
  replace(key, content = null, priority = null) {
    if (this.items[key]) {
      if (content !== null) {
        this.items[key].content = content;
      }

      if (priority !== null) {
        this.items[key].priority = priority;
      }
    }
  }

  /**
   * Remove an item from the list.
   *
   * @param {String} key
   * @public
   */
  remove(key) {
    delete this.items[key];
  }

  /**
   * Merge another list's items into this one.
   *
   * @param {ItemList} items
   * @public
   */
  merge(items) {
    for (const i in items.items) {
      if (items.items.hasOwnProperty(i) && items.items[i] instanceof Item) {
        this.items[i] = items.items[i];
      }
    }
  }

  /**
   * Convert the list into an array of item content arranged by priority. Each
   * item's content will be assigned an `itemName` property equal to the item's
   * unique key.
   *
   * @return {Array}
   * @public
   */
  toArray() {
    const items = [];

    for (const i in this.items) {
      if (this.items.hasOwnProperty(i) && this.items[i] instanceof Item) {
        this.items[i].content = Object(this.items[i].content);

        this.items[i].content.itemName = i;
        items.push(this.items[i]);
        this.items[i].key = items.length;
      }
    }

    return items.sort((a, b) => {
      if (a.priority === b.priority) {
        return a.key - b.key;
      } else if (a.priority > b.priority) {
        return -1;
      }
      return 1;
    }).map(item => item.content);
  }
}

