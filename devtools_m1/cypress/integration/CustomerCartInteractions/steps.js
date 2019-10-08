import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

When('I create an account', function() {
  cy.contains('Register').click({ force: true })
  cy.get('#form-validate').within(function() {
    cy.get('input[name="firstname"]').type('Test')
    cy.get('input[name="lastname"]').type('User')
    cy.get('input[name="email"]').type('testuser@example.com')
    cy.get('input[name="password"]').type('blahblah123!!!')
    cy.get('input[name="confirmation"]').type('blahblah123!!!')
    cy.contains('Register').click()
  })
})

When('I add a {string} widget to my cart', function(type) {
  // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
  cy.on('uncaught:exception', (err, runnable) => {
    return false
  })
  cy.visit(`/widget-1.html`)
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
When('I add a different {string} widget to my cart', function(type) {
  // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
  cy.on('uncaught:exception', (err, runnable) => {
    return false
  })
  cy.visit(`/widget-1.html`)
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

// This is extracting some common assertions that will likely differ soon.
function checkBasicCartEvents() {
  cy.log('Validating subscriber mocks were called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v2/123456/subscribers'
    }, 1, 1);
  })
  cy.log('Validating event mocks were called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v2/123456/events'
    }, 2, 2);
  })
  cy.log('Validating cart mock was called')
  cy.then(function() {
    return Mockclient.verify({
      'path': '/v3/123456/shopper_activity/cart'
    }, 1, 2);
  })
}

Then('A simple cart event should be sent to Drip', function() {
  checkBasicCartEvents()
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    expect(body.action).to.eq('created')
    expect(body.cart_id).to.eq('1')
    expect(body.cart_url).to.startWith('http://main.magento.localhost:3005/drip/cart/index/q/1')
    expect(body.currency).to.eq('USD')
    expect(body.grand_total).to.eq(11.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.magento_source).to.eq('Storefront')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.2, Drip Extension \d+\.\d+\.\d+$/)
    expect(body.items).to.have.lengthOf(1)

    const item = body.items[0]
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1')
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3005/media/catalog/product/')
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_url).to.eq('http://main.magento.localhost:3005/widget-1.html')
    expect(item.quantity).to.eq(1)
    expect(item.total).to.eq(11.22)
  })
})

Then('A configurable cart event should be sent to Drip', function() {
  checkBasicCartEvents()
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1-xl')
    expect(body.items).to.have.lengthOf(1)
  })
})

Then('Configurable cart events should be sent to Drip', function() {
  checkBasicCartEvents()
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
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

Then('A grouped cart event should be sent to Drip', function() {
  checkBasicCartEvents()
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    const item1 = body.items[0]
    expect(item1.product_id).to.eq('2')
    expect(item1.product_variant_id).to.eq('2')
    expect(item1.sku).to.eq('widg-1-sub1')
    const item2 = body.items[1]
    expect(item2.product_id).to.eq('3')
    expect(item2.product_variant_id).to.eq('3')
    expect(item2.sku).to.eq('widg-1-sub2')
    expect(body.items).to.have.lengthOf(2)
  })
})

Then('A bundle cart event should be sent to Drip', function() {
  checkBasicCartEvents()
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')

    // We don't send anything unique for the child products right now.
    const item = body.items[0]
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('3')
    expect(item.sku).to.eq('widg-1')
    expect(body.items).to.have.lengthOf(1)
  })
})
