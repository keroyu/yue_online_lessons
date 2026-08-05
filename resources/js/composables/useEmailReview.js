import { ref, onUnmounted } from 'vue'

/**
 * Two-stage submit that makes the visitor look at the address they typed
 * (011 FR-059, shared with the drip claim form per 010 D18).
 *
 * A mistyped address fails silently: everything reports success and only the
 * inbox stays empty. The pause is not a delay for its own sake — it is there so
 * the second glance actually happens.
 *
 * The countdown length lives here rather than at each call site: two forms
 * guarding the same mistake with different strengths is a drift nobody notices.
 *
 * Usage — `requestSubmit()` replaces the submit handler:
 *
 *   const review = useEmailReview()
 *   function requestSubmit() {
 *     if (!review.start()) return   // first press: show the notice, wait
 *     submit()
 *   }
 *   watch(() => form.email, review.reset)
 */
export function useEmailReview(seconds = 10) {
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
