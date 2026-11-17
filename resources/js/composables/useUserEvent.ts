import { usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';

/**
 * Listens to an event on the signed-in user's private channel (App.Models.User.{id}).
 * Events use broadcastAs() names, hence the leading dot.
 */
export function useUserEvent<TPayload>(event: string, callback: (payload: TPayload) => void) {
    const page = usePage();
    const userId = (page.props.auth as { user: { id: number } }).user.id;

    return useEcho<TPayload>(`App.Models.User.${userId}`, `.${event}`, callback);
}
