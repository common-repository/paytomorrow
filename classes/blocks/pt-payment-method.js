const settings = window.wc.wcSettings.getSetting('paymentMethodData', {'paytomorrow': {}})['paytomorrow'];
const label = window.wp.htmlEntities.decodeEntities(settings.title) || window.wp.i18n.__('Paytomorrow', 'paytomorrow');
const Content = () => {
    return window.wp.htmlEntities.decodeEntities(settings.description || '');
};

const PaymentMethodInfo = () => {
    return window.wp.element.createElement(
        'div',
        {className: ''},
        'PayTomorrow offers Fair Financing for All Credit Types. Simply select PayTomorrow, supply some basic information via our secure application process and get instantly approved to complete your purchase. Applying to PayTomorrow will not affect your credit score. '
    );
}

const Block_Gateway = {
    name: 'paytomorrow',
    label: window.wp.element.createElement(
        "img",
        {
            src: settings['icon'] ,alt: 'Paytomorrow Payment'
        },
        null
    ),
    description: '',
    content: Object(window.wp.element.createElement)(PaymentMethodInfo, null),
    edit: Object(window.wp.element.createElement)(Content, null),
    canMakePayment: () => settings['enabled'],
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};


window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
