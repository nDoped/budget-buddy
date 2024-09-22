  import {
    nextTick,
  } from 'vue';

export const forceNumericalInput = (evt) => {
  if (! evt.key.match(/^[0-9.]*$/)) {
    evt.preventDefault();
  }
};

export const forceMonetaryInput = (event, allowNegativeValues = false, currencyCode = "USD") => {
  const currencyRegexs = {
    USD: {
      full: /^-?[,0-9]*\.[0-9]{2}$/,
      allowedSymbols: /^[-,.0-9]*$/,
    }
  };
  if (!currencyRegexs[currencyCode]) {
    console.error(`Currency code ${currencyCode} not supported`);
    return;
  }
  if (! event.key.match(currencyRegexs[currencyCode].allowedSymbols)) {
    event.preventDefault();

  } else {
    // only allow one decimal point
    if (event.key === '.' && event.target.value.includes('.')) {
      event.preventDefault();

    // negative sign can only be first character
    } else if (event.key === '-'
      && (event.target.value || ! allowNegativeValues)
    ) {
      event.preventDefault();

    // only allow two decimal places
    } else if (event.target.value.match(currencyRegexs[currencyCode].full)) {

      // We have two decimals, so only block if the user is trying to
      // to add a thousandths place... ie. allow edits to the whole value
      if (event.target.selectionStart >= event.target.value.length - 2) {
        event.preventDefault();
      }
    }
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
