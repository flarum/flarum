import { extend } from 'flarum/extend';
import Component from 'flarum/Component';
import listItems from 'flarum/helpers/listItems';

export default class listInline extends Component {
    static initProps(props) {
        super.initProps(props);
        props.className = props.className || '';
        props.buttonClassName = props.buttonClassName || '';
        props.menuClassName = props.menuClassName || '';
        props.label = props.label || '';
        props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'caret-down';
    }
    init(){
        this.showing = false;
    }
    view(){
        const items = this.props.children ? listItems(this.props.children) : [];

        return (
            <div className={'viewNavInline ' + this.props.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
                {this.getMenu(items)}
            </div>
        );
    }
    getMenu(items) {
        return (
            <ul className={'listInline ' + this.props.menuClassName}>
                {items}
            </ul>
        );
    }
}