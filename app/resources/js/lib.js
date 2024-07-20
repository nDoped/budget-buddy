  import {
    nextTick,
  } from 'vue';

export const forceNumericalInput = (evt) => {
  if (! evt.key.match(/^[0-9.]*$/)) {
    evt.preventDefault();
  }
};

export  const focusElement = (id, select = false) => {
  nextTick(() => {
    const element = document.getElementById(id);
    if (element) {
      element.focus();
      if (select) {
        element.select();
      }
    }
  });
};
