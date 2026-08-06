import { ref, onUnmounted } from 'vue'

/**
 * Two-stage confirm: the first press arms a countdown, the second one goes
 * through. The wait is not a delay for its own sake — it is there so that
 * reading the consequence actually happens before the action is taken.
 *
 * Three call sites, two different consequences:
 *   - Email review before an irreversible send (10s) — 011 FR-059, 010 D18.
 *     A mistyped address fails silently: everything reports success and only
 *     the inbox stays empty.
 *   - Stopping drip emails (5s) — 010 FR-028. People press it to get fewer
 *     emails without knowing it ends their claim eligibility too.
 *
 * The length is a parameter because the consequences differ, but the state
 * machine is shared: two guards against the same class of mistake drifting
 * apart is something nobody notices.
 *
 * Usage — `requestSubmit()` replaces the submit handler:
 *
 *   const confirm = useDelayedConfirm(5)
 *   function requestSubmit() {
 *     if (!confirm.start()) return   // first press: show the notice, wait
 *     submit()
 *   }
 *   watch(() => form.email, confirm.reset)
 */
export function useDelayedConfirm(seconds = 10) {
  const confirming = ref(false)
  const countdown = ref(0)
  let timer = null

  function stop() {
    clearInterval(timer)
    timer = null
  }

  function reset() {
    stop()
    confirming.value = false
    countdown.value = 0
  }

  /**
   * @returns {boolean} true once the visitor has confirmed and the wait is over
   *                    — i.e. the caller may go ahead and submit.
   */
  function start() {
    if (!confirming.value) {
      confirming.value = true
      countdown.value = seconds
      stop()

      timer = setInterval(() => {
        countdown.value -= 1
        if (countdown.value <= 0) stop()
      }, 1000)

      return false
    }

    return countdown.value <= 0
  }

  onUnmounted(stop)

  return { confirming, countdown, start, reset, stop }
}
