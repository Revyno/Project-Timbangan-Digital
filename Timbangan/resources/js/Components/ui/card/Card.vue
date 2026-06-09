<script setup>
import { cn } from "@/lib/utils";
import { computed } from "vue";

const props = defineProps({
  class: {
    type: [Boolean, null, String, Object, Array],
    required: false,
    skipCheck: true,
  },
});

const hasCustomBg = computed(() => {
  if (!props.class) return false;
  // Convert class object/array/string to string representation to check for 'bg-'
  const classStr = typeof props.class === 'string' 
    ? props.class 
    : JSON.stringify(props.class);
  return classStr.includes('bg-');
});
</script>

<template>
  <div
    :class="
      cn(
        'rounded-lg border text-card-foreground shadow-sm',
        !hasCustomBg && 'bg-card',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
