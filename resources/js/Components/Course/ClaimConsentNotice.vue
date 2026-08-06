<script setup>
// Consent disclosure for every free-claim entry point (010 FR-027).
//
// The copy lives here rather than at each call site on purpose: this is a
// terms notice, and once two copies drift there is no answering "which one
// did they actually agree to". Passive disclosure — submitting is consent,
// no checkbox (D24): the claim form had its verification step removed
// specifically to cut friction, so adding a required tick back is a step
// in the wrong direction.
//
// Wording follows FR-017 — free products are 領取/停止接收, never 訂閱/退訂.
</script>

<template>
  <p class="text-xs leading-relaxed text-gray-500">
    領取即代表同意接收免費資源及後續相關內容。您可以隨時停止接收；停止後我們將不再寄送信件，同時失去限定免費資源與服務的申請資格。
  </p>
</template>
