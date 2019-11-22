import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mapFrontendWebsiteId, mapFrontendStoreId } from "../../lib/frontend_context"

Given('I am logged into the admin interface', function() {
  cy.visit(`http://main.magento.localhost:3005/index.php/admin`)
  cy.get('input[name="login[username]"]').type('admin')
  cy.get('input[name="login[password]"]').type('abc1234567890')
  cy.contains('Login').click()
})

Given('I have set up a multi-store configuration', function() {
  cy.contains('System').trigger('mouseover')
  cy.contains('Configuration').click()
  cy.contains('Manage Stores').click({force: true})

  cy.contains('Create Website').click()
  cy.get('input[name="website[name]"]').type('site1_website')
  cy.get('input[name="website[code]"]').type('site1_website')
  cy.contains('Save Website').click()

  cy.contains('Create Store').click()
  cy.get('select[name="group[website_id]"]').select('site1_website')
  cy.get('input[name="group[name]"]').type('site1_store')
  cy.get('select[name="group[root_category_id]"]').select('Default Category')
  cy.contains('Save Store').click()

  cy.contains('Create Store View').click()
  cy.get('select[name="store[group_id]"]').select('site1_store')
  cy.get('input[name="store[name]"]').type('site1_store_view')
  cy.get('input[name="store[code]"]').type('site1_store_view')
  cy.get('select[name="store[is_active]"]').select('Enabled')
  cy.contains('Save Store').click()

  cy.contains('System').trigger('mouseover')
  cy.contains('Configuration').click()
  cy.get('ul#system_config_tabs').within(function() {
    cy.contains('Web').click()
  })
  cy.get('select#store_switcher').select('site1_website')
  cy.contains('Unsecure').click()
  cy.get('input[name="groups[unsecure][fields][base_url][inherit]"]').uncheck()
  cy.get('input[name="groups[unsecure][fields][base_url][value]"]').clear().type(`http://site1.magento.localhost:3005/`)
  cy.contains('Save Config').click()
})

Given('I have configured Drip to be enabled for {string}', function(site) {
  cy.contains('System').trigger('mouseover')
  cy.contains('Configuration').click()
  cy.contains('Drip Connect Configuration').click()
  let websiteKey
  switch (site) {
    case 'main':
      websiteKey = 'Main Website'
      break;
    case 'default':
      websiteKey = 'Default Config'
      break;
    default:
      websiteKey = `${site}_website`
      break;
  }
  cy.get('select#store_switcher').select(websiteKey)
  cy.contains('Module Settings').click()
  cy.contains('API Settings').click()
  if (site !== 'default') {
    cy.get('input[name="groups[module_settings][fields][is_enabled][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][account_id][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][api_key][inherit]"]').uncheck()
    cy.get('input[name="groups[api_settings][fields][url][inherit]"]').uncheck()
  }
  cy.get('select[name="groups[module_settings][fields][is_enabled][value]"]').select('Yes')
  cy.get('input[name="groups[api_settings][fields][account_id][value]"]').type('123456')
  cy.get('input[name="groups[api_settings][fields][api_key][value]"]').type('abc123')
  cy.get('input[name="groups[api_settings][fields][url][value]"]').clear().type('http://mock:1080/v2/')
  cy.contains('Save Config').click()
})

// Simple Product
Given('I have configured a simple widget for {string}', function(site) {
  cy.createProduct({
    "storeId": mapFrontendStoreId(site),
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "websiteIds": [mapFrontendWebsiteId(site)]
  })
})

// Configurable Product
Given('I have configured a configurable widget', function() {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "configurable",
    "attributes": {
      "widget_size": {
        "XL": {
          "sku": "widg-1-xl",
          "name": "Widget 1 XL",
          "description": "This is really an XL widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an XL widget.",
        },
        "L": {
          "sku": "widg-1-l",
          "name": "Widget 1 L",
          "description": "This is really an L widget. There are many like it, but this one is mine.",
          "shortDescription": "This is really an L widget.",
        }
      }
    }
  })
})

// Grouped Product
Given('I have configured a grouped widget', function() {
  cy.createProduct({
    "sku": "widg-1",
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "grouped",
    "associated": [
      {
        "sku": "widg-1-sub1",
        "name": "Widget 1 Sub 1",
        "description": "This is really a sub1 widget. There are many like it, but this one is mine.",
        "shortDescription": "This is really a sub1 widget.",
      },
      {
        "sku": "widg-1-sub2",
        "name": "Widget 1 Sub 2",
        "description": "This is really a sub2 widget. There are many like it, but this one is mine.",
        "shortDescription": "This is really a sub2 widget.",
      }
    ]
  })
})

// Bundle Product
Given('I have configured a bundle widget', function() {
  // skuType of 1 is a fixed sku rather than generating a composite SKU.
  cy.createProduct({
    "sku": "widg-1",
    "skuType": 1,
    "name": "Widget 1",
    "description": "This is really a widget. There are many like it, but this one is mine.",
    "shortDescription": "This is really a widget.",
    "typeId": "bundle",
    "bundle_options": [
      {
        "title": "item01",
        "product_options": [
          {
            "sku": "widg-1-sub1",
            "name": "Widget 1 Sub 1",
            "description": "This is really a sub1 widget. There are many like it, but this one is mine.",
            "shortDescription": "This is really a sub1 widget.",
          }
        ]
      },
      {
        "title": "item02",
        "product_options": [
          {
            "sku": "widg-1-sub2",
            "name": "Widget 1 Sub 2",
            "description": "This is really a sub2 widget. There are many like it, but this one is mine.",
            "shortDescription": "This is really a sub2 widget.",
          }
        ]
      }
    ]
  })
})

Given('a customer exists', function() {
  cy.createCustomer({})
})

When('I create an order', function() {
  cy.contains('Orders').click({force: true})
  cy.contains('Create New Order').click()

  // Select customer
  cy.contains('John Doe').click()

  // Add product to order
  cy.contains('Add Products').click()
  cy.contains('Widget 1').click()
  cy.get('#product_composite_configure').within(function() {
    cy.get('select[name="super_attribute[135]"]').select('XL')
    cy.contains('OK').click()
  })
  cy.contains('Add Selected Product(s) to Order').click()

  // Fill out shipping/billing addresses
  cy.get('input[name="order[billing_address][firstname]"]').type('John')
  cy.get('input[name="order[billing_address][lastname]"]').type('Doe')
  cy.get('input[name="order[billing_address][street][0]"]').type('123 Main St.')
  cy.get('input[name="order[billing_address][city]"]').type('Centerville')
  cy.get('select[name="order[billing_address][region_id]"]').select('Minnesota')
  cy.get('input[name="order[billing_address][postcode]"]').type('12345')
  cy.get('input[name="order[billing_address][telephone]"]').type('999-999-9999')

  cy.contains('Get shipping methods and rates').click()
  cy.get('input[name="order[shipping_method]"]').check()

  cy.contains('Submit Order').click()

  cy.contains('The order has been created')
})
