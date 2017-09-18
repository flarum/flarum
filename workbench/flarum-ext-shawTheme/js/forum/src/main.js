import { extend } from 'flarum/extend';
import Post from 'flarum/components/Post';
import Component from 'flarum/Component';
import Page from 'flarum/components/Page';
import HeaderSecondary from 'flarum/components/HeaderSecondary';
import SessionDropdown from 'flarum/components/SessionDropdown';
import Dropdown from 'flarum/components/Dropdown';
import IndexPage from 'flarum/components/IndexPage';
import listItems from 'flarum/helpers/listItems';
import ItemList from 'flarum/utils/ItemList';
import Button from 'flarum/components/Button';
import LinkButton from 'flarum/components/LinkButton';
import SelectDropdown from 'flarum/components/SelectDropdown';
import tagLabel from 'flarum/tags/helpers/tagLabel';
import sortTags from 'flarum/tags/utils/sortTags';
import TagsPage from 'flarum/tags/components/TagsPage';
import humanTime from 'flarum/helpers/humanTime';
import icon from 'flarum/helpers/icon';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import listInline from 'romanzpolski/shawTheme/listInline';



app.initializers.add('romanzpolski/shawTheme', () => {



    SessionDropdown.prototype.getButtonContent = function() {
        const user = app.session.user;
        const attrs = {};
        attrs.style = {background: '#000'};
        return [
            <span className="Button-label">{username(user)}</span>,
            avatar(user), ' '
        ];
    };



    IndexPage.prototype.viewItems = function(){
        const items = new ItemList();
        const sortMap = app.cache.discussionList.sortMap();

        const sortOptions = {};
        for (const i in sortMap) {
            sortOptions[i] = app.translator.trans('core.forum.index_sort.' + i + '_button');
        }

        items.add('sort',
        listInline.component({
                buttonClassName: 'Button',
                label: sortOptions[this.params().sort] || Object.keys(sortMap).map(key => sortOptions[key])[0],
                children: Object.keys(sortOptions).map(value => {
                    const label = sortOptions[value];
                    const active = (this.params().sort || Object.keys(sortMap)[0]) === value;

                    return Button.component({
                        className: 'Button',
                        children: label,
                        icon: active ? 'check' : true,
                        onclick: this.changeSort.bind(this, value),
                        active: active,
                    })
                }),
            })

        );

        return items;
    };


    IndexPage.prototype.sidebarItems = function() {
        const items = new ItemList();
        const canStartDiscussion = app.forum.attribute('canStartDiscussion') || !app.session.user;

        items.add('newDiscussion',
            Button.component({
                children: app.translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button'),
                icon: 'edit',
                className: 'Button Button--primary IndexPage-newDiscussion',
                itemClassName: 'App-primaryControl',
                onclick: this.newDiscussion.bind(this),
                disabled: !canStartDiscussion
            })
        );

        items.add('nav',
            SelectDropdown.component({
                children: this.navItems(this).toArray(),
                buttonClassName: 'Button',
                className: 'App-titleControl'
            })
        );
        return items;
    };


    TagsPage.prototype.view = function() {
        const pinned = this.tags.filter(tag => tag.position() !== null);
        const cloud = this.tags.filter(tag => tag.position() === null);

        return (
            <div className="TagsPage">
                {IndexPage.prototype.hero()}
                <div className="container">
                    <nav className="TagsPage-nav IndexPage-nav sideNav" config={IndexPage.prototype.affixSidebar}>
                        <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
                    </nav>

                    <div className="TagsPage-content sideNavOffset">
                        <ul className="TagTiles">
                            {pinned.map(tag => {
                                const lastDiscussion = tag.lastDiscussion();
                                const children = sortTags(app.store.all('tags').filter(child => child.parent() === tag));
                                return (

                                    <li className={'TagTile bgImg ' +tag.data.attributes.slug+ (tag.color() ? ' colored' : '')}
                                        style={{backgroundColor: tag.color()}}>
                                        <a className="TagTile-info" href={app.route.tag(tag)} config={m.route}>
                                            <h3 className="TagTile-name">{tag.name()}</h3>
                                            <p className="TagTile-description">{tag.description()}</p>
                                            {children
                                                ? (
                                                    <div className="TagTile-children">
                                                        {children.map(child => [
                                                            <a href={app.route.tag(child)} config={function(element, isInitialized) {
                                                                if (isInitialized) return;
                                                                $(element).on('click', e => e.stopPropagation());
                                                                m.route.apply(this, arguments);
                                                            }}>
                                                                {child.name()}
                                                            </a>,
                                                            ' '
                                                        ])}
                                                    </div>
                                                ) : ''}
                                        </a>
                                        {lastDiscussion
                                            ? (
                                                <a className="TagTile-lastDiscussion"
                                                   href={app.route.discussion(lastDiscussion, lastDiscussion.lastPostNumber())}
                                                   config={m.route}>
                                                    <span className="TagTile-lastDiscussion-title">{lastDiscussion.title()}</span>
                                                    {humanTime(lastDiscussion.lastTime())}
                                                </a>
                                            ) : (
                                                <span className="TagTile-lastDiscussion"/>
                                            )}
                                    </li>
                                );
                            })}
                        </ul>

                        {cloud.length ? (
                            <div className="TagCloud">
                                {cloud.map(tag => {
                                    const color = tag.color();

                                    return [
                                        tagLabel(tag, {link: true}),
                                        ' '
                                    ];
                                })}
                            </div>
                        ) : ''}
                    </div>
                </div>
            </div>
        );
    };




    IndexPage.prototype.view = function() {
        console.log(this.sidebarItems().toArray());
//        console.log(this.viewItems().toArray());
        return (
            <div className="IndexPage">
                {this.hero()}
                <div className="container">
                    <nav className="IndexPage-nav sideNav">
                        <ul>{listItems(this.sidebarItems().toArray())}</ul>
                    </nav>

                    <div className="IndexPage-results sideNavOffset">
                        <div className="IndexPage-toolbar">
                            <ul className="IndexPage-toolbar-view kutas">{listItems(this.viewItems().toArray())}</ul>
                            <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
                        </div>
                        {app.cache.discussionList.render()}
                    </div>
                </div>
            </div>
        );
    };

/*    extend(Post.prototype, 'view', function(vdom) {
//        vdom.children.push('<div class="kutas"><p>this is some stuff to add after each post</p></div>');
        vdom.attrs.style = 'background-color: #fafafa; border-bottom: 1px solid #000';
    });*/
});