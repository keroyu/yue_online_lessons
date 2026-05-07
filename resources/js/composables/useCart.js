import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const GUEST_CART_KEY = 'guest_cart'

function getGuestCart() {
  try {
    return JSON.parse(localStorage.getItem(GUEST_CART_KEY) || '[]')
  } catch {
    return []
  }
}

function saveGuestCart(items) {
  localStorage.setItem(GUEST_CART_KEY, JSON.stringify(items))
}

// Module-scoped reactive state — shared across every useCart() call
const cartCount = ref(0)
let initialized = false

export function useCart() {
  const page = usePage()

  const isAuthenticated = () => !!page.props.auth?.user

  if (!initialized) {
    initialized = true

    cartCount.value = isAuthenticated()
      ? (page.props.cartCount ?? 0)
      : getGuestCart().length

    // Sync when Inertia navigates and refreshes shared props
    watch(() => page.props.cartCount, (v) => {
      if (isAuthenticated() && typeof v === 'number') {
        cartCount.value = v
      }
    })

    // Re-sync on auth state change (login/logout)
    watch(() => page.props.auth?.user, (u) => {
      cartCount.value = u ? (page.props.cartCount ?? 0) : getGuestCart().length
    })
  }

  const addToCart = async (courseId, { name = '', price = 0, thumbnail = null } = {}) => {
    if (window.fbq) {
      window.fbq('track', 'AddToCart', {
        value:        Number(price) || 0,
        currency:     'TWD',
        content_ids:  [courseId],
        content_type: 'product',
        content_name: name,
      })
    }

    if (isAuthenticated()) {
      try {
        const res = await window.axios.post('/api/cart/add', { course_id: courseId })
        cartCount.value = res.data.cartCount
        return { success: true, cartCount: res.data.cartCount }
      } catch (e) {
        if (e.response?.status === 409) {
          if (typeof e.response.data?.cartCount === 'number') {
            cartCount.value = e.response.data.cartCount
          }
          return { success: true, alreadyInCart: true, cartCount: e.response.data?.cartCount }
        }
        return { success: false, error: e.response?.data?.message || '加入購物車失敗' }
      }
    } else {
      const cart = getGuestCart()
      if (!cart.some((i) => i.id === courseId)) {
        cart.push({ id: courseId, name, price, thumbnail })
        saveGuestCart(cart)
      }
      cartCount.value = cart.length
      return { success: true, cartCount: cart.length }
    }
  }

  const removeFromCart = async (courseId) => {
    if (isAuthenticated()) {
      try {
        const res = await window.axios.delete(`/api/cart/${courseId}`)
        cartCount.value = res.data.cartCount
        return { success: true, cartCount: res.data.cartCount }
      } catch (e) {
        return { success: false, error: e.response?.data?.message || '移除失敗' }
      }
    } else {
      const cart = getGuestCart().filter((i) => i.id !== courseId)
      saveGuestCart(cart)
      cartCount.value = cart.length
      return { success: true, cartCount: cart.length }
    }
  }

  const buyNow = async (courseId, opts = {}) => {
    const result = await addToCart(courseId, opts)
    if (result.success) {
      router.visit('/checkout')
    }
    return result
  }

  const mergeGuestCartOnLogin = async () => {
    const cart = getGuestCart()
    if (cart.length === 0) return

    const courseIds = cart.map((i) => i.id)
    try {
      const res = await window.axios.post('/api/cart/merge', { course_ids: courseIds })
      localStorage.removeItem(GUEST_CART_KEY)
      if (typeof res.data?.cartCount === 'number') {
        cartCount.value = res.data.cartCount
      }
    } catch {
      // merge failure is non-critical; guest cart remains for retry
    }
  }

  return { addToCart, removeFromCart, buyNow, mergeGuestCartOnLogin, cartCount }
}
