/* global expect */
/* global beforeEach */
/* global test */
import {mount} from "@vue/test-utils";
import TransactionCategory from '@/Components/TransactionCategory.vue';

let wrapper;
let props = {};
beforeEach(() => {
  props = structuredClone({
    totalAmount: "100",
    categories: [],
    categoryTypes: [
      {
        id: 1,
        name: "Food",
        hex_color: "#111111"
      },
      {
        id: 2,
        name: "Vehicle",
        hex_color: "#222222"
      },
    ],
    availableCategories: [
      {
        cat_id: 1,
        name: "Eggs",
        cat_type_id: 1,
        cat_type_name: "Food",
        hex_color: "#FF0000"
      },
      {
        cat_id: 2,
        name: "Bacon",
        cat_type_id: 1,
        cat_type_name: "Food",
        hex_color: "#FF3546"
      }
    ],
    errors: {}
  });
  wrapper = mount(TransactionCategory, { props: props });
});

test("test aiAnalysis populates line items with matching categories", async () => {
  expect(wrapper.vm.calcCatsByReciept).toBe(false);

  await wrapper.setProps({
    aiAnalysis: {
      store_name: "Grocery Store",
      line_items: [
        { description: "Eggs", price: 5.99, suggested_category: "Eggs" },
        { description: "Milk", price: 3.49, suggested_category: "Dairy" },
      ],
      subtotal: 9.48,
      tax: 0.76,
      total: 10.24,
    }
  });

  expect(wrapper.vm.calcCatsByReciept).toBe(true);
  expect(wrapper.vm.lineItems.length).toBe(2);
  expect(wrapper.vm.lineItems[0].price).toBe("5.99");
  expect(wrapper.vm.lineItems[0].cat_data.cat_id).toBe(1);
  expect(wrapper.vm.lineItems[0].cat_data.name).toBe("Eggs");
  expect(wrapper.vm.lineItems[1].price).toBe("3.49");
  expect(wrapper.vm.lineItems[1].cat_data.cat_id).toBeNull();
  expect(wrapper.vm.lineItems[1].cat_data.name).toBe("Dairy");
});

test("test aiAnalysis with no matching categories uses suggested name", async () => {
  await wrapper.setProps({
    aiAnalysis: {
      store_name: "Gas Station",
      line_items: [
        { description: "Gas", price: 45.00, suggested_category: "Transportation" },
      ],
      subtotal: 45.00,
      tax: 0,
      total: 45.00,
    }
  });

  expect(wrapper.vm.lineItems.length).toBe(1);
  expect(wrapper.vm.lineItems[0].price).toBe("45");
  expect(wrapper.vm.lineItems[0].cat_data.name).toBe("Transportation");
  expect(wrapper.vm.lineItems[0].cat_data.cat_id).toBeNull();
});

test("test aiAnalysis with null analysis does nothing", async () => {
  const beforeLineItems = wrapper.vm.lineItems.length;
  expect(wrapper.vm.calcCatsByReciept).toBe(false);

  await wrapper.setProps({
    aiAnalysis: null
  });

  expect(wrapper.vm.lineItems.length).toBe(beforeLineItems);
  expect(wrapper.vm.calcCatsByReciept).toBe(false);
});

test("test aiAnalysis with empty line items does nothing", async () => {
  await wrapper.setProps({
    aiAnalysis: {
      store_name: null,
      line_items: [],
      tax: null,
    }
  });

  expect(wrapper.vm.calcCatsByReciept).toBe(false);
});
