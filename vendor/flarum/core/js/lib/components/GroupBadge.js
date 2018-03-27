import Badge from 'flarum/components/Badge';

export default class GroupBadge extends Badge {
  static initProps(props) {
    super.initProps(props);

    if (props.group) {
      props.icon = props.group.icon();
      props.style = {backgroundColor: props.group.color()};
      props.label = typeof props.label === 'undefined' ? props.group.nameSingular() : props.label;
      props.type = 'group--' + props.group.id();

      delete props.group;
    }
  }
}
