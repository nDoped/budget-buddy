<script setup lang=ts>
  import {
    toRaw,
    ref,
    watch,
    watchEffect,
    computed,
    nextTick
  } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import InputError from '@/Components/InputError.vue';
  import CategoryInputs from '@/Components/CategoryInputs.vue';
  import CurrencyInput from '@/Components/CurrencyInput.vue';
  import CategorySelect from '@/Components/CategorySelect.vue';
  import ToggleSlider from '@/Components/ToggleSlider.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  import DangerButton from '@/Components/DangerButton.vue';
  import {
    forceNumericalInput,
    focusElement,
    randomUUID,
  } from '@/lib.js';

  interface Category {
    cat_data: {
      cat_id: number | null,
      name: string | null,
      cat_type_id: number | null,
      cat_type_name: string | null,
      hex_color: string,
      cat_subtype_id: number | null,
    },
    percent: number,
  }
  interface LineItem {
    key: string,
    cat_data: {
      cat_id: number | null,
      name: string | null,
      cat_type_id: number | null,
      cat_type_name: string | null,
      hex_color: string,
      cat_subtype_id?: number | null,
    },
    price: number,
  }
  const emit = defineEmits(['category-update', 'invalid-category-state', 'line-items-update']);
  const props = defineProps({
    totalAmount: {
      type: String,
      default: ""
    },
    categories: {
      type: Array,
      default: () => []
    },
    categoryTypes: {
      type: Array,
      default: () => []
    },
    categorySubtypes: {
      type: Array,
      default: () => []
    },
    availableCategories: {
      type: Array,
      default: () => []
    },
    errors: {
      type: Object,
      default: () => {return {}}
    },
    aiAnalysis: {
      type: Object,
      default: null
    },
    savedLineItems: {
      type: Array,
      default: () => []
    },
    savedTax: {
      type: [String, Number],
      default: null
    },
  });

  const calcCatsByReciept = ref(false);

  const filteredCats = computed(() => {
    return props.availableCategories.filter((ac) => {
      return ! catsRef.value.find(cr => cr.cat_data.cat_id == ac.cat_id);
    });
  });

  /*
   * Receipt logic
   */
  const taxAmount = ref(null);
  const subTotalError = ref(null);
  const total = ref(parseFloat(props.totalAmount));
  watch(() => props.totalAmount, (newVal) => {
    total.value = newVal;
  });

  const lineItems = ref<LineItem[]>([]);
  const savedLineItemsRef = ref<LineItem[]>([]);
  const savedTaxRef = ref(null);
  const showSavedLineItems = ref(false);

  const hasSavedLineItems = computed(() => {
    return savedLineItemsRef.value.length > 0;
  });

  const lineItemSum = computed(() => {
    if (lineItems.value.length === 0) {
      return parseFloat(0);
    } else {
      return parseFloat(lineItems.value.reduce((acc, item) => {
        return acc + parseFloat(item.price);
      }, 0).toFixed(2));
    }
  });
  if (props.savedLineItems && props.savedLineItems.length > 0) {
    savedLineItemsRef.value = JSON.parse(JSON.stringify(props.savedLineItems));
    savedTaxRef.value = props.savedTax;
  }

  watchEffect(
    () => {
      if (lineItems.value.length > 0) {
        if (lineItemSum.value == parseFloat(total.value - taxAmount.value).toFixed(2)) {
          subTotalError.value = null
        } else {
          let msg = "Line items must sum to the total amount minus tax.";
          let delta = Math.round(((lineItemSum.value - (total.value - taxAmount.value)) + Number.EPSILON) * 100) / 100;
          if (isNaN(delta)) {
            delta = taxAmount.value - total.value;
          }
          if (delta > 0) {
            subTotalError.value = msg + " You are over by $" + delta.toFixed(2);
          } else {
            subTotalError.value = msg + " You are under by $" + Math.abs(delta.toFixed(2));
          }
        }
      } else {
        subTotalError.value = null;
      }
    }
  );

  const canCalculatePercentages = computed(() => {
    return lineItems.value.length > 0 && (lineItemSum.value == (total.value - taxAmount.value).toFixed(2));
  });
  watch(canCalculatePercentages, (value: Boolean) => {
    if (value && !showSavedLineItems.value) {
      calculatePercentages();
    }
  });
  const calculatePercentages = () => {
    savedLineItemsRef.value = JSON.parse(JSON.stringify(lineItems.value));
    savedTaxRef.value = taxAmount.value;

    let subTotal = lineItemSum.value;
    catsRef.value = [];

    let catTotals = {
      new: []
    };
    lineItems.value.forEach((li: LineItem) => {
      let price = parseFloat(li.price);
      if (! li.cat_data.cat_id) {
        catTotals.new.push({
          sub_total: price,
          cat_data: li.cat_data,
        });
      } else {
        if (! catTotals[li.cat_data.cat_id]) {
          catTotals[li.cat_data.cat_id] = {
            sub_total: price,
            cat_data: li.cat_data,
          };
        } else {
          catTotals[li.cat_data.cat_id].sub_total += price;
        }
      }
    });
    for (let catId in catTotals) {
      if (catId === 'new') {
        continue;
      }
      let catSubTotal = catTotals[catId].sub_total;
      let catData = catTotals[catId].cat_data;

      catsRef.value.push({
        _key: randomUUID(),
        "cat_data": {
          cat_id: catData.cat_id,
          name: catData.name,
          cat_type_id: catData.cat_type_id,
          cat_type_name: catData.cat_type_name,
          hex_color: catData.hex_color,
          cat_subtype_id: catData.cat_subtype_id,
        },
        percent: Math.round(((catSubTotal / subTotal) * 100 + Number.EPSILON) * 100) / 100,
      });
    }
    // add the to be created cats
    catTotals.new.forEach((c) => {
      catsRef.value.push({
        _key: randomUUID(),
        "cat_data": {
          cat_id: c.cat_data.cat_id,
          name: c.cat_data.name,
          cat_type_id: c.cat_data.cat_type_id,
          cat_type_name: c.cat_data.cat_type_name,
          hex_color: c.cat_data.hex_color,
          cat_subtype_id: c.cat_data.cat_subtype_id,
        },
        percent: Math.round(((c.sub_total / subTotal) * 100 + Number.EPSILON) * 100) / 100,
      });
    });
    calcCatsByReciept.value = false;
    showSavedLineItems.value = false;
    emitLineItems();
  };

  const emitLineItems = () => {
    if (savedLineItemsRef.value.length > 0) {
      emit('line-items-update', {
        lineItems: JSON.parse(JSON.stringify(savedLineItemsRef.value)),
        tax: savedTaxRef.value,
      });
    } else {
      emit('line-items-update', {
        lineItems: [],
        tax: null,
      });
    }
  };

  const viewSavedLineItems = () => {
    lineItems.value = JSON.parse(JSON.stringify(savedLineItemsRef.value));
    taxAmount.value = savedTaxRef.value != null ? String(savedTaxRef.value) : null;
    showSavedLineItems.value = true;
    calcCatsByReciept.value = true;
  };
  const addLineItem = () => {
    let lastEnteredCatIndex = 0;
    if (lineItems.value.length > 0) {
      lastEnteredCatIndex = props.availableCategories.findIndex((c) => c.cat_id === lineItems.value[lineItems.value.length - 1].cat_data.cat_id);
    }
    if (lastEnteredCatIndex < 0) {
      lastEnteredCatIndex = 0;
    }
    lineItems.value.push({
      _key: randomUUID(),
      "cat_data": {
        cat_id: props.availableCategories[lastEnteredCatIndex].cat_id,
        name: props.availableCategories[lastEnteredCatIndex].name,
        cat_type_id: props.availableCategories[lastEnteredCatIndex].cat_type_id,
        cat_type_name: props.availableCategories[lastEnteredCatIndex].cat_type_name,
        hex_color: props.availableCategories[lastEnteredCatIndex].hex_color,
        cat_subtype_id: props.availableCategories[lastEnteredCatIndex].cat_subtype_id,
      },
      price: null,
    });
    focusElement(getUuid('line-item-price', lineItems.value.length - 1));
  };
  const removeLineItem = (i) => {
    lineItems.value.splice(i, 1);
  };
  const createLineItemCategory = (i, data) => {
    lineItems.value[i].cat_data.name = data.name;
    lineItems.value[i].cat_data.hex_color = data.hex_color;
    lineItems.value[i].cat_data.cat_type_id = data.type;
    lineItems.value[i].cat_data.cat_subtype_id = data.subtype;
  };
  const createANewLineItemCategory = () => {
    lineItems.value.push({
      _key: randomUUID(),
      "cat_data" : {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: '#000000',
        cat_subtype_id: null,
      },
      price: null,
    });
  };

  /*
   * Percent logic
   */
  const addKeyToCat = (cat) => ({ ...cat, _key: randomUUID() });
  const catsRef = ref(props.categories.map(addKeyToCat));
  watch(() => props.categories, () => catsRef.value = props.categories.map(addKeyToCat));

  const percentError = ref(null);
  const percentTotal = computed(() => {
    let percentTotal = 0;
    catsRef.value.forEach((c) => {
      percentTotal += c.percent;
    });
    return Math.round((percentTotal + Number.EPSILON) * 100) / 100
  });
  const getError = (i) => {
    if (props.errors[`categories.${i}.cat_data.name`]) {
      return {name: props.errors[`categories.${i}.cat_data.name`]};
    }
  };
  watch(
    percentTotal,
    (p) => {
      if  (p === 100 || p === 0) {
        percentError.value = null
      } else if (p > 100) {
        percentError.value = "Percentages must sum to 100%. You are over by " + Math.round(((p - 100) + Number.EPSILON) * 100) / 100;
      } else {
        percentError.value = "Percentages must sum to 100%. You are under by " + Math.round(((100 - p) + Number.EPSILON) * 100) / 100;
      }
    }
  );

  const catSelectBorder = (cat: Category) => {
    return `border: solid ${cat.cat_data.hex_color}; border-radius: 5px;`;
  };
  const uuid = randomUUID();
  const getUuid = (el, i = 0) => {
    return `${el}-${i}-${uuid}`;
  };
  const catChange = () => {
    if (percentTotal.value === 100 || catsRef.value.length === 0) {
      emit('category-update', catsRef);
    } else {
      emit('invalid-category-state');
    }
  };

  const catSelected = (catRefId) => {
    focusElement(getUuid('category-percent', catRefId), true);
  };

  const removeCategory = (i) => {
    catsRef.value.splice(i, 1);
  };
  const addCategory = () => {
    catsRef.value.push({
      _key: randomUUID(),
      "cat_data": {
        cat_id: filteredCats.value[0].cat_id,
        name: filteredCats.value[0].name,
        cat_type_id: filteredCats.value[0].cat_type_id,
        cat_type_name: filteredCats.value[0].cat_type_name,
        hex_color: filteredCats.value[0].hex_color,
        cat_subtype_id: filteredCats.value[0].cat_subtype_id,
      },
      percent: 0,
    });
    focusElement(getUuid('category-select', catsRef.value.length - 1));
  };
  const createCategory = (i, data) => {
    catsRef.value[i].cat_data.name = data.name;
    catsRef.value[i].cat_data.hex_color = data.hex_color;
    catsRef.value[i].cat_data.cat_type_id = data.type;
    catsRef.value[i].cat_data.cat_subtype_id = data.subtype;
  };
  const createANewCategory = () => {
    catsRef.value.push({
      _key: randomUUID(),
      "cat_data" : {
        cat_id: null,
        name: null,
        cat_type_id: null,
        cat_type_name: null,
        hex_color: '#000000',
        cat_subtype_id: null,
      },
      percent: 0,
    });
  };
  watch(
    catsRef, catChange,
    {
      deep: true
    }
  );
  const matchCategory = (suggestedCategory) => {
    if (! suggestedCategory) return null;
    const lower = suggestedCategory.toLowerCase();
    return props.availableCategories.find(c =>
      c.name.toLowerCase() === lower
    ) || null;
  };

  watch(() => props.aiAnalysis, (analysis) => {
    if (! analysis || ! analysis.line_items || analysis.line_items.length === 0) return;

    calcCatsByReciept.value = true;
    lineItems.value = [];
    taxAmount.value = analysis.tax != null ? String(analysis.tax) : null;

    analysis.line_items.forEach((item: any) => {
      const match = matchCategory(item.suggested_category);
      lineItems.value.push({
        _key: randomUUID(),
        cat_data: {
          cat_id: match ? match.cat_id : null,
          name: match ? match.name : (item.suggested_category || null),
          cat_type_id: match ? match.cat_type_id : null,
          cat_type_name: match ? match.cat_type_name : null,
          hex_color: match ? match.hex_color : '#000000',
        },
        price: String(item.price),
      });
    });

    focusElement(getUuid('tax-amount'), true);
  }, { deep: true });

  const receiptToggleEventHandler = (value) => {
    if (value) {
      focusElement(getUuid('tax-amount'), true);
    }
  };

  watch(taxAmount, (newVal) => {
    if (showSavedLineItems.value && calcCatsByReciept.value && lineItems.value.length > 0) {
      savedLineItemsRef.value = JSON.parse(JSON.stringify(lineItems.value));
      savedTaxRef.value = newVal;
      emitLineItems();
    }
  });
  const toggleSavedLineItems = async (val) => {
    showSavedLineItems.value = val;
    if (val) {
      calcCatsByReciept.value = true;
      await nextTick();
      lineItems.value = JSON.parse(JSON.stringify(savedLineItemsRef.value));
      taxAmount.value = savedTaxRef.value != null ? String(savedTaxRef.value) : null;
    } else if (calcCatsByReciept.value) {
      calcCatsByReciept.value = false;
      lineItems.value = [];
      taxAmount.value = null;
    }
  };
</script>

<template>
  <div class="ml-4">
    <template v-if="! calcCatsByReciept">
      <ToggleSlider
        v-if="hasSavedLineItems"
        :model-value="showSavedLineItems"
        @update:model-value="toggleSavedLineItems"
        label="Show line items"
        class="ml-2"
      />
      <ToggleSlider
        v-else
        v-model="calcCatsByReciept"
        @update:model-value="receiptToggleEventHandler"
        label="Enter receipt line items"
        class="ml-2"
      />
    </template>
    <template v-else>
      <ToggleSlider
        v-if="showSavedLineItems"
        :model-value="showSavedLineItems"
        @update:model-value="toggleSavedLineItems"
        label="Show line items"
        class="ml-2"
      />
      <ToggleSlider
        v-else
        v-model="calcCatsByReciept"
        @update:model-value="receiptToggleEventHandler"
        label="Enter receipt line items"
        class="ml-2"
      />
    </template>

    <template v-if="! calcCatsByReciept">
      <div class="max-w-7xl  sm:px-6 lg:px-8">
        <div class="bg-slate-500 sm:rounded-lg">
          <SecondaryButton
            :id="getUuid('add-cat-button')"
            type="button"
            @click="addCategory"
          >
            Add an Existing Cat
          </SecondaryButton>
          <SecondaryButton
            :id="getUuid('create-cat-button')"
            type="button"
            @click="createANewCategory"
          >
            Create a New Cat
          </SecondaryButton>
        </div>
      </div>

      <InputError :message="percentError" />
      <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
        <div
          class="flex flex-wrap bg-slate-500 ml-5 mr-4"
          :class="{'border border-red-600 dark:border-red-400': percentError}"
        >
          <div
            v-for="(category, i) in catsRef"
            :key="category._key"
            class="m-2"
          >
            <template v-if="category.cat_data.cat_id">
              <CategorySelect
                :select-id="getUuid('category-select-line-item', i)"
                :available-categories="filteredCats"
                v-model="catsRef[i].cat_data"
                @update:model-value="catSelected(i)"
              />
            </template>

            <template v-else>
              <CategoryInputs
                :id="getUuid('category-inputs', i)"
                :type="category.cat_data.cat_type_id"
                :name="category.cat_data.name"
                :color="category.cat_data.hex_color"
                :category-types="categoryTypes"
                :category-subtypes="categorySubtypes"
                :errors="getError(i)"
                @field-update="(data) => createCategory(i, data)"
              />
            </template>

            <InputLabel
              :for="getUuid('category-percent', i)"
              value="Percentage of Transaction Total (%)"
              class="mt-2"
            />
            <input
              :id="getUuid('category-percent', i)"
              type="number"
              min=".1"
              step=".01"
              v-model="catsRef[i].percent"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block  p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
              :style="catSelectBorder(category)"
              @keypress="forceNumericalInput($event)"
            >
            <DangerButton
              class="max-h-1 "
              type="button"
              @click="removeCategory(i)"
            >
              Remove
            </DangerButton>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="ml-6 flex flex-col md:flex-row content-between dark:bg-slate-500">
        <div class="m-1">
          <CurrencyInput
            :input-id="getUuid('tax-amount')"
            label="Tax"
            v-model="taxAmount"
          />
        </div>
        <div
          v-if="! total || ! taxAmount"
          class="m-1 p-2"
        >
          <p v-if="! total && ! taxAmount">
            Please enter the transaction total and tax paid
          </p>
          <p v-else-if="! total">
            Please enter the transaction total
          </p>
          <p v-else>
            Please enter the tax paid
          </p>
        </div>
        <template v-else>
          <div class="m-1 p-2">
            <SecondaryButton
              :id="getUuid('add-line-item-button')"
              type="button"
              @click="addLineItem"
            >
              Add a line item
            </SecondaryButton>

            <SecondaryButton
              class="m-4"
              type="button"
              @click="createANewLineItemCategory"
            >
              Add a line item and create a new category
            </SecondaryButton>
          </div>
        </template>
      </div>

      <PrimaryButton
        :id="getUuid('calculate-percentages-button')"
        v-if="canCalculatePercentages"
        type="button"
        @click="calculatePercentages"
      >
        {{ showSavedLineItems ? 'Recalculate Percentages' : 'Calculate Percentages' }}
      </PrimaryButton>

      <InputError :message="subTotalError" />
      <div class="flex flex-col md:flex-row content-between dark:bg-slate-500">
        <div
          class="flex flex-wrap bg-slate-500 ml-4 mr-4"
          :class="{'border border-red-600 dark:border-red-400': subTotalError}"
        >
          <div
            v-for="(item, i) in lineItems"
            :key="item._key"
            class="m-2"
          >
            <template v-if="item.cat_data.cat_id">
              <CategorySelect
                :select-id="getUuid('category-select', i)"
                :available-categories="availableCategories"
                v-model="lineItems[i].cat_data"
              />
            </template>

            <template v-else>
              <CategoryInputs
                :id="getUuid('category-inputs', i)"
                :type="item.cat_data.cat_type_id"
                :name="item.cat_data.name"
                :color="item.cat_data.hex_color"
                :category-types="categoryTypes"
                :category-subtypes="categorySubtypes"
                :errors="getError(i)"
                @field-update="(data) => createLineItemCategory(i, data)"
              />
            </template>

            <CurrencyInput
              label="Line Item Price"
              :input-id="getUuid('line-item-price', i)"
              v-model="lineItems[i].price"
              :extra-classes="catSelectBorder(item)"
            />

            <DangerButton
              class="max-h-1"
              type="button"
              @click="removeLineItem(i)"
            >
              Remove
            </DangerButton>
          </div>
          <div class="flex flex-col items-start">
            <SecondaryButton
              v-if="lineItems.length > 0"
              :id="getUuid('add-line-item-toggle-button')"
              class="h-6 m-4"
              type="button"
              @click="addLineItem"
            >
              +
            </SecondaryButton>
            <SecondaryButton
              v-if="lineItems.length > 0"
              class="h-6 mx-4 mb-4"
              type="button"
              @click="createANewLineItemCategory"
            >
              + &amp; create category
            </SecondaryButton>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
