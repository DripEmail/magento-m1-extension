import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Given('I have configured a simple widget for {string} in the admin site', function (site) {
  cy.log('Creating a simple widget via the admin pages so observers get called')
  cy.visit('http://main.magento.localhost:3005/index.php/admin/')
  cy.contains('Catalog').trigger('mouseover')
  cy.contains('Manage Products').click()
  cy.contains('Add Product').click()
  cy.contains('Continue').click()

  cy.contains('Country of Manufacture')
  cy.get('input[id="name"]').type('Thing-a\'-mah-bob')
  cy.get('textarea[id="description"]').type('An authentic thing-a\'-mah-bob')
  cy.get('textarea[id="short_description"]').type('thing-a\'-mah-bob')
  cy.get('input[id="sku"]').type('thing-1')
  cy.get('input[id="weight"]').type('0')
  cy.get('select[id="status"]').select('1')
  cy.get('select[id="visibility"]').select('4')

  cy.get('a[title="Prices"]').click()
  cy.contains("Tax Class")
  cy.get('input[id="price"]').type('0.13')
  cy.get('select[id="tax_class_id"]').select('0')

  cy.get('a[title="Inventory"]').click()
  cy.contains('Stock Availability')
  cy.get('input[id="inventory_qty"]').type('13')

  if(site === 'site1') {
    cy.get('a[title="Websites"]').click()
    cy.contains('site1_store_view')
    cy.get('input[id="product_website_100"]').check()
  }

  cy.get('a[title="Categories"]').click()
  cy.contains("Default Category (0)")
  cy.get('div.active-category > input[type="checkbox"]').check()

  cy.wrap(Mockclient.reset())
  cy.get('button[title="Save"]').click()
  cy.contains("Add Product")
})

Given('I have changed the price for the simple widget in the admin site', function () {
  cy.log('Creating a simple widget via the admin pages so observers get called')
  cy.visit('http://main.magento.localhost:3005/index.php/admin/')
  cy.contains('Catalog').trigger('mouseover')
  cy.contains('Manage Products').click()
  cy.contains('Thing-a\'-mah-bob')
  cy.contains('Edit').click()

  cy.get('a[title="Prices"]').click()

  cy.contains("Tax Class")
  cy.get('input[id="price"]').clear().type('13.13')

  cy.wrap(Mockclient.reset())
  cy.get('button[title="Save"]').click()

  // signals that the page transitioned and the "save" is done.
  cy.contains("Add Product")
})

When('I delete the {string} widget in the admin site', function(type) {
  cy.log('Deleting a previously created ' + type + ' widget via the admin pages so observers get called')
  cy.visit('http://main.magento.localhost:3005/index.php/admin/')
  cy.contains('Catalog').trigger('mouseover')
  cy.contains('Manage Products').click()
  cy.contains('Thing-a\'-mah-bob')
  cy.get('.massaction-checkbox').first().check()
  cy.get('#productGrid_massaction-select').select('delete')

  cy.wrap(Mockclient.reset())
  cy.get('button[title="Submit"').click()
  cy.wait(100)
})

Then('A simple product {string} event should be sent to Drip for {string}', function (operation, site) {
  cy.log('Validating that the product event call happened and has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/product'
  })).then(function (recordedRequests) {
    const expectedPrice = (operation === 'created' ? 0.13 : 13.13)
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq(operation)
    expect(body.provider).to.eq('magento')
    expect(body.product_id).to.eq('1')
    expect(body.product_variant_id).to.eq('1')
    expect(body.sku).to.eq('thing-1')
    expect(body.name).to.eq('Thing-a\'-mah-bob')
    expect(body.price).to.eq(expectedPrice)
    if(operation !== 'deleted') {
      // because we send only the minimum amount of information for deleted events.
      expect(body.inventory).to.eq(13)
      expect(body.product_url).to.startWith('http://' + site + '.magento.localhost:3005/')
      expect(body.product_url).to.contain('/thing-a-mah-bob')
      const categories = body.categories
      expect(categories).to.have.lengthOf(1)
      expect(categories[0]).to.equal('Default Category')
    }
  })
})
