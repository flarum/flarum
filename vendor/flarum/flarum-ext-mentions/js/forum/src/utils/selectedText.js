export default function selectedText(body) {
  const selection = window.getSelection();
  if (selection.rangeCount) {
    const range = selection.getRangeAt(0);
    const parent = range.commonAncestorContainer;
    if (body[0] === parent || $.contains(body[0], parent)) {
      const clone = $("<div>").append(range.cloneContents());

      // Replace emoji images with their shortcode (found in alt attribute)
      clone.find('img.emoji').replaceWith(function() {
        return this.alt;
      });

      // Replace all other images with a Markdown image
      clone.find('img').replaceWith(function() {
        return '![](' + this.src + ')';
      });

      // Replace all links with a Markdown link
      clone.find('a').replaceWith(function() {
        return '[' + this.innerText + '](' + this.href + ')';
      });

      return clone.text();
    }
  }
  return "";
}
