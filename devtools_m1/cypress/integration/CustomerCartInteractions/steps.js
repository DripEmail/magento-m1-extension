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
    case 'configured':
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
    }, 1, 1);
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
    const item = body.items[0]
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.sku).to.eq('widg-1')
    expect(body.items).to.have.lengthOf(1)
  })
})

Then('A configured cart event should be sent to Drip', function() {
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
