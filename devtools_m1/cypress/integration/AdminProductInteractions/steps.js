import { Given, When, Then } from "cypress-cucumber-preprocessor/steps"
import { mockServerClient } from "mockserver-client"

const Mockclient = mockServerClient("localhost", 1080);

Then('A simple product event should be sent to Drip', function() {
  cy.log('Validating that the product event call happened and has everything we need')
  cy.wrap(Mockclient.retrieveRecordedRequests({
    'path': '/v3/123456/shopper_activity/order'
  })).then(function(recordedRequests) {
    expect(recordedRequests).to.have.lengthOf(1)
    const body = JSON.parse(recordedRequests[0].body.string)
    expect(body.action).to.eq('created')
    expect(body.provider).to.eq('mageno')
    expect(body.product_id).to.eq('')
    expect(body.sku).to.eq('')
    expect(body.name).to.eq('')
    expect(body.price).to.eq('')
    expect(body.inventory).to.eq('')
    expect(body.product_url).to.eq('')
    expect(body.image_url).to.eq('')
    expect(body.categories).to.eq('')
    expect(body.brand).to.eq('')
  })
})
