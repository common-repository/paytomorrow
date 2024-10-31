window.addEventListener('DOMContentLoaded', (event) => {
  if (mpeSettings.mpeOptions.mpe !== "no") {
    PayTomorrow.mpeInit({
      debugMode: mpeSettings.mpeOptions.debug_mpe === "yes",
      enableMoreInfoLink: mpeSettings.mpeOptions.enableMoreInfoLink_mpe === "yes",
      logoColor: mpeSettings.mpeOptions.logoColor_mpe,
      maxAmount: mpeSettings.mpeOptions.maxAmount_mpe,
      maxTerm: mpeSettings.mpeOptions.maxTerm_mpe,
      minAmount: mpeSettings.mpeOptions.minAmount_mpe,
      mpeSelector: mpeSettings.mpeOptions.mpeSelector_mpe && mpeSettings.mpeOptions.mpeSelector_mpe.replace("&gt;", ">"),
      priceSelector: mpeSettings.mpeOptions.priceSelector_mpe && mpeSettings.mpeOptions.priceSelector_mpe.replace("&gt;", ">"),
      storeDisplayName: mpeSettings.mpeOptions.storeDisplayName_mpe,
      publicId: mpeSettings.mpeOptions.publicId_mpe,
      displayMicroOffers: mpeSettings.mpeOptions.display_micro_offer_mpe === "yes",
      displayPrimeOffers: mpeSettings.mpeOptions.display_prime_offer_mpe === "yes",
      maxMicroAmount: mpeSettings.mpeOptions.max_micro_amount_mpe,
      primeApr: mpeSettings.mpeOptions.prime_apr_mpe
    })
  }
});
