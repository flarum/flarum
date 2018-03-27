import LinkButton from 'flarum/components/LinkButton';
import tagIcon from 'flarum/tags/helpers/tagIcon';

export default class TagLinkButton extends LinkButton {
  view() {
    const tag = this.props.tag;
    const active = this.constructor.isActive(this.props);
    const description = tag && tag.description();

    return (
      <a className={'TagLinkButton hasIcon ' + (tag.isChild() ? 'child' : '')} href={this.props.href} config={m.route}
        style={active && tag ? {color: tag.color()} : ''}
        title={description || ''}>
        {tagIcon(tag, {className: 'Button-icon'})}
        {this.props.children}
      </a>
    );
  }

  static initProps(props) {
    const tag = props.tag;

    props.params.tags = tag ? tag.slug() : 'untagged';
    props.href = app.route('tag', props.params);
    props.children = tag ? tag.name() : app.translator.trans('flarum-tags.forum.index.untagged_link');
  }
}
