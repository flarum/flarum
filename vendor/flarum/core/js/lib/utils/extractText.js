/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {String}
 */
export default function extractText(vdom) {
  if (vdom instanceof Array) {
    return vdom.map(element => extractText(element)).join('');
  } else if (typeof vdom === 'object') {
    return extractText(vdom.children);
  } else {
    return vdom;
  }
}
