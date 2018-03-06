import extract from 'flarum/utils/extract';
import tagLabel from 'flarum/tags/helpers/tagLabel';
import sortTags from 'flarum/tags/utils/sortTags';

export default function tagsLabel(tags, attrs = {}) {
  const children = [];
  const link = extract(attrs, 'link');

  attrs.className = 'TagsLabel ' + (attrs.className || '');

  if (tags) {
    sortTags(tags).forEach(tag => {
      if (tag || tags.length === 1) {
        children.push(tagLabel(tag, {link}));
      }
    });
  } else {
    children.push(tagLabel());
  }

  return <span {...attrs}>{children}</span>;
}
