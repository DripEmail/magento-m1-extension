import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"
import { getCurrentFrontendDomain, getCurrentFrontendWebsiteId, getCurrentFrontendStoreViewId } from "../../lib/frontend_context"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function () {
  cy.contains('Register').click({ force: true })
  cy.get('#form-validate').within(function () {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="confirmation"]').type('blahblah123!!!')
    cy.contains('Register').click()
  })
})

When('I add a {string} widget to my cart', function (type) {
  // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
  cy.on('uncaught:exception', (err, runnable) => {
    return false
  })
  cy.visit(`${getCurrentFrontendDomain()}/widget-1.html`)
  switch (type) {
    case 'configurable':
      cy.get('#product-options-wrapper select').select('XL')
      break;
    case 'grouped':
      cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
      cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
      break;
    case 'simple':
    case 'bundle': // For now, we only have one option for each bundle option, so we don't have to do anything.
      // Do nothing
      break;
    default:
      throw 'Methinks thou hast forgotten something…'
  }
  cy.contains('Add to Cart').click()
})

// TODO: This is kind of ugly and duplicates the prior.
When('I add a different {string} widget to my cart', function (type) {
  // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
  cy.on('uncaught:exception', (err, runnable) => {
    return false
  })
  cy.visit(`${getCurrentFrontendDomain()}/widget-1.html`)
  switch (type) {
    case 'configurable':
      cy.get('#product-options-wrapper select').select('L')
      break;
    case 'grouped':
      cy.get('#product_addtocart_form input[name="super_group[2]"]').clear().type('1')
      cy.get('#product_addtocart_form input[name="super_group[3]"]').clear().type('1')
      break;
    case 'simple':
    case 'bundle': // For now, we only have one option for each bundle option, so we don't have to do anything.
      // Do nothing
      break;
    default:
      throw 'Methinks thou hast forgotten something…'
  }
  cy.contains('Add to Cart').click()
})

Then('A simple cart event should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    // Cucumber runs scenarios in a World object. Step definitions are run in the context of the current World instance. Data can be used between steps using the self prefix.
    self.cartUrl = body.cart_url
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable cart event should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1') // TODO: Figure out whether this is correct.
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable cart event with parent image and url should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/parent_image.png`)
    expect(item.name).to.eq('Widget 1') // TODO: Figure out whether this is correct.
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('Configurable cart events should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const body = JSON.parse(recordedRequests[recordedRequests.length - 1].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.items).to.have.lengthOf(2)
    const item1 = body.items[0]
    expect(item1.product_id).to.eq('3')
    expect(item1.product_variant_id).to.eq('1')
    expect(item1.sku).to.eq('widg-1-xl')
    const item2 = body.items[1]
    expect(item2.product_id).to.eq('3')
    expect(item2.product_variant_id).to.eq('2')
    expect(item2.sku).to.eq('widg-1-l')
  })
})

Then('A grouped cart event should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(22.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(2)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(2)

    // These may be in any order, so we'll loop and assert based on SKU.
    body.items.forEach(item => {
      switch (item.sku) {
        case 'widg-1-sub1':
          expect(item.product_id).to.eq('2')
          expect(item.product_variant_id).to.eq('2')
          expect(item.name).to.eq('Widget 1 Sub 1')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-1.html`)
          break;
        case 'widg-1-sub2':
          expect(item.product_id).to.eq('3')
          expect(item.product_variant_id).to.eq('3')
          expect(item.name).to.eq('Widget 1 Sub 2')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-2.html`)
          break;
        default:
          expect.fail(`Unknown SKU: ${item.sku}`)
          break;
      }
      expect(item.categories).to.be.empty
      expect(item.discounts).to.eq(0)
      expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
      expect(item.price).to.eq(11.22)
      expect(item.quantity).to.eq(1)
      expect(item.total).to.eq(11.22)
    });
  })
})

Then('A bundle cart event should be sent to Drip', function () {
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith(`${getCurrentFrontendDomain()}/drip/cart/index/q/1`)
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(22.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    // We don't send anything unique for the child products right now.
    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('3')
    expect(item.sku).to.eq('widg-1')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/my_image.png`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(22.44)
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(22.44)
  })
})

When('I check out', function () {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.contains('Proceed to Checkout').click()

  cy.get('input[name="billing[street][]"]:first').type('123 Main St.')
  cy.get('input[name="billing[city]"]').type('Centerville')
  cy.get('select[name="billing[region_id]"]').select('Minnesota')
  cy.get('input[name="billing[postcode]"]').type('12345')
  cy.get('input[name="billing[telephone]"]').type('999-999-9999')
  cy.contains('Continue').click()

  cy.contains('Flat Rate')
  cy.get('#shipping-method-buttons-container').contains('Continue').click()

  cy.contains('Check / Money order')
  cy.get('#checkout-step-payment').contains('Continue').click()

  cy.contains('Place Order').click()
  cy.contains('Your order has been received')
})

When('I begin check out as a guest', function () {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.contains('Proceed to Checkout').click()

  cy.contains('Continue').click()

  cy.get('input[id="billing:firstname"]').type('Test')
  cy.get('input[id="billing:lastname"]').type('User')
  cy.get('input[id="billing:email"]').type('testuser@example.com')
  cy.get('input[name="billing[street][]"]:first').type('123 Main St.')
  cy.get('input[name="billing[city]"]').type('Centerville')
  cy.get('select[name="billing[region_id]"]').select('Minnesota')
  cy.get('input[name="billing[postcode]"]').type('12345')
  cy.get('input[name="billing[telephone]"]').type('999-999-9999')
  cy.get('input[id="billing:use_for_shipping_yes"]').check()
  cy.get('button[onclick="billing.save()"]').click()
  cy.contains('Flat Rate')
})

When('I complete check out as a guest', function () {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.get('#shipping-method-buttons-container').contains('Continue').click()

  cy.contains('Check / Money order')
  cy.get('#checkout-step-payment').contains('Continue').click()

  cy.contains('Place Order').click()
  cy.contains('Your order has been received')
})

When('I logout', function () {
  cy.visit('/customer/account/logout')
})

function basicOrderBodyAssertions(body) {
  const storeViewId = getCurrentFrontendStoreViewId()

  expect(body.currency).to.eq('USD')
  expect(body.magento_source).to.eq('Storefront')
  expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
  expect(body.order_id).to.eq(`${storeViewId}00000001`)
  expect(body.order_public_id).to.eq(`${storeViewId}00000001`)
  expect(body.provider).to.eq('magento')
  expect(body.total_discounts).to.eq(0)
  expect(body.total_taxes).to.eq(0)
  expect(body.version).to.match(/^Magento 1\.9\.4\.3, Drip Extension \d+\.\d+\.\d+$/)

  expect(body.billing_address.address_1).to.eq('123 Main St.')
  expect(body.billing_address.address_2).to.eq('')
  expect(body.billing_address.city).to.eq('Centerville')
  expect(body.billing_address.company).to.eq('')
  expect(body.billing_address.country).to.eq('US')
  expect(body.billing_address.first_name).to.eq('Test')
  expect(body.billing_address.last_name).to.eq('User')
  expect(body.billing_address.phone).to.eq('999-999-9999')
  expect(body.billing_address.postal_code).to.eq('12345')
  expect(body.billing_address.state).to.eq('Minnesota')

  expect(body.shipping_address.address_1).to.eq('123 Main St.')
  expect(body.shipping_address.address_2).to.eq('')
  expect(body.shipping_address.city).to.eq('Centerville')
  expect(body.shipping_address.company).to.eq('')
  expect(body.shipping_address.country).to.eq('US')
  expect(body.shipping_address.first_name).to.eq('Test')
  expect(body.shipping_address.last_name).to.eq('User')
  expect(body.shipping_address.phone).to.eq('999-999-9999')
  expect(body.shipping_address.postal_code).to.eq('12345')
  expect(body.shipping_address.state).to.eq('Minnesota')
}

Then('A simple order event should be sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/(order|cart)'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(2)
    const orderRequests = recordedRequests.filter(function (req) {
      return req.path === '/v3/123456/shopper_activity/order';
    })
    const cartRequests = recordedRequests.filter(function (req) {
      return req.path === '/v3/123456/shopper_activity/cart';
    })
    expect(orderRequests).to.have.lengthOf(1)
    const body = JSON.parse(orderRequests[0].body.string)
    const cartBody = JSON.parse(cartRequests[0].body.string)
    expect(body.occurred_at).to.be.greaterThan(cartBody.occurred_at)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable order event should be sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

Then('A grouped order event should be sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(32.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(2)
    expect(body.total_shipping).to.eq(10)
    expect(body.items).to.have.lengthOf(2)

    basicOrderBodyAssertions(body)

    // These may be in any order, so we'll loop and assert based on SKU.
    body.items.forEach(item => {
      switch (item.sku) {
        case 'widg-1-sub1':
          expect(item.name).to.eq('Widget 1 Sub 1')
          expect(item.product_id).to.eq('2')
          expect(item.product_variant_id).to.eq('2')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-1.html`)
          break;
        case 'widg-1-sub2':
          expect(item.name).to.eq('Widget 1 Sub 2')
          expect(item.product_id).to.eq('3')
          expect(item.product_variant_id).to.eq('3')
          expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1-sub-2.html`)
          break;
        default:
          expect.fail(`Unknown SKU: ${item.sku}`)
          break;
      }
      expect(item.categories).to.be.empty
      expect(item.discounts).to.eq(0)
      expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
      expect(item.price).to.eq(11.22)
      expect(item.quantity).to.eq(1)
      expect(item.taxes).to.eq(0)
      expect(item.total).to.eq(11.22)
    });
  })
})

Then('A bundle order event should be sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('testuser@example.com')
    expect(body.grand_total).to.eq(27.44)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    basicOrderBodyAssertions(body)

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq(`${getCurrentFrontendDomain()}/media/catalog/product/`)
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(22.44)
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('3')
    expect(item.product_url).to.eq(`${getCurrentFrontendDomain()}/widget-1.html`)
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(22.44)
  })
})

When('I open the abandoned cart url', function(){
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())
  // To use this step, a previous step has to fill the abandonedCartUrl property. See 'A simple cart event should be sent to Drip' for an example.
  cy.visit(self.cartUrl)
})
