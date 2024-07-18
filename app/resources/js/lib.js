export const forceNumericalInput = (evt) => {
  if (! evt.key.match(/^[0-9.]*$/)) {
    evt.preventDefault();
  }
};
