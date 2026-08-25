import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Success/error toast state, shared by AppLayout and AdminLayout.
 *
 * Clears only success/error after the toast has had time to show — not the
 * whole flash object, since other keys (newsletter_*, drip_*, payment_failed)
 * may still be live for a component elsewhere on the page to read.
 */
export function useFlash() {
  const page = usePage()
  const flash = computed(() => page.props.flash)

  watch(
    () => flash.value,
    (newFlash) => {
      if (newFlash?.success || newFlash?.error) {
        setTimeout(() => {
          page.props.flash = { ...page.props.flash, success: null, error: null }
        }, 5000)
      }
    },
    { immediate: true }
  )

  return { flash }
}
