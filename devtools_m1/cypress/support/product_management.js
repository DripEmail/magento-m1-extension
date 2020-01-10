Cypress.Commands.add("createProduct", (desc) => {
  // You must provide at least the following:
  // "sku"
  // "name"
  // "description"
  // "shortDescription"

  cy.log('Creating magento product')
  const str = JSON.stringify(desc)
  cy.exec(`[[ $CONTINUOUS_INTEGRATION = "true" ]] && WEB="web-travis" || WEB="web-local";echo '${str}' | ./docker_compose.sh exec -T $WEB /bin/php5.6 -f shell/drip/create_product.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("createCustomer", (desc) => {
  cy.log('Creating magento customer')
  const str = JSON.stringify(desc)
  cy.exec(`[[ $CONTINUOUS_INTEGRATION = "true" ]] && WEB="web-travis" || WEB="web-local";echo '${str}' | ./docker_compose.sh exec -T $WEB /bin/php5.6 -f shell/drip/create_customer.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("runCron", (desc) => {
  cy.log('Running Magento Cron')
  const str = JSON.stringify(desc)
  cy.exec(`./cron.sh`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})
