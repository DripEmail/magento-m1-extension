import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"

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
  if (site == 'main') {
    websiteKey = 'Main Website'
  } else {
    websiteKey = `${site}_website`
  }
  cy.get('select#store_switcher').select(websiteKey)
  cy.contains('Module Settings').click()
  cy.contains('API Settings').click()
  cy.get('input[name="groups[module_settings][fields][is_enabled][inherit]"]').uncheck()
  cy.get('select[name="groups[module_settings][fields][is_enabled][value]"]').select('Yes')
  cy.get('input[name="groups[api_settings][fields][account_id][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][account_id][value]"]').type('123456')
  cy.get('input[name="groups[api_settings][fields][api_key][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][api_key][value]"]').type('abc123')
  cy.get('input[name="groups[api_settings][fields][url][inherit]"]').uncheck()
  cy.get('input[name="groups[api_settings][fields][url][value]"]').clear().type('http://mock:1080/v2/')
  cy.contains('Save Config').click()
})

// Simple Product
Given('I have configured a simple widget', function() {
  cy.contains('Manage Products').click({ force: true })
  cy.contains('Add Product').click()
  cy.contains('Continue').click()
  cy.get('input[name="product[name]"]').type('Widget 1')
  cy.get('textarea[name="product[description]"]').type('This is really a widget. There are many like it, but this one is mine.')
  cy.get('textarea[name="product[short_description]"]').type('This is really a widget.')
  cy.get('input[name="product[sku]"]').type('widg-1')
  cy.get('input[name="product[weight]"]').type('120')
  cy.get('select[name="product[status]"]').select('Enabled')
  cy.get('a[name="group_8"]').click()
  cy.get('input[name="product[price]"]').type('120')
  cy.get('select[name="product[tax_class_id]"]').select('None')
  cy.get('a[name="inventory"]').click()
  cy.get('input[name="product[stock_data][qty]"]').type('120')
  cy.get('select[name="product[stock_data][is_in_stock]"]').select('In Stock')
  cy.contains('Save').click()
})

// Configurable Product
Given('I have configured a configurable widget', function() {
  cy.contains('Manage Attributes').click({ force: true })
  cy.contains('Add New Attribute').click()
  cy.get('input[name="attribute_code"]').type('widget_size')
  cy.get('select[name="frontend_input"]').select('Dropdown')
  cy.get('select[name="is_global"]').select('Global')
  cy.get('select[name="is_configurable"]').select('Yes')
  cy.get('a#product_attribute_tabs_labels').click()
  cy.get('input[name="frontend_label[0]"]').type('Widget Size')
  cy.contains('Add Option').click()
  cy.get('input[name="option[value][option_0][0]"]').type('XL') // Admin
  cy.contains('Save Attribute').click()
  // Make sure the page reload has finished.
  cy.contains('Add New Attribute')

  cy.contains('Manage Attribute Sets').click({ force: true })
  cy.contains('Add New Set').click()
  cy.get('input[name="attribute_set_name"]').type('widget_size_set')
  cy.contains('Save Attribute Set').click()
  cy.get('#tree-div2').contains('widget_size').drag('#tree-div1 .folder')
  cy.contains('Save Attribute Set').click()
  // Make sure the page reload has finished.
  cy.contains('Add New Set')

  cy.contains('Manage Products').click({ force: true })
  cy.contains('Add Product').click()
  cy.get('select[name="type"]').select('Configurable Product')
  cy.get('select[name="set"]').select('widget_size_set')
  cy.contains('Continue').click()
  cy.contains('Widget Size').click() // TODO: Make this a checkbox check instead of a click.
  cy.contains('Continue').click()
  cy.get('input[name="product[name]"]').type('Widget 1')
  cy.get('textarea[name="product[description]"]').type('This is really a widget. There are many like it, but this one is mine.')
  cy.get('textarea[name="product[short_description]"]').type('This is really a widget.')
  cy.get('input[name="product[sku]"]').type('widg-1')
  cy.get('select[name="product[status]"]').select('Enabled')
  cy.get('#product_info_tabs').contains('Prices').click()
  cy.get('input[name="product[price]"]').type('120')
  cy.get('select[name="product[tax_class_id]"]').select('None')
  cy.get('a[name="inventory"]').click()
  cy.get('select[name="product[stock_data][is_in_stock]"]').select('In Stock')

  cy.get('#product_info_tabs').contains('Associated Products').click()
  cy.contains('Save and Continue Edit').click()

  cy.get('#simple_product').within(() => {
    cy.get('input[name="simple_product[weight]"]').type('120')
    cy.get('select[name="simple_product[status]"]').select('Enabled')
    cy.get('select[name="simple_product[visibility]"]').select('Catalog, Search')
    cy.get('select[name="simple_product[widget_size]"]').select('XL')
    cy.get('input[name="simple_product[stock_data][qty]"]').type('120')
    cy.get('select[name="simple_product[stock_data][is_in_stock]"]').select('In Stock')
    cy.contains('Quick Create').click()
    // cy.get('a[name="websites"]').click()
    // cy.get('#product_website_1').check()
  })

  cy.contains('Save').click()
})
