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

export function useCart() {
  const page = usePage()

  const isAuthenticated = () => !!page.props.auth?.user

  const addToCart = async (courseId, { name = '', price = 0, thumbnail = null } = {}) => {
    if (isAuthenticated()) {
      try {
        const res = await window.axios.post('/api/cart/add', { course_id: courseId })

        if (window.fbq) {
          window.fbq('track', 'AddToCart', {
            content_ids: [courseId],
            value: price,
            currency: 'TWD',
            content_type: 'product',
          })
        }

        return { success: true, cartCount: res.data.cartCount }
      } catch (e) {
        if (e.response?.status === 409) {
          return { success: true, alreadyInCart: true, cartCount: e.response.data.cartCount }
        }
        return { success: false, error: e.response?.data?.message || '加入購物車失敗' }
      }
    } else {
      const cart = getGuestCart()
      const exists = cart.some((i) => i.id === courseId)
      if (!exists) {
        cart.push({ id: courseId, name, price, thumbnail })
        saveGuestCart(cart)
      }

      if (window.fbq) {
        window.fbq('track', 'AddToCart', {
          content_ids: [courseId],
          value: price,
          currency: 'TWD',
          content_type: 'product',
        })
      }

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
      await window.axios.post('/api/cart/merge', { course_ids: courseIds })
      localStorage.removeItem(GUEST_CART_KEY)
    } catch {
      // merge failure is non-critical; guest cart remains for retry
    }
  }

  return { addToCart, buyNow, mergeGuestCartOnLogin }
}
