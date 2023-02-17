<script setup>

let props = defineProps({
  fields: Object,
  items: Array,
  asset: Boolean,
});

const textColor = (item, highlight) => {
  let ret = 'text-slate-400';
  if (! highlight) {
    return ret;
  }
  if (props.asset) {
    if (item > 0) {
      ret = 'text-green-400';
    } else {
      ret = 'text-red-400';
    }

  } else {
    if (item > 0) {
      ret = 'text-red-400';
    } else {
      ret = 'text-green-400';
    }
  }
  return ret;
};

</script>

<template>
  <table class="min-w-full ">
    <thead>
      <tr>
        <template v-for="{ key, label } in fields" :key="key">
          <th
            :class="textColor(false, false)"
          >
            {{ label }}
          </th>
        </template>
      </tr>
    </thead>

    <tbody>
      <tr v-for="item in items" :key="item.uuid" :class="{ 'bg-slate-900':true, 'border-b': true }">
        <td v-for="{ key, label, highlight } in fields"
          :key="key"
          :class="textColor(item[key], highlight)"
          class="px-6 py-4 whitespace-nowrap text-sm font-medium"
        >
          <slot :name="`cell(${key})`" :value="item[key]" :item="item">
            {{ item[key] }}
          </slot>
        </td>
      </tr>

    </tbody>
  </table>
</template>
