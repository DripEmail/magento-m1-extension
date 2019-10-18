import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

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

Then('an order event is sent to Drip', function() {
  cy.log('Validating that the order call has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('placed')
    expect(body.email).to.eq('jd1@example.com')
    expect(body.grand_total).to.eq(16.22)
    expect(body.initial_status).to.eq('unsubscribed')
    expect(body.items_count).to.eq(1)
    expect(body.total_shipping).to.eq(5)
    expect(body.items).to.have.lengthOf(1)

    expect(body.currency).to.eq('USD')
    expect(body.magento_source).to.eq('Admin')
    expect(body.occurred_at).to.match(/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z/)
    expect(body.order_id).to.eq('100000001')
    expect(body.order_public_id).to.eq('100000001')
    expect(body.provider).to.eq('magento')
    expect(body.total_discounts).to.eq(0)
    expect(body.total_taxes).to.eq(0)
    expect(body.version).to.match(/^Magento 1\.9\.4\.2, Drip Extension \d+\.\d+\.\d+$/)

    expect(body.billing_address.address_1).to.eq('123 Main St.')
    expect(body.billing_address.address_2).to.eq('')
    expect(body.billing_address.city).to.eq('Centerville')
    expect(body.billing_address.company).to.eq('')
    expect(body.billing_address.country).to.eq('US')
    expect(body.billing_address.first_name).to.eq('John')
    expect(body.billing_address.last_name).to.eq('Doe')
    expect(body.billing_address.phone).to.eq('999-999-9999')
    expect(body.billing_address.postal_code).to.eq('12345')
    expect(body.billing_address.state).to.eq('Minnesota')

    expect(body.shipping_address.address_1).to.eq('123 Main St.')
    expect(body.shipping_address.address_2).to.eq('')
    expect(body.shipping_address.city).to.eq('Centerville')
    expect(body.shipping_address.company).to.eq('')
    expect(body.shipping_address.country).to.eq('US')
    expect(body.shipping_address.first_name).to.eq('John')
    expect(body.shipping_address.last_name).to.eq('Doe')
    expect(body.shipping_address.phone).to.eq('999-999-9999')
    expect(body.shipping_address.postal_code).to.eq('12345')
    expect(body.shipping_address.state).to.eq('Minnesota')

    const item = body.items[0]
    expect(item.categories).to.be.empty
    expect(item.discounts).to.eq(0)
    expect(item.image_url).to.eq('http://main.magento.localhost:3005/media/catalog/product/')
    expect(item.name).to.eq('Widget 1')
    expect(item.price).to.eq(11.22)
    expect(item.product_id).to.eq('3')
    expect(item.product_variant_id).to.eq('1')
    expect(item.product_url).to.eq('http://main.magento.localhost:3005/widget-1.html')
    expect(item.quantity).to.eq(1)
    expect(item.sku).to.eq('widg-1-xl')
    expect(item.taxes).to.eq(0)
    expect(item.total).to.eq(11.22)
  })
})
