import Echo from 'laravel-echo'
import Pusher, { type Channel, type ChannelAuthorizationCallback } from 'pusher-js'
import { getToken } from './tokenStorage'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

/**
 * Это не cookie-based Sanctum SPA, а чистый Bearer-токен API (как весь остальной apiRequest) —
 * поэтому кастомный authorizer сам подставляет заголовок Authorization на /api/broadcasting/auth
 * вместо дефолтной cookie-авторизации echo/pusher-js.
 */
export const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT),
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel: Channel) => ({
    authorize(socketId: string, callback: ChannelAuthorizationCallback) {
      fetch(`${import.meta.env.VITE_API_URL}/api/broadcasting/auth`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json',
          Authorization: `Bearer ${getToken()}`,
        },
        body: new URLSearchParams({ socket_id: socketId, channel_name: channel.name }),
      })
        .then((response) => (response.ok ? response.json() : Promise.reject(response)))
        .then((data) => callback(null, data))
        .catch(() => callback(new Error('broadcasting auth failed'), null))
    },
  }),
})
