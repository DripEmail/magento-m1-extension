import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Given('I have configured a simple product for {string} in the admin site', function (site) {
  cy.log('Creating a simple widget via the admin pages so observers get called')
  navigateToProductManagement()
  cy.contains('Add Product').click()
  cy.contains('Continue').click()

  fillInProductGeneralInfo()
  fillInProductPriceInfo()
  fillInProductInventoryInfo()

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

Given('I have changed the price for the simple product in the admin site', function () {
  cy.log('Creating a simple widget via the admin pages so observers get called')
  navigateToProductManagement()
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

Given('I delete the {string} product in the admin site', function(type) {
  cy.log('Deleting a previously created ' + type + ' widget via the admin pages so observers get called')
  navigateToProductManagement()
  cy.contains('Thing-a\'-mah-bob')
  cy.get('.massaction-checkbox').first().check()
  cy.get('#productGrid_massaction-select').select('delete')

  cy.wrap(Mockclient.reset())
  cy.get('button[title="Submit"').click()
  cy.wait(100)
})

Given('I have configured a configurable product for {string} in the admin site', function(site) {
  cy.log('Creating a configurable product via the admin pages so observers get called')
  createBaseConfigurableProduct(site)
})

Then('A {string} product {string} event should be sent to Drip for {string}', function (type, operation, site) {
  cy.log('Validating that the product event call happened and has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/product'
  })).then(function (recordedRequests) {
    var expectedVariants = (type === 'configurable' ? ['Classic', 'Modern', 'Contemporary', '1'] : ['1'])
    if(type === 'configurable') {
      expect(recordedRequests).to.have.lengthOf(5) // four created, one updated
      for(var index = 0; index < recordedRequests.length; index++) {
        var body = JSON.parse(recordedRequests[index].body.string)
        if(operation === 'created' && body.action !== 'updated') { // BLEH!  This only happens for configurables
          checkProductEventInformation(true, site, operation, expectedVariants, body)
        }
      }
    } else {
      expect(recordedRequests).to.have.lengthOf(1)
      const body = JSON.parse(recordedRequests[0].body.string)
      checkProductEventInformation(false, site, operation, expectedVariants, body)
    }
    expect(expectedVariants).to.have.lengthOf(0)
  })
})

const navigateToProductManagement = function() {
  cy.visit('http://main.magento.localhost:3005/index.php/admin/')
  cy.contains('Catalog').trigger('mouseover')
  cy.contains('Manage Products').click()
}

const fillInProductGeneralInfo = function(isSimpleProduct = true) {
  cy.contains('Country of Manufacture')
  cy.get('input[id="name"]').type('Thing-a\'-mah-bob')
  cy.get('textarea[id="description"]').type('An authentic thing-a\'-mah-bob')
  cy.get('textarea[id="short_description"]').type('thing-a\'-mah-bob')
  cy.get('input[id="sku"]').type('thing-1')
  if(isSimpleProduct) {
    cy.get('input[id="weight"]').type('0')
  }
  cy.get('select[id="status"]').select('1')
  cy.get('select[id="visibility"]').select('4')
}

const fillInProductPriceInfo = function() {
  cy.get('a[title="Prices"]').click()
  cy.contains("Tax Class")
  cy.get('input[id="price"]').type('0.13')
  cy.get('select[id="tax_class_id"]').select('0')
}

const fillInProductInventoryInfo = function(isSimpleProduct = true) {
  cy.get('a[title="Inventory"]').click()
  cy.contains('Stock Availability')
  if(isSimpleProduct) {
    cy.get('input[id="inventory_qty"]').type('13')
  } else {
    cy.get('select[id="inventory_stock_availability"]').select('1')
  }
}

const createBaseConfigurableProduct = function(site) {
  navigateToProductManagement()

  cy.contains('Add Product').click()
  cy.contains('Continue')

  cy.get('select[id="product_type"]').select('configurable')
  cy.get('button[title="Continue"]').click()

  cy.get('input[title="thingamabobattr"]').check()
  cy.get('button[title="Continue"]').click()

  fillInProductGeneralInfo(false)
  fillInProductPriceInfo()
  fillInProductInventoryInfo(false)

  if(site === 'site1') {
    cy.get('a[title="Websites"]').click()
    cy.contains('site1_store_view')
    cy.get('input[id="product_website_100"]').check()
  }

  cy.get('a[title="Categories"]').click()
  cy.contains("Default Category (0)")
  cy.get('div.active-category > input[type="checkbox"]').check()

  cy.wrap(Mockclient.reset())
  cy.contains('Save and Continue Edit').click()
  cy.contains('The product has been saved.')

  cy.get('a[title="Associated Products"]').click()
  cy.wrap(['Classic', 'Modern', 'Contemporary']).each(function(variant) {
    cy.get('input[id="simple_product_weight"]').clear().type('0.13')
    cy.get('select[id="simple_product_status"]').select('1')
    cy.get('select[id="simple_product_visibility"]').select("4")
    cy.get('select[id="simple_product_thingamahattribute"]').select(variant)
    cy.get('input[id="simple_product_inventory_qty"]').clear().type("13")
    cy.get('select[id="simple_product_inventory_is_in_stock"]').select("1")
    cy.contains('Quick Create').click()
    cy.contains('Thing-a\'-mah-bob-' + variant)
  })

  cy.contains("Save").click()
  cy.wait(100)
  cy.contains('The product has been saved.')
}

const checkProductEventInformation = function(is_configurable, site, operation, expectedVariants, body) {
  const expectedPrice = (operation === 'created' ? 0.13 : 13.13)
  const expectedInventory = ((is_configurable && body.product_variant_id === '1' )? 0 : 13) // configurable products have no inventory
  const variantId = body.product_variant_id
  const variants = ['Classic', 'Modern', 'Contemporary']

  expect(body.action).to.eq(operation)
  expect(body.provider).to.eq('magento')
  expect(body.product_id).to.eq('1')
  expect(expectedVariants).to.contain(variantId)

  expect(body.sku).to.eq('thing-1' + (variantId === '1' ? '' : '-' + variants[variantId - 2]))
  expect(body.name).to.eq('Thing-a\'-mah-bob')
  expect(body.price).to.eq(expectedPrice)

  if(operation !== 'deleted') {
    // because we send only the minimum amount of information for deleted events.
    expect(body.inventory).to.eq(expectedInventory)
    expect(body.product_url).to.startWith('http://' + site + '.magento.localhost:3005/')
    expect(body.product_url).to.contain('/thing-a-mah-bob')
    const categories = body.categories
    expect(categories).to.have.lengthOf(1)
    expect(categories[0]).to.equal('Default Category')
  }
  
  var variantIndex = expectedVariants.indexOf(variantId)
  expect(variantIndex).to.be.greaterThan(-1)
  expectedVariants.splice(variantIndex, 1)
}
