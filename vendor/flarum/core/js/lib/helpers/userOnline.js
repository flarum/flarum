import icon from 'flarum/helpers/icon';

/**
 * The `useronline` helper displays a green circle if the user is online
 *
 * @param {User} user
 * @return {Object}
 */
export default function userOnline(user) {
    if (user.lastSeenTime() && user.isOnline()) {
        return <span className="UserOnline">{icon('circle')}</span>;
    }
}
