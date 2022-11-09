Cypress.Commands.add("createProduct", (desc) => {
  // You must provide at least the following:
  // "sku"
  // "name"
  // "description"
  // "shortDescription"

  cy.log('Creating Magento Product')
  const str = JSON.stringify(desc)
  cy.exec(`[[ $CONTINUOUS_INTEGRATION = "true" ]] && WEB="web-travis" || WEB="web-local";echo '${str}' | ./docker_compose.sh exec -T $WEB /bin/php5.6 -f shell/drip/create_product.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})

Cypress.Commands.add("createCustomer", (desc) => {
  cy.log('Creating Magento Customer')
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

Cypress.Commands.add('createAttributes', (desc) => {
  /*
   * example [minimum] hash to create an attribute.
   * pass in multiple attributes to create many attributes
   * uses "options" to create the variants of a configurable


  [
    {
      "code": "widgetsize",
      "scope": "global",
      "type": "varchar",
      "input": "select",
      "visible": 1,
      "required": 0,
      "configurable": 1,  <-- indicates this is for a "configurable" product
      "filterable": 0,
      "visible_on_front": 0,
      "label": "widget size",
      "apply_to": ["configurable"],
      "options": ["Extra Small", "Small", "Medium", "Large", "Extra Large"]  <-- the options(variants) associated with a configurable product
    }
  ]

  */
  cy.log('Creating Magento Attribute')
  const str = JSON.stringify(desc)
  cy.exec(`[[ $CONTINUOUS_INTEGRATION = "true" ]] && WEB="web-travis" || WEB="web-local";echo '${str}' | ./docker_compose.sh exec -T $WEB /bin/php5.6 -f shell/drip/create_attributes.php`, {
    env: {
      DRIP_COMPOSE_ENV: 'test'
    }
  })
})
