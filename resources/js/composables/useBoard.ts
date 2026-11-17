import type { ApplicationStatus, BoardColumn } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { ref, watch, type Ref } from 'vue';
import type { DraggableEvent } from 'vue-draggable-plus';

/**
 * Local, draggable copy of the board columns.
 *
 * vue-draggable-plus reorders the local arrays immediately (optimistic UI); the move is then sent
 * to the server, and the fresh columns it returns replace the local copy. If the request fails,
 * reloading the columns puts the card back where the server says it is.
 */
export function useBoard(serverColumns: Ref<BoardColumn[]>) {
    // Props are reactive proxies, which structuredClone rejects; the data is plain JSON from the server anyway.
    const copy = (source: BoardColumn[]): BoardColumn[] => JSON.parse(JSON.stringify(source));

    const columns = ref<BoardColumn[]>(copy(serverColumns.value));
    const saving = ref(false);

    watch(serverColumns, (fresh) => (columns.value = copy(fresh)), { deep: true });

    const onDragEnd = (event: DraggableEvent) => {
        const applicationId = Number(event.item.dataset.id);
        const status = event.to.dataset.status as ApplicationStatus;
        const position = event.newIndex ?? 0;

        if (event.from === event.to && event.oldIndex === event.newIndex) {
            return;
        }

        saving.value = true;

        router.patch(
            route('job-applications.move', { jobApplication: applicationId }),
            { status, position },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['columns'],
                onError: () => router.reload({ only: ['columns'] }),
                onFinish: () => (saving.value = false),
            },
        );
    };

    return { columns, saving, onDragEnd };
}
