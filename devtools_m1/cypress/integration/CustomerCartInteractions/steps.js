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

When('I add something to my cart', function() {
  // For some reason, Magento throws an error here in JS. We don't really care, so ignore it.
  cy.on('uncaught:exception', (err, runnable) => {
    return false
  })
  cy.visit(`/widget-1.html`)
  cy.get('#product-options-wrapper select').select('XL')
  cy.contains('Add to Cart').click()
})

Then('A cart event should be sent to Drip', function() {
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
  cy.log('Validating that the cart call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/cart'
  })).then(function(recordedRequests) {
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.email).to.eq('testuser@example.com')
    const item = body.items[0]
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('2')
    expect(item.sku).to.eq('widg-1-XL')
    expect(body.items).to.have.lengthOf(1)
  })
})
