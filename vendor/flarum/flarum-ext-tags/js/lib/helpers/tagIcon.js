export default function tagIcon(tag, attrs = {}) {
  attrs.className = 'icon TagIcon ' + (attrs.className || '');

  if (tag) {
    attrs.style = attrs.style || {};
    attrs.style.backgroundColor = tag.color();
  } else {
    attrs.className += ' untagged';
  }

  return <span {...attrs}/>;
}
