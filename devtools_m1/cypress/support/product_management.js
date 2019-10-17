Cypress.Commands.add("createProduct", (desc) => {
  // You must provide at least the following:
  // "sku"
  // "name"
  // "description"
  // "shortDescription"

  cy.log('Creating magento product')
  const str = JSON.stringify(desc)
  cy.exec(`echo '${str}' | ./docker_compose.sh exec -T web /bin/php -f shell/drip/create_product.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("createCustomer", (desc) => {
  cy.log('Creating magento customer')
  const str = JSON.stringify(desc)
  cy.exec(`echo '${str}' | ./docker_compose.sh exec -T web /bin/php -f shell/drip/create_customer.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})
