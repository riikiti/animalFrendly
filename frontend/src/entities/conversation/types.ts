export interface Conversation {
  id: string
  match_id: string | null
  adoption_request_id: string | null
  shelter_id: string | null
  shelter_animal_id: string | null
  created_at: string
  counterpart_address: string | null
  counterpart_location: { lat: number; lng: number } | null
  counterpart_name: string | null
  counterpart_avatar_url: string | null
}

export interface Message {
  id: string
  conversation_id: string
  sender_id: string
  body: string
  read_at: string | null
  created_at: string
}
