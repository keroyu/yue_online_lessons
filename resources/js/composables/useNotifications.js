import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const notificationCount = ref(0)
const notifications = ref([])
let initialized = false

export function useNotifications() {
  const page = usePage()

  if (!initialized) {
    initialized = true

    notificationCount.value = page.props.notificationCount ?? 0
    notifications.value = page.props.notifications ?? []

    watch(() => page.props.notificationCount, (v) => {
      if (typeof v === 'number') notificationCount.value = v
    })

    watch(() => page.props.notifications, (v) => {
      if (Array.isArray(v)) notifications.value = v
    })
  }

  const markRead = (id, { onSuccess } = {}) => {
    router.post(`/member/notifications/${id}/read`, {}, {
      preserveScroll: true,
      preserveState: true,
      onSuccess,
    })
  }

  return { notificationCount, notifications, markRead }
}
