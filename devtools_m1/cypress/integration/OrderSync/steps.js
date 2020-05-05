import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Given('I have configured a no-name-widget', function () {
  cy.createProduct({
    "storeId": 1,
    "sku": "widg-1",
    "name": "",
    "description": "I have been through the desert [with] a [widget] with no name...",
    "shortDescription": "This is really a widget.",
    "websiteIds": [1]
  })
})

When('I create an order with the no-name-widget', function () {
  cy.contains('Orders').click({ force: true })
  cy.contains('Create New Order').click()

  // Select customer
  cy.contains('John Doe').click()

  // Add product to order
  cy.contains('Add Products', { timeout: 30000 }).click()
  cy.contains('widg-1').click()
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

Given('I have configured a no-name-widget', function () {
  cy.createProduct({
    "storeId": 1,
    "sku": "widg-1",
    "name": "",
    "description": "I have been through the desert [with] a [widget] with no name...",
    "shortDescription": "This is really a widget.",
    "websiteIds": [1]
  })
})

When('I create an order with the no-name-widget', function () {
  cy.contains('Orders').click({ force: true })
  cy.contains('Create New Order').click()

  // Select customer
  cy.contains('John Doe').click()

  // Add product to order
  cy.contains('Add Products', { timeout: 30000 }).click()
  cy.contains('widg-1').click()
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

Then('an modified order event is sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(16.22)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(1)
    expect(order.total_shipping).to.eq(5)
    expect(order.items).to.have.lengthOf(1)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('100000001')
    expect(order.order_public_id).to.eq('100000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)

    validateAddress(order.billing_address)
    validateAddress(order.shipping_address)

    const item = order.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3005/media/catalog/product/')
    expect(item.name).to.eq('[Missing Product 1-1 Name]')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq('http://main.magento.localhost:3005/.html')
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

When('I click order sync', function () {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.contains('Configuration').click({ force: true })
  cy.contains('Drip Connect Configuration').click()
  cy.get('select#store_switcher').select('site1_store_view')
  cy.contains('Drip Actions').click()
  cy.contains('Sync All Orders To Drip').click()
  cy.contains('Queued')
  cy.runCron()
})

When('I start an order sync', function () {
  cy.log('Resetting mocks')
  cy.wrap(Mockclient.reset())

  cy.contains('Configuration').click({ force: true })
  cy.contains('Drip Connect Configuration').click()
  cy.get('#store_switcher').select('Default Store View')
  cy.contains('Drip Actions').click()
  cy.contains('Sync All Orders To Drip').click()
  cy.contains('Queued')
  cy.runCron()
})

Then('an order event is sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(16.22)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(1)
    expect(order.total_shipping).to.eq(5)
    expect(order.items).to.have.lengthOf(1)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('30000000001')
    expect(order.order_public_id).to.eq('30000000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)

    validateAddress(order.billing_address)
    validateAddress(order.shipping_address)

    const item = order.items[0]
    validateWidget(item, "3", "1")
  })
})

Then('an modified order event is sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(16.22)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(1)
    expect(order.total_shipping).to.eq(5)
    expect(order.items).to.have.lengthOf(1)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('100000001')
    expect(order.order_public_id).to.eq('100000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)

    validateAddress(order.billing_address)
    validateAddress(order.shipping_address)

    const item = order.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3005/media/catalog/product/')
    expect(item.name).to.eq('[Missing Product 1-1 Name]')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('1')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq('http://main.magento.localhost:3005/.html')
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})

Then('an order event with virtual product is sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(11.22)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(1)
    expect(order.total_shipping).to.eq(0)
    expect(order.items).to.have.lengthOf(1)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('30000000001')
    expect(order.order_public_id).to.eq('30000000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)

    validateAddress(order.billing_address)

    expect(order.shipping_address).to.be.undefined

    const item = order.items[0]
    validateVirtualProduct(item)
  })
})

Then('an order event with both products is sent to Drip', function () {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order/batch'
  })).then(function (recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.orders).to.have.lengthOf(1)
    const order = body.orders[0]
    expect(order.action).to.eq('placed')
    expect(order.email).to.eq('jd1@example.com')
    expect(order.grand_total).to.eq(27.44)
    expect(order.initial_status).to.eq('unsubscribed')
    expect(order.items_count).to.eq(2)
    expect(order.total_shipping).to.eq(5)
    expect(order.items).to.have.lengthOf(2)

    expect(order.currency).to.eq('USD')
    // TODO: This needs to be figured out.
    // expect(order.magento_source).to.eq('Admin')
    expect(order.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(order.order_id).to.eq('30000000001')
    expect(order.order_public_id).to.eq('30000000001')
    expect(order.provider).to.eq('magento')
    expect(order.total_discounts).to.eq(0)
    expect(order.total_taxes).to.eq(0)

    validateAddress(order.billing_address)
    validateAddress(order.shipping_address)

    const item1 = order.items[0]
    validateVirtualProduct(item1)
    const item2 = order.items[1]
    validateWidget(item2, "4", "2")
  })
})

function validateWidget(item, product_id, product_variant_id) {
  expect(item.categories).to.be.empty
  expect(item.discounts).to.eq(0)
  expect(item.image_url).to.eq('http://site1.magento.localhost:3005/media/catalog/product/')
  expect(item.name).to.eq('Widget 1')
  expect(item.price).to.eq(11.22)
  expect(item.product_id).to.eq(product_id)
  expect(item.product_variant_id).to.eq(product_variant_id)
  expect(item.product_url).to.eq('http://site1.magento.localhost:3005/widget-1.html')
  expect(item.quantity).to.eq(1)
  expect(item.sku).to.eq('widg-1-xl')
  expect(item.taxes).to.eq(0)
  expect(item.total).to.eq(11.22)
}

function validateVirtualProduct(item) {
  expect(item.categories).to.be.empty
  expect(item.discounts).to.eq(0)
  expect(item.image_url).to.eq('http://site1.magento.localhost:3005/media/catalog/product/')
  expect(item.name).to.eq('Virtual 1')
  expect(item.price).to.eq(11.22)
  expect(item.product_id).to.eq('1')
  expect(item.product_variant_id).to.eq('1')
  expect(item.product_url).to.eq('http://site1.magento.localhost:3005/virtual-1.html')
  expect(item.quantity).to.eq(1)
  expect(item.sku).to.eq('v-widg-1')
  expect(item.taxes).to.eq(0)
  expect(item.total).to.eq(11.22)
}

function validateAddress(address) {
  expect(address.address_1).to.eq('123 Main St.')
  expect(address.address_2).to.eq('')
  expect(address.city).to.eq('Centerville')
  expect(address.company).to.eq('')
  expect(address.country).to.eq('US')
  expect(address.first_name).to.eq('John')
  expect(address.last_name).to.eq('Doe')
  expect(address.phone).to.eq('999-999-9999')
  expect(address.postal_code).to.eq('12345')
  expect(address.state).to.eq('Minnesota')
}
