import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

When('I open the site1 homepage', function() {
  cy.visit(`http://site1.magento.localhost:3005/`)
})

When('I open the main homepage', function() {
  cy.visit(`http://main.magento.localhost:3005/`)
})

Then('clientjs is inserted', function() {
  cy.window().then(function(win) {
    expect(win._dcq).to.not.be.undefined
  })
})

Then('clientjs is not inserted', function() {
  cy.window().then(function(win) {
    expect(win._dcq).to.be.undefined
  })
})
