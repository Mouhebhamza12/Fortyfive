<script setup>
// The Fortyfive mark, traced from the logo artwork so it stays sharp at any size
// and takes its colour from the surrounding text colour.
defineProps({
  title: { type: String, default: "" },
});
</script>

<template>
  <svg
    class="brand-mark"
    viewBox="0 0 627 803"
    :role="title ? 'img' : undefined"
    :aria-label="title || undefined"
    :aria-hidden="title ? undefined : 'true'"
    fill="currentColor"
  >
    <polygon class="brand-mark__four" points="181,119 61,154 0,660 267,579 241,803 365,722 398,447 132,527" />
    <polygon class="brand-mark__five" points="627,0 255,103 212,439 462,363 435,602 564,518 599,235 346,310 363,163 616,90" />
  </svg>
</template>
